<?php
/**
 * Implements SPEC.md §8 Phase 0 item 4: the 3.0.1 WP_Publication_Archive
 * delegate. Not final; every method stays static, with 3.0.1's names,
 * parameters and defaults, phpdoc types only. Every member delegates
 * through \WPPA\Plugin::instance(); no hook is ever registered with these
 * callables (Plugin::register_hooks() wires its own service methods instead,
 * §2/P3).
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA\Legacy;

class Publication_Archive {

	/**
	 * Automatically upgrade the plugin data store from one version to another.
	 *
	 * @param int $from
	 *
	 * @return void
	 */
	public static function upgrade( $from ) {
		\WPPA\Plugin::instance()->upgrade()->run( $from );
	}

	/**
	 * Generate a link with a given endpoint.
	 *
	 * @param int         $publication_id Optional ID of the publication for which to generate a link.
	 * @param string      $endpoint       Optional endpoint name.
	 * @param bool|string $permalink      Optional existing permalink.
	 * @param bool|string $key            Optional alternate download key.
	 *
	 * @return string Download/Open link.
	 */
	protected static function get_link( $publication_id = 0, $endpoint = 'view', $permalink = false, $key = false ) {
		return \WPPA\Plugin::instance()->rewrites()->link(
			$publication_id,
			$endpoint,
			false === $permalink ? null : $permalink,
			false === $key ? null : $key
		);
	}

	/**
	 * Generate a link for a particular file download.
	 *
	 * @param int $publication_id Optional ID of the publication for which to retrieve a download link.
	 *
	 * @return string Open link.
	 */
	public static function get_open_link( $publication_id = 0 ) {
		return \WPPA\Plugin::instance()->rewrites()->open_link( $publication_id );
	}

	/**
	 * Generate a link for a particular file download.
	 *
	 * @param int $publication_id Optional ID of the publication for which to retrieve a download link.
	 *
	 * @return string Download link.
	 */
	public static function get_download_link( $publication_id = 0 ) {
		return \WPPA\Plugin::instance()->rewrites()->download_link( $publication_id );
	}

	/**
	 * Generate a link for a particular alternate file download.
	 *
	 * @param int         $publication_id Optional ID of the publication for which to retrieve a download link.
	 * @param string|bool $key            Optional key of the file to download.
	 *
	 * @return string Download link.
	 */
	public static function get_alternate_open_link( $publication_id = 0, $key = false ) {
		return \WPPA\Plugin::instance()->rewrites()->alternate_open_link( $publication_id, false === $key ? null : $key );
	}

	/**
	 * Generate a link for a particular alternate file download.
	 *
	 * @param int         $publication_id Optional ID of the publication for which to retrieve a download link.
	 * @param string|bool $key            Optional key of the file to download.
	 *
	 * @return string Download link.
	 */
	public static function get_alternate_download_link( $publication_id = 0, $key = false ) {
		return \WPPA\Plugin::instance()->rewrites()->alternate_download_link( $publication_id, false === $key ? null : $key );
	}

	/**
	 * Filter WordPress' request so that we can send a redirect to the file if it's requested.
	 *
	 * @return void
	 */
	public static function open_file() {
		\WPPA\Plugin::instance()->delivery()->open();
	}

	/**
	 * @return void
	 */
	public static function download_file() {
		\WPPA\Plugin::instance()->delivery()->download();
	}

	/**
	 * Get an image for the publication based on its MIME type.
	 *
	 * @param string $doctype MIME type of the file.
	 *
	 * @return string
	 */
	public static function get_image( $doctype ) {
		return \WPPA\Plugin::instance()->icons()->url_for( $doctype );
	}

	/**
	 * Queue up scripts and styles, based on whether the user is on the admin or the front-end.
	 *
	 * @return void
	 */
	public static function enqueue_scripts_and_styles() {
		if ( is_admin() ) {
			\WPPA\Plugin::instance()->assets()->enqueue_admin();
		}
	}

	/**
	 * Register the Publication custom post type.
	 *
	 * @return void
	 */
	public static function register_publication() {
		\WPPA\Plugin::instance()->post_type()->register();
	}

	/**
	 * Register the publication author taxonomy.
	 *
	 * @return void
	 */
	public static function register_author() {
		\WPPA\Plugin::instance()->post_type()->register();
	}

	/**
	 * Register custom meta boxes for the Publication page.
	 *
	 * @return void
	 */
	public static function pub_meta_boxes() {
		\WPPA\Plugin::instance()->meta_boxes()->add();
	}

	/**
	 * Build the Publication link box.
	 *
	 * @param \WP_Post $post
	 *
	 * @return void
	 */
	public static function doc_uri_box( $post ) {
		\WPPA\Plugin::instance()->meta_boxes()->render_doc( $post );
	}

	/**
	 * Build the Publication thumbnail image box.
	 *
	 * @param \WP_Post $post
	 *
	 * @return void
	 */
	public static function doc_thumb_box( $post ) {
		\WPPA\Plugin::instance()->meta_boxes()->render_thumb( $post );
	}

	/**
	 * Output a meta box with repeatable alternate upload fields.
	 *
	 * @param \WP_Post $post
	 *
	 * @return void
	 */
	public static function doc_alternates_box( $post ) {
		\WPPA\Plugin::instance()->meta_boxes()->render_alternates( $post );
	}

	/**
	 * Save our changes to Publication meta information.
	 *
	 * @param int $post_id ID of the Publication we're updating.
	 *
	 * @return int
	 */
	public static function save_meta( $post_id ) {
		return \WPPA\Plugin::instance()->meta_boxes()->save( $post_id );
	}

	/**
	 * Handle the plugin's shortcode and provided filters.
	 *
	 * @param array<string, mixed> $atts Shortcode arguments.
	 *
	 * @return string Shortcode output.
	 */
	public static function shortcode_handler( $atts ) {
		return \WPPA\Plugin::instance()->shortcode()->render( $atts );
	}

	/**
	 * Register new query variables.
	 *
	 * @param array<int, string> $public_vars Query variables.
	 *
	 * @return array<int, string> Query variables.
	 */
	public static function query_vars( $public_vars ) {
		return \WPPA\Plugin::instance()->rewrites()->query_vars( $public_vars );
	}

	/**
	 * Register our custom rewrite slugs and URLs.
	 *
	 * @return void
	 */
	public static function custom_rewrites() {
		\WPPA\Plugin::instance()->rewrites()->register();
	}

	/**
	 * D8 (Decisions, P2-01): this was 3.0.1's post_type_link hijack, dead
	 * code that removed and re-added a filter nothing else added and that
	 * no hook is ever registered with (§2). Returns $permalink unchanged.
	 *
	 * @param string   $permalink
	 * @param \WP_Post $post
	 *
	 * @return string
	 */
	public static function publication_link( $permalink, $post ) {
		unset( $post );

		return $permalink;
	}

	/**
	 * D8 (P2-08): this filter is never hooked to anything (§2); returns
	 * $content unchanged.
	 *
	 * @param string $content Regular post content from the `wp_posts` table.
	 *
	 * @return string
	 */
	public static function the_content( $content ) {
		return $content;
	}

	/**
	 * D8 (P2-08): this filter is never hooked to anything (§2); returns
	 * $title unchanged.
	 *
	 * @param string $title Original title.
	 * @param int    $id    Post ID.
	 *
	 * @return string
	 */
	public static function the_title( $title, $id = 0 ) {
		unset( $id );

		return $title;
	}

	/**
	 * Also check if the search term is contained in the publication's description.
	 *
	 * @param string $where Existing search query string.
	 *
	 * @return string
	 */
	public static function search( $where ) {
		// D4: the meta-search-and-distinct trio is not registered any more.
		return $where;
	}

	/**
	 * Add post meta to the search Query.
	 *
	 * @param string $join Existing search query string.
	 *
	 * @return string
	 */
	public static function search_join( $join ) {
		// D4: not registered any more.
		return $join;
	}

	/**
	 * Force the search to only return distinct values.
	 *
	 * @param string $distinct
	 *
	 * @return string
	 */
	public static function search_distinct( $distinct ) {
		// D4: not registered any more.
		return $distinct;
	}

	/**
	 * Utility function to return a WP_Query object with Publication posts.
	 *
	 * @param array<string, mixed> $args
	 *
	 * @return \WP_Query
	 */
	public static function query_publications( $args ) {
		return \WPPA\Plugin::instance()->post_type()->query( $args );
	}

	/**
	 * Allow users to filter the length of only publication summaries.
	 *
	 * @param int $length
	 *
	 * @return int
	 */
	public static function custom_excerpt_length( $length ) {
		return \WPPA\Plugin::instance()->templates()->excerpt_length( $length );
	}
}
