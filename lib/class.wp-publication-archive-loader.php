<?php
/**
 * Transitional loader for the 3.0.1 runtime. Requires the six remaining
 * 3.0.1 lib files and wires the hooks WPPA services do not yet own. Post
 * type, taxonomy, rewrites, query vars, the schema upgrade and the
 * allow_url_fopen admin notice moved to WPPA\Post_Type, WPPA\Rewrites,
 * WPPA\Upgrade and WPPA\Plugin in P0-08; the view/download endpoints and
 * icon/MIME lookup moved to WPPA\Delivery and WPPA\Icons in P0-09; the meta
 * boxes, save_meta and the admin (Thickbox) enqueue moved to
 * WPPA\Meta_Boxes and WPPA\Assets in P0-10; WP_Publication_Archive_Item
 * (class.publication-markup.php, deleted) moved to WPPA\Publication_Item,
 * aliased back by WPPA\Legacy\Aliases in P0-11; the shortcode, template
 * location and excerpt_length moved to WPPA\Shortcode and WPPA\Templates
 * in P0-12; the category helpers (class.wp-publication-archive-utilities.php,
 * deleted) moved to WPPA\Categories/WPPA\Legacy\Utilities, and the
 * category-count widget (class.wp-publication-archive-cat-count-widget.php,
 * deleted) moved to WPPA\Widgets\Category_Count_Widget, in P0-13.
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

		require_once WP_PUB_ARCH_DIR . 'lib/class.wp-publication-archive.php';
		require_once WP_PUB_ARCH_DIR . 'lib/class.publication-widget.php';
		require_once WP_PUB_ARCH_DIR . 'lib/class.wp-publication-archive-category-widget.php';
	}
}
