<?php
/**
 * Plugin Name: WP Publication Archive
 * Plugin URI: http://jumping-duck.com/wordpress/plugins/wp-publication-archive/
 * Description: Allows users to upload, manage, search, and download publications, documents, and similar content (PDF, Power-Point, etc.).
 * Version: 3.0.1
 * Author: Eric Mann
 * Author URI: http://eamann.com
 * License: GPLv2
 */

/**
 * Copyright 2010-2013  Eric Mann, Jumping Duck Media
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

/**
 * This plug-in is a fork and continuation of the original wp-publications-archive by
 * Luis Lino, Siemens Networks, S.A. - http://code.google.com/p/wp-publications-archive/
 */

define( 'WP_PUB_ARCH_VERSION', '3' );
define( 'WP_PUB_ARCH_URL', plugin_dir_url( __FILE__ ) );
define( 'WP_PUB_ARCH_DIR', dirname( __FILE__ ) . '/' );

require_once( 'lib/class.mimetype.php' );
require_once( 'lib/class.wp-publication-archive-utilities.php' );
require_once( 'lib/class.wp-publication-archive.php' );
require_once( 'lib/class.publication-markup.php' );
require_once( 'lib/class.publication-widget.php' );
require_once( 'lib/class.wp-publication-archive-cat-count-widget.php' );
require_once( 'lib/class.wp-publication-archive-category-widget.php' );

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

/**
 * Default initialization routine for the plugin.
 * - Registers the default textdomain.
 * - Loads a rewrite endpoint for processing file downloads.
 */
function wp_pubarch_init() {
	load_plugin_textdomain( 'wp_pubarch_translate', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );

	WP_Publication_Archive::register_author();
	WP_Publication_Archive::register_publication();

	WP_Publication_Archive::custom_rewrites();
}

/**
 * Flush rewrite rules on plugin activation.
 */
function wp_pubarch_activate() {
	// First, load up the init scripts so we know which rewrites to add.
	wp_pubarch_init();

	flush_rewrite_rules();
}

register_activation_hook( __FILE__, 'wp_pubarch_activate' );

/**
 * Flush rewrite rules on plugin deactivation.
 */
function wp_pubarch_deactivate() {
	flush_rewrite_rules();
}

register_deactivation_hook( __FILE__, 'wp_pubarch_deactivate' );

// Check that allow_url_fopen is set to "on" in php.ini
function wp_pubarch_fopen_disabled() {
	echo '<div class="error"><p>';
	_e( 'Please set <code>allow_url_fopen</code> to "On" in your PHP.ini file, otherwise WP Publication Archive downloads <strong>WILL NOT WORK!</strong>', 'wp_pubarch_translate' );
	echo '<br /><a target="_blank" href="http://php.net/allow-url-fopen">' . __( 'More information ...', 'wp_pubarch_translate' ) . '</a>';
	echo '</p></div>';
}
if ( ! (bool) ini_get( 'allow_url_fopen' ) ) {
	add_action( 'admin_notices', 'wp_pubarch_fopen_disabled' );
}

// Wireup actions
add_action( 'init', 'wp_pubarch_init' );
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