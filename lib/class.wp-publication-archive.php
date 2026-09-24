<?php
/**
 * Core functionality for the WP Publication Archive plugin.
 *
 * All functions are static members of this class to allow for easy namespacing.
 *
 * @module WP_Publication_Archive
 * @author Eric Mann
 */

/**
 * This class contains all of the functionality for the WP Publication Archive Plugin.
 *
 * All methods are static, so this class should not be instantiated.
 */
class WP_Publication_Archive {

	/**
	 * Automatically upgrade the plugin data store from one version to another.
	 *
	 * @param int $from
	 */
	public static function upgrade( $from ) {
		\WPPA\Plugin::instance()->upgrade()->run( $from );
	}

	/**
	 * Generate a link with a given endpoint.
	 *
	 * If no permalink is provided, it will be pulled back from WordPress.  In this case, the filter that auto-converts permalinks into open links will be removed and re-added.
	 *
	 * @param int         $publication_id Optional ID of the publication for which to generate a link.
	 * @param string      $endpoint       Optional endpoint name.
	 * @param bool|string $permalink      Optional existing permalink
	 * @param bool|string $key            Optional alternate download key
	 *
	 * @return string Download/Open link.
	 * @since 2.5
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
	 * @since 2.5
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
	 * @since 2.5
	 */
	public static function get_download_link( $publication_id = 0 ) {
		return \WPPA\Plugin::instance()->rewrites()->download_link( $publication_id );
	}

	/**
	 * Generate a link for a particular alternate file download.
	 *
	 * @param int         $publication_id Optional ID of the publication for which to retrieve a download link.
	 * @param string|bool $key            Optional key of the file to download
	 *
	 * @return string Download link.
	 * @since 3.0
	 */
	public static function get_alternate_open_link( $publication_id = 0, $key = false ) {
		return \WPPA\Plugin::instance()->rewrites()->alternate_open_link( $publication_id, false === $key ? null : $key );
	}

	/**
	 * Generate a link for a particular alternate file download.
	 *
	 * @param int         $publication_id Optional ID of the publication for which to retrieve a download link.
	 * @param string|bool $key            Optional key of the file to download
	 *
	 * @return string Download link.
	 * @since 3.0
	 */
	public static function get_alternate_download_link( $publication_id = 0, $key = false ) {
		return \WPPA\Plugin::instance()->rewrites()->alternate_download_link( $publication_id, false === $key ? null : $key );
	}

	/**
	 * Filter WordPress' request so that we can send a redirect to the file if it's requested.
	 *
	 * @uses  apply_filters() Calls 'wppa_open_url' to get the download URL.
	 * @uses  apply_filters() Calls 'wppa_mask_url' to check whether the file source URL should be masked.
	 *
	 * @since 2.5
	 */
	public static function open_file() {
		\WPPA\Plugin::instance()->delivery()->open();
	}

	public static function download_file() {
		\WPPA\Plugin::instance()->delivery()->download();
	}

	/**
	 * Get an image for the publication based on its MIME type.
	 *
	 * @uses apply_filters Calls 'wppa_publication_icon' to allow adding icons for unregistered MIME types.
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
	 * @uses wp_enqueue_script()
	 * @uses wp_enqueue_style()
	 */
	public static function enqueue_scripts_and_styles() {
		if ( is_admin() ) {
			\WPPA\Plugin::instance()->assets()->enqueue_admin();
		}
	}

	/**
	 * Register the Publication custom post type.
	 *
	 * @uses register_post_type()
	 */
	public static function register_publication() {
		\WPPA\Plugin::instance()->post_type()->register();
	}

	/**
	 * Register the publication author taxonomy.
	 *
	 * @uses register_taxonomy
	 * @todo Create a custom meta box to allow listing previously used authors rather than the freeform Tag box.
	 */
	public static function register_author() {
		\WPPA\Plugin::instance()->post_type()->register();
	}

	/**
	 * Register custom meta boxes for the Publication oage.
	 */
	public static function pub_meta_boxes() {
		\WPPA\Plugin::instance()->meta_boxes()->add();
	}

	/**
	 * Build the Publication link box
	 *
	 * @param WP_Post $post
	 */
	public static function doc_uri_box( $post ) {
		\WPPA\Plugin::instance()->meta_boxes()->render_doc( $post );
	}

	/**
	 * Build the Publication thumbnail image box.
	 *
	 * @param WP_Post $post
	 */
	public static function doc_thumb_box( $post ) {
		\WPPA\Plugin::instance()->meta_boxes()->render_thumb( $post );
	}

	/**
	 * Output a meta box with repeatable alternate upload fields
	 *
	 * @param WP_Post $post
	 */
	public static function doc_alternates_box( $post ) {
		\WPPA\Plugin::instance()->meta_boxes()->render_alternates( $post );
	}

	/**
	 * Save our changes to Publication meta information.
	 *
	 * @param int $post_id ID of the Publication we're updating
	 *
	 * @return int
	 */
	public static function save_meta( $post_id ) {
		return \WPPA\Plugin::instance()->meta_boxes()->save( $post_id );
	}

	/**
	 * Handle the 'wp-publication-archive' shortcode and provided filters.
	 *
	 * @param array $atts Shortcode arguments.
	 *
	 * @return string Shortcode output.
	 * @uses apply_filters() Calls 'wwpa_list_limit' to get the number of publications listed on each page.
	 * @uses apply_filters() Calls 'wppa_list_template' to get the shortcode template file.
	 * @uses apply_filters() Calls 'wppa_dropdown_template' to get the shortcode template file.
	 */
	public static function shortcode_handler( $atts ) {
		return \WPPA\Plugin::instance()->shortcode()->render( $atts );
	}

	/**
	 * Register new query variables.
	 *
	 * @param array $public_vars Query variables.
	 *
	 * @return array Query variables.
	 */
	public static function query_vars( $public_vars ) {
		return \WPPA\Plugin::instance()->rewrites()->query_vars( $public_vars );
	}

	/**
	 * Register our custom rewrite slugs and URLs.
	 */
	public static function custom_rewrites() {
		\WPPA\Plugin::instance()->rewrites()->register();
	}

	/**
	 * The post link for Publications is actually link to *open* the file, rather than to open the post page.  Filter
	 * out requests so we generate the correct link.
	 *
	 * @param string $permalink
	 * @param object $post
	 *
	 * @return string
	 */
	public static function publication_link( $permalink, $post ) {
		return \WPPA\Plugin::instance()->rewrites()->filter_post_type_link( $permalink, $post );
	}

	/**
	 * Filter the content of a Publication.
	 *
	 * Since Publications aren't using the regular post editor for their description, we need to hook in to calls
	 * to `the_content()` to filter out what's stored in the database and replace it with what's stored in the description
	 * meta field.
	 *
	 * We won't use the actual post content for Publications because, eventually, this will contain full-text references
	 * from the Publication itself to aid in full-text searching within WordPress.
	 *
	 * @param string $content Regular post content from the `wp_posts` table.
	 *
	 * @return string Actual summary description of the Publication, or unfiltered text if this isn't a Publication.
	 */
	public static function the_content( $content ) {
		global $post;
		if ( 'publication' != $post->post_type ) {
			return $content;
		}

		$pub = new WP_Publication_Archive_Item( $post );

		return $pub->summary;
	}

	/**
	 * Filter the title to append "(Download Publication)" where necessary.
	 *
	 * @param string $title Original title
	 * @param int    $id    Post ID
	 *
	 * @return string
	 */
	public static function the_title( $title, $id = 0 ) {
		// If the filter is called without passing in an ID, it's being called incorrectly.  Rather than spewing a PHP warning,
		// we will just exit out.  This code was added specifically to handle bad plugins like All-in-One Event Calendar.
		if ( 0 == $id ) {
			return $title;
		}

		$post = get_post( $id );
		if ( 'publication' != $post->post_type || is_admin() )
			return $title;

		return sprintf( __( '%s (Publication)', 'wp-publication-archive' ), $title );
	}

	/**
	 * Also check if the search term is contained in the publication's description.
	 *
	 * @param string $where Existing search query string.
	 *
	 * @uses  add_filter()
	 *
	 * @return string
	 *
	 * @since 2.5
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
	 *
	 * @since 2.5
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
	 * Utility function to return a WP_Query object with Publication posts
	 *
	 * @author Matthew Eppelsheimer
	 * @since  2.5
	 */
	public static function query_publications( $args ) {
		$defaults = array(
			'posts_per_page' => - 1,
			'order'          => 'ASC',
			'orderby'        => 'menu_order'
		);

		$query_args              = wp_parse_args( $args, $defaults );
		$query_args['post_type'] = 'publication';

		$results = new WP_Query( $query_args );

		return $results;
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
