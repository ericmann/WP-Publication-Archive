<?php
/**
 * Transitional loader for the 3.0.1 runtime. Requires the seven 3.0.1 lib
 * files and wires the hooks WPPA services do not yet own. Post type,
 * taxonomy, rewrites, query vars, the schema upgrade and the allow_url_fopen
 * admin notice moved to WPPA\Post_Type, WPPA\Rewrites, WPPA\Upgrade and
 * WPPA\Plugin in P0-08.
 *
 * `lib/` is transitional and is deleted by P0-15.
 */

class WP_Publication_Archive_Loader {

	/**
	 * @var bool
	 */
	protected static $loaded = false;

	/**
	 * Require the 3.0.1 runtime and wire its remaining hooks. Idempotent.
	 */
	public static function load() {
		if ( self::$loaded ) {
			return;
		}
		self::$loaded = true;

		require_once WP_PUB_ARCH_DIR . 'lib/class.mimetype.php';
		require_once WP_PUB_ARCH_DIR . 'lib/class.wp-publication-archive-utilities.php';
		require_once WP_PUB_ARCH_DIR . 'lib/class.wp-publication-archive.php';
		require_once WP_PUB_ARCH_DIR . 'lib/class.publication-markup.php';
		require_once WP_PUB_ARCH_DIR . 'lib/class.publication-widget.php';
		require_once WP_PUB_ARCH_DIR . 'lib/class.wp-publication-archive-cat-count-widget.php';
		require_once WP_PUB_ARCH_DIR . 'lib/class.wp-publication-archive-category-widget.php';

		// Wireup actions
		add_action( 'init', array( 'WP_Publication_Archive', 'enqueue_scripts_and_styles' ) );
		add_action( 'save_post', array( 'WP_Publication_Archive', 'save_meta' ) );
		add_action( 'template_redirect', array( 'WP_Publication_Archive', 'open_file' ) );
		add_action( 'template_redirect', array( 'WP_Publication_Archive', 'download_file' ) );

		// Wireup filters
		add_filter( 'excerpt_length', array( 'WP_Publication_Archive', 'custom_excerpt_length' ) );

		// Wireup shortcodes
		add_shortcode( 'wp-publication-archive', array( 'WP_Publication_Archive', 'shortcode_handler' ) );
	}
}
