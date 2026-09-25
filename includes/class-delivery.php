<?php
/**
 * Implements SPEC.md §6.2: the view and download endpoints. Every request
 * validates its stored URL through Url_Policy and checks Dam_Bridge before
 * it leaves this class, so an invalid or DAM-withheld file 404s instead of
 * leaking bytes (D1, D17). Default mode redirects; opt-in proxy mode
 * streams through a temp file via Streamer::send() (P1-06).
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA;

final class Delivery {

	private Url_Policy $policy;

	private Streamer $streamer;

	private Dam_Bridge $dam;

	/** @var callable */
	private $exit;

	/**
	 * Set only while a redirect to $redirect_host is in progress (P3); read
	 * by allowed_redirect_hosts(), which is on a live hook.
	 */
	private ?string $redirect_host = null;

	/**
	 * $header is accepted for constructor-signature compatibility with
	 * Streamer and older tests, but Delivery no longer emits headers
	 * itself: wp_safe_redirect() sets its own, and the proxy path's headers
	 * go through Streamer::send() instead. It may be left unused.
	 */
	public function __construct( Url_Policy $policy, Streamer $streamer, Dam_Bridge $dam, ?callable $exit = null, ?callable $header = null ) {
		unset( $header );

		$this->policy   = $policy;
		$this->streamer = $streamer;
		$this->dam      = $dam;
		$this->exit     = $exit ?? static function () {
			exit;
		};
	}

	public function policy(): Url_Policy {
		return $this->policy;
	}

	public function streamer(): Streamer {
		return $this->streamer;
	}

	public function dam(): Dam_Bridge {
		return $this->dam;
	}

	/**
	 * Hooked to allowed_redirect_hosts. Adds the host wp_safe_redirect() is
	 * about to send the client to, but only while such a redirect is in
	 * progress ($redirect_host is set/cleared in try/finally around
	 * wp_safe_redirect() by redirect()); otherwise returns $hosts unchanged.
	 *
	 * @param array<int, string> $hosts
	 *
	 * @return array<int, string>
	 */
	public function allowed_redirect_hosts( array $hosts ): array {
		if ( null === $this->redirect_host ) {
			return $hosts;
		}

		$hosts[] = $this->redirect_host;

		return $hosts;
	}

	/**
	 * Runs $render() with $host added to allowed_redirect_hosts() for the
	 * duration of the call (P3), so wp_safe_redirect() accepts a host it
	 * has not been told about in advance.
	 */
	public function with_redirect_host( string $host, callable $render ): void {
		$this->redirect_host = $host;

		try {
			$render();
		} finally {
			$this->redirect_host = null;
		}
	}

	/**
	 * Hooked to template_redirect.
	 */
	public function handle(): void {
		if ( '' !== (string) get_query_var( Keys::QV_OPEN ) ) {
			$this->open();

			return;
		}

		if ( '' !== (string) get_query_var( Keys::QV_DOWNLOAD ) ) {
			$this->download();
		}
	}

	/**
	 * 3.0.1 WP_Publication_Archive::open_file().
	 */
	public function open(): void {
		$this->deliver( Keys::QV_OPEN, false );
	}

	/**
	 * 3.0.1 WP_Publication_Archive::download_file().
	 */
	public function download(): void {
		$this->deliver( Keys::QV_DOWNLOAD, true );
	}

	/**
	 * SPEC §6.2 Delivery::handle() steps, shared by open() and download().
	 */
	private function deliver( string $query_var, bool $is_download ): void {
		if ( '' === (string) get_query_var( $query_var ) ) {
			return;
		}

		$post = get_post();

		if ( null === $post || Keys::POST_TYPE !== $post->post_type ) {
			return;
		}

		$uri = $this->policy->normalise( $this->resolve_uri( $post ) );
		$uri = $is_download ? Hooks::download_url( $uri ) : Hooks::open_url( $uri );

		$validated = $this->policy->validate( $uri );

		if ( ! is_string( $validated ) ) {
			wp_die( esc_html__( 'File not found.', 'wp-publication-archive' ), '', array( 'response' => 404 ) );
		}

		if ( $this->dam->is_withheld( $validated ) ) {
			wp_die( esc_html__( 'File not found.', 'wp-publication-archive' ), '', array( 'response' => 404 ) );
		}

		if ( 'redirect' === self::decide( Hooks::mask_url( Keys::DEFAULT_MASK_URL ), null, 0 ) ) {
			$this->redirect( $validated );

			return;
		}

		$this->proxy( $validated, $is_download );
	}

	/**
	 * 3.0.1 key semantics: the stored doc URL, or the alternate whose
	 * description equals urldecode( QV_ALT ).
	 */
	private function resolve_uri( \WP_Post $post ): string {
		$alt = get_query_var( Keys::QV_ALT );

		if ( '' === (string) $alt ) {
			return (string) get_post_meta( $post->ID, Keys::META_DOC, true );
		}

		$alternates = get_post_meta( $post->ID, Keys::META_ALTERNATES );

		foreach ( $alternates as $candidate ) {
			if ( urldecode( (string) $alt ) === $candidate['description'] ) {
				return (string) $candidate['url'];
			}
		}

		return '';
	}

	private function redirect( string $url ): void {
		$host = (string) wp_parse_url( $url, PHP_URL_HOST );

		$this->with_redirect_host(
			$host,
			static function () use ( $url ) {
				wp_safe_redirect( $url, 302 ); // phpcs:ignore WordPressVIPMinimum.Security.ExitAfterRedirect.NoExit -- reason: the exit callable runs in redirect(), right after with_redirect_host() returns; the sniff can't see across this closure boundary.
			}
		);

		( $this->exit )();
	}

	private function proxy( string $url, bool $is_download ): void {
		$timeout = Hooks::proxy_timeout( Keys::DEFAULT_PROXY_TIMEOUT );

		$head = wp_safe_remote_head( $url, array( 'timeout' => $timeout ) );

		$content_length = null;

		if ( ! is_wp_error( $head ) ) {
			$content_length_header = wp_remote_retrieve_header( $head, 'content-length' );

			if ( '' !== $content_length_header ) {
				$content_length = (int) $content_length_header;
			}
		}

		if ( 'redirect' === self::decide( true, $content_length, Hooks::proxy_max_bytes( Keys::DEFAULT_PROXY_MAX_BYTES ) ) ) {
			$this->redirect( $url );

			return;
		}

		$tmp = wp_tempnam( basename( (string) wp_parse_url( $url, PHP_URL_PATH ) ) );

		$response = wp_safe_remote_get(
			$url,
			array(
				'stream'   => true,
				'filename' => $tmp,
				'timeout'  => $timeout,
			)
		);

		$code = wp_remote_retrieve_response_code( $response );

		if ( is_wp_error( $response ) || (int) $code < 200 || (int) $code >= 300 ) {
			if ( file_exists( $tmp ) ) {
				wp_delete_file( $tmp );
			}

			$this->redirect( $url );

			return;
		}

		// SPEC §6.2 step 1: resolve content type with wp_check_filetype()
		// directly (Delivery does not depend on Icons, per §4.2's module
		// map), falling back to the response's content-type header, then
		// Keys::CONTENT_TYPE_FALLBACK.
		$type         = wp_check_filetype( basename( (string) wp_parse_url( $url, PHP_URL_PATH ) ) )['type'];
		$content_type = false === $type ? Keys::CONTENT_TYPE_FALLBACK : $type;

		if ( Keys::CONTENT_TYPE_FALLBACK === $content_type ) {
			$response_type = wp_remote_retrieve_header( $response, 'content-type' );
			$content_type  = '' !== $response_type ? (string) $response_type : Keys::CONTENT_TYPE_FALLBACK;
		}

		$filename = $is_download ? sanitize_file_name( basename( (string) wp_parse_url( $url, PHP_URL_PATH ) ) ) : null;

		$this->streamer->send( $tmp, $content_type, $filename );
	}

	/**
	 * The pure redirect-or-proxy decision (unit tested).
	 */
	public static function decide( bool $mask, ?int $content_length, int $max_bytes ): string {
		if ( ! $mask ) {
			return 'redirect';
		}

		if ( null !== $content_length && $content_length > $max_bytes ) {
			return 'redirect';
		}

		return 'proxy';
	}
}
