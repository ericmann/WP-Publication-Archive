<?php
/**
 * Implements SPEC.md §8 Phase 0 item 4 and Decisions D8: 3.0.1's rewrite
 * rules, query vars and link generation, including the faithful port of the
 * post_type_link hijack.
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA;

final class Rewrites {

	private Flags $flags;

	/**
	 * True while link() is generating a permalink through get_permalink(),
	 * so filter_post_type_link() does not recurse into itself. D8.
	 */
	private bool $suspended = false;

	/**
	 * True once link() has run at least once this request. D8: from then on
	 * filter_post_type_link() hijacks every post_type_link call for a
	 * publication, exactly as 3.0.1 did.
	 */
	private bool $armed = false;

	public function __construct( Flags $flags ) {
		$this->flags = $flags;
	}

	public function flags(): Flags {
		return $this->flags;
	}

	/**
	 * Hooked to init.
	 */
	public function register(): void {
		add_rewrite_tag( Keys::TAG_DOWNLOAD, '(.+)' );
		add_rewrite_tag( Keys::TAG_OPEN, '(.+)' );
		add_rewrite_tag( Keys::TAG_ALT, '(.+)' );

		add_rewrite_rule( '^' . Keys::REWRITE_BASE . '/' . Keys::ENDPOINT_DOWNLOAD . '/([^/]+)(/[0-9]+)?/?$', 'index.php?' . Keys::POST_TYPE . '=$matches[1]&' . Keys::QV_DOWNLOAD . '=yes', 'top' );
		add_rewrite_rule( '^' . Keys::REWRITE_BASE . '/' . Keys::ENDPOINT_VIEW . '/([^/]+)(/[0-9]+)?/?$', 'index.php?' . Keys::POST_TYPE . '=$matches[1]&' . Keys::QV_OPEN . '=yes', 'top' );
		add_rewrite_rule( '^' . Keys::REWRITE_BASE . '/' . Keys::ENDPOINT_ALTDOWN . '/([^/]+)/([^/]+)/?$', 'index.php?' . Keys::POST_TYPE . '=$matches[1]&' . Keys::QV_DOWNLOAD . '=yes&' . Keys::QV_ALT . '=$matches[2]', 'top' );
		add_rewrite_rule( '^' . Keys::REWRITE_BASE . '/' . Keys::ENDPOINT_ALTVIEW . '/([^/]+)/([^/]+)/?$', 'index.php?' . Keys::POST_TYPE . '=$matches[1]&' . Keys::QV_OPEN . '=yes&' . Keys::QV_ALT . '=$matches[2]', 'top' );

		add_rewrite_rule( '^' . Keys::REWRITE_BASE . '/' . Keys::REWRITE_CATEGORY . '/(.+?)/feed/(feed|rdf|rss|rss2|atom)/?$', 'index.php?post_type=' . Keys::POST_TYPE . '&category_name=$matches[1]&feed=$matches[2]', 'top' );
		add_rewrite_rule( '^' . Keys::REWRITE_BASE . '/' . Keys::REWRITE_CATEGORY . '/(.+?)/(feed|rdf|rss|rss2|atom)/?$', 'index.php?post_type=' . Keys::POST_TYPE . '&category_name=$matches[1]&feed=$matches[2]', 'top' );
		add_rewrite_rule( '^' . Keys::REWRITE_BASE . '/' . Keys::REWRITE_CATEGORY . '/(.+?)/page/?([0-9]{1,})/?$', 'index.php?post_type=' . Keys::POST_TYPE . '&category_name=$matches[1]&paged=$matches[2]', 'top' );
		add_rewrite_rule( '^' . Keys::REWRITE_BASE . '/' . Keys::REWRITE_CATEGORY . '/(.+?)/?$', 'index.php?post_type=' . Keys::POST_TYPE . '&category_name=$matches[1]', 'top' );
	}

	/**
	 * Hooked to query_vars.
	 *
	 * @param array<int, string> $vars
	 *
	 * @return array<int, string>
	 */
	public function query_vars( array $vars ): array {
		$vars[] = Keys::QV_PAGED;

		return $vars;
	}

	/**
	 * 3.0.1 get_link(). $key is the alternate download description; null
	 * means none.
	 */
	public function link( int $publication_id, string $endpoint, ?string $permalink = null, ?string $key = null ): string {
		if ( null === $permalink ) {
			$this->suspended = true;

			try {
				$permalink = get_permalink( $publication_id );
			} finally {
				$this->suspended = false;
			}

			$this->armed = true;
		}

		if ( '' === $this->flags->permalink_structure() ) {
			$new = add_query_arg( $endpoint, 'yes', $permalink );

			if ( null !== $key ) {
				$new = add_query_arg( Keys::QUERY_ALT_KEY, $key, $new );
			}
		} else {
			$new = site_url() . '/' . Keys::REWRITE_BASE . '/' . $endpoint . '/' . basename( (string) $permalink );

			if ( null !== $key ) {
				$new .= '/' . $key;
			}
		}

		return $new;
	}

	public function open_link( int $id ): string {
		return $this->link( $id, Keys::ENDPOINT_VIEW );
	}

	public function download_link( int $id ): string {
		return $this->link( $id, Keys::ENDPOINT_DOWNLOAD );
	}

	public function alternate_open_link( int $id, ?string $key = null ): string {
		return $this->link( $id, Keys::ENDPOINT_ALTVIEW, null, $key );
	}

	public function alternate_download_link( int $id, ?string $key = null ): string {
		return $this->link( $id, Keys::ENDPOINT_ALTDOWN, null, $key );
	}

	/**
	 * Hooked to post_type_link. D8: once armed, every later permalink for a
	 * publication is generated through link(), exactly as 3.0.1's
	 * publication_link() did.
	 */
	public function filter_post_type_link( string $permalink, \WP_Post $post ): string {
		if ( ! $this->armed || $this->suspended || Keys::POST_TYPE !== $post->post_type ) {
			return $permalink;
		}

		return $this->link( $post->ID, Keys::QV_OPEN, $permalink );
	}

	/**
	 * Tests only: clears the D8 hijack armed by a prior link() call.
	 */
	public function disarm(): void {
		$this->armed = false;
	}
}
