<?php
/**
 * Implements SPEC.md §8 Phase 0 item 4: the 3.0.1 view and download
 * endpoints (`open_file()`/`download_file()`), ported onto Streamer and
 * Icons. D1, D6 and D17 fixes are Phase 1.
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA;

final class Delivery {

	private Url_Policy $policy;

	private Streamer $streamer;

	private Dam_Bridge $dam;

	private Icons $icons;

	/** @var callable */
	private $exit;

	/** @var callable */
	private $header;

	/**
	 * Set only while a redirect to $redirect_host is in progress (P1-07,
	 * P3); read by allowed_redirect_hosts(), which is on a live hook.
	 */
	private ?string $redirect_host = null;

	public function __construct( Url_Policy $policy, Streamer $streamer, Dam_Bridge $dam, Icons $icons, ?callable $exit = null, ?callable $header = null ) {
		$this->policy   = $policy;
		$this->streamer = $streamer;
		$this->dam      = $dam;
		$this->icons    = $icons;
		$this->exit     = $exit ?? static function () {
			exit;
		};
		$this->header   = $header ?? 'header';
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

	public function icons(): Icons {
		return $this->icons;
	}

	/**
	 * Hooked to allowed_redirect_hosts. Adds the host wp_safe_redirect() is
	 * about to send the client to, but only while such a redirect is in
	 * progress (P1-07 sets/clears $redirect_host in try/finally around
	 * wp_safe_redirect()); otherwise returns $hosts unchanged.
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
	 * duration of the call. P1-07 wraps its wp_safe_redirect() with this
	 * (P3), so wp_safe_redirect() accepts a host it has not been told about
	 * in advance.
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
		if ( '' === (string) get_query_var( Keys::QV_OPEN ) ) {
			return;
		}

		$uri = $this->resolve_uri();
		$uri = Hooks::open_url( $uri );

		if ( empty( $uri ) ) {
			return;
		}

		if ( Hooks::mask_url( true ) ) {
			$this->stream(
				$uri,
				array(
					'HTTP/1.1 200 OK',
					'Expires: Wed, 9 Nov 1983 05:00:00 GMT',
				)
			);
		} else {
			$this->redirect( $uri );
		}
	}

	/**
	 * 3.0.1 WP_Publication_Archive::download_file().
	 */
	public function download(): void {
		if ( '' === (string) get_query_var( Keys::QV_DOWNLOAD ) ) {
			return;
		}

		$uri = $this->resolve_uri();
		$uri = Hooks::download_url( $uri );

		if ( empty( $uri ) ) {
			return;
		}

		if ( Hooks::mask_url( true ) ) {
			$this->stream(
				$uri,
				array(
					'HTTP/1.1 200 OK',
					'Expires: Wed, 9 Nov 1983 05:00:00 GMT',
					'Content-Disposition: attachment; filename=' . basename( $uri ),
				)
			);
		} else {
			$this->redirect( $uri );
		}
	}

	/**
	 * @return string
	 */
	private function resolve_uri() {
		$publication = new Publication_Item( get_post() );

		$uri = '';

		$alt = get_query_var( Keys::QV_ALT );

		if ( '' !== (string) $alt ) {
			foreach ( $publication->alternates as $candidate ) {
				if ( urldecode( (string) $alt ) === $candidate['description'] ) {
					$uri = $candidate['url'];
					break;
				}
			}
		} else {
			$uri = str_replace( 'http|', 'http://', $publication->uri );
			$uri = str_replace( 'https|', 'https://', $uri );
		}

		return $uri;
	}

	/**
	 * @param array<int, string> $leading_headers
	 */
	private function stream( string $uri, array $leading_headers ): void {
		$content_length = false;
		$last_modified  = false;

		$request = wp_safe_remote_head( $uri );

		if ( ! is_wp_error( $request ) ) {
			$headers = wp_remote_retrieve_headers( $request );

			if ( isset( $headers['content-length'] ) ) {
				$content_length = $headers['content-length'];
			}

			if ( isset( $headers['last-modified'] ) ) {
				$last_modified = $headers['last-modified'];
			}
		}

		$headers = $leading_headers;

		$headers[] = 'Content-type: ' . $this->icons->mime_for( basename( $uri ) );
		$headers[] = 'Content-Transfer-Encoding: binary';

		if ( false !== $content_length ) {
			$headers[] = 'Content-Length: ' . $content_length;
		}

		if ( false !== $last_modified ) {
			$headers[] = 'Last-Modified: ' . $last_modified;
		}

		$this->streamer->passthrough( $uri, $headers );
	}

	private function redirect( string $uri ): void {
		( $this->header )( 'HTTP/1.1 303 See Other' );
		( $this->header )( 'Location: ' . $uri );

		( $this->exit )();
	}
}
