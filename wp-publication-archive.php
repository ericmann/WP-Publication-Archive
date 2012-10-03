<?php
/**
 * Plugin Name: WP Publication Archive
 * Plugin URI: http://jumping-duck.com/wordpress/plugins/wp-publication-archive/
 * Description: Allows users to upload, manage, search, and download publications, documents, and similar content (PDF, Power-Point, etc.).
 * Version: 2.5
 * Author: Eric Mann
 * Author URI: http://eamann.com
 * License: GPLv2
 */

/**
 * Copyright 2010-2012  Eric Mann, Jumping Duck Media
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

define( 'WP_PUB_ARCH_INC_URL', plugin_dir_url( __FILE__ ) . 'includes' );
define( 'WP_PUB_ARCH_IMG_URL', plugin_dir_url( __FILE__ ) . 'images' );
define( 'WP_PUB_ARCH_LIB_URL', plugin_dir_url( __FILE__ ) . 'lib' );

require_once( 'lib/class.mimetype.php' );
require_once( 'lib/class.wp-publication-archive.php' );
require_once( 'lib/class.publication-markup.php' );

update_option( 'wp-publication-archive-core', 2, '', 'no' );

/**
 * Default initialization routine for the plugin.
 * - Registers the default textdomain.
 * - Loads a rewrite endpoint for processing file downloads.
 */
function wppa_init() {
	load_plugin_textdomain( 'wppa_translate', false, dirname( dirname( plugin_basename( __FILE__) ) ) . '/lang/' );

	WP_Publication_Archive::register_author();
	WP_Publication_Archive::register_publication();

	add_rewrite_endpoint( 'wppa_download', EP_ALL );
}

/**
 * Flush rewrite rules on plugin activation.
 */
function wppa_activate() {
	// First, load up the init scripts so we know which rewrites to add.
	wppa_init();

	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'wppa_activate' );

if ( ! function_exists( 'remove_rewrite_endpoint' ) ) :
	/**
	 * This function will remove a rewrite endpoint from WordPress by name.
	 * It is included in an if(!function_exists()) block so that a later version of WordPress can replace it in the future.
	 *
	 * @param string $name Endpoint to be removed.
	 * @since 2.5
	 * @see add_rewrite_endpoint()
	 */
	function remove_rewrite_endpoint( $name ) {
		global $wp_rewrite;

		for ( $i = 0; $i < count( $wp_rewrite->endpoints ); $i++ ) {
			$endpoint = $wp_rewrite->endpoints[$i];
			if ( $endpoint[1] == $name ) {
				unset( $wp_rewrite->endpoints[$i] );
				break;
			}
		}
	}
endif;

/**
 * Flush rewrite rules on plugin deactivation.
 */
function wppa_deactivate() {
	remove_rewrite_endpoint( 'wppa_download' );

	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'wppa_deactivate' );

// Wireup actions
add_action( 'init',              'wppa_init' );
add_action( 'init',              array( 'WP_Publication_Archive', 'enqueue_scripts_and_styles' ) );
add_action( 'save_post',         array( 'WP_Publication_Archive', 'save_meta' ) );
add_action( 'template_redirect', array( 'WP_Publication_Archive', 'download_file' ) );

// Wireup filters
//add_filter( 'post_type_link', array( 'WP_Publication_Archive', 'publication_link' ), 10, 2 );
add_filter( 'query_vars',     array( 'WP_Publication_Archive', 'query_vars' ) );
add_filter( 'the_content',    array( 'WP_Publication_Archive', 'the_content' ) );
//add_filter( 'the_title',      array( 'WP_Publication_Archive', 'the_title' ), 10, 2 );

// Wireup shortcodes
add_shortcode( 'wp-publication-archive', array( 'WP_Publication_Archive', 'shortcode_handler' ) );
?>