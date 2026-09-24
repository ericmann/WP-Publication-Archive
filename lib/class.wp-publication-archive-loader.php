<?php
/**
 * Transitional loader for the 3.0.1 runtime. Requires the seven 3.0.1 lib
 * files, runs the 3.0.1 schema block and hook wiring verbatim, and exposes
 * the four wp_pubarch_* functions as static methods so the bootstrap file
 * can register them without declaring file-scope functions.
 *
 * `lib/` is transitional and is deleted by P0-15.
 */

class WP_Publication_Archive_Loader {

	/**
	 * @var bool
	 */
	protected static $loaded = false;

	/**
	 * Require the 3.0.1 runtime, run its schema upgrade block, and wire its
	 * hooks. Idempotent.
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

		$installed = get_option( 'wp-publication-archive-core' );
		if ( false === $installed || (int) $installed < 3 ) {
			// This is an old installation, so upgrate it
			WP_Publication_Archive::upgrade( $installed );

			update_option( 'wp-publication-archive-core', 3 );

			// Update rewrite structures
			flush_rewrite_rules();
		} else {
			// This is a new installation, don't upgrade anything
			add_option( 'wp-publication-archive-core', 3, '', 'no' );
		}

		// Check that allow_url_fopen is set to "on" in php.ini
		if ( ! (bool) ini_get( 'allow_url_fopen' ) ) {
			add_action( 'admin_notices', array( __CLASS__, 'fopen_disabled' ) );
		}

		// Wireup actions
		add_action( 'init', array( __CLASS__, 'init' ) );
		add_action( 'init', array( 'WP_Publication_Archive', 'enqueue_scripts_and_styles' ) );
		add_action( 'save_post', array( 'WP_Publication_Archive', 'save_meta' ) );
		add_action( 'template_redirect', array( 'WP_Publication_Archive', 'open_file' ) );
		add_action( 'template_redirect', array( 'WP_Publication_Archive', 'download_file' ) );

		// Wireup filters
		add_filter( 'query_vars', array( 'WP_Publication_Archive', 'query_vars' ) );
		add_filter( 'posts_where_request', array( 'WP_Publication_Archive', 'search' ) );
		add_filter( 'excerpt_length', array( 'WP_Publication_Archive', 'custom_excerpt_length' ) );

		// Wireup shortcodes
		add_shortcode( 'wp-publication-archive', array( 'WP_Publication_Archive', 'shortcode_handler' ) );
	}

	/**
	 * Default initialization routine for the plugin.
	 * - Registers the default textdomain.
	 * - Loads a rewrite endpoint for processing file downloads.
	 */
	public static function init() {
		load_plugin_textdomain( 'wp_pubarch_translate', false, dirname( plugin_basename( WP_PUB_ARCH_DIR . 'wp-publication-archive.php' ) ) . '/lang/' );

		WP_Publication_Archive::register_author();
		WP_Publication_Archive::register_publication();

		WP_Publication_Archive::custom_rewrites();
	}

	/**
	 * Flush rewrite rules on plugin activation.
	 */
	public static function activate() {
		// First, load up the init scripts so we know which rewrites to add.
		self::init();

		flush_rewrite_rules();
	}

	/**
	 * Flush rewrite rules on plugin deactivation.
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}

	// Check that allow_url_fopen is set to "on" in php.ini
	public static function fopen_disabled() {
		echo '<div class="error"><p>';
		_e( 'Please set <code>allow_url_fopen</code> to "On" in your PHP.ini file, otherwise WP Publication Archive downloads <strong>WILL NOT WORK!</strong>', 'wp_pubarch_translate' );
		echo '<br /><a target="_blank" href="http://php.net/allow-url-fopen">' . __( 'More information ...', 'wp_pubarch_translate' ) . '</a>';
		echo '</p></div>';
	}
}
