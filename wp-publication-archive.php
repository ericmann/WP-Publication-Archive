<?php
/*
Plugin Name: WP Publication Archive
Plugin URI: http://jumping-duck.com/wordpress
Description: Allows users to upload, manage, search, and download publications, documents, and similar content (PDF, Power-Point, etc.).
Version: 2.3.4
Author: Eric Mann
Author URI: http://eamann.com
License: GPLv2
*/

/*  Copyright 2010-2011  Eric Mann, Jumping Duck Media
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as 
 * published by the Free Software Foundation.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

/*	This plug-in is a fork and continuation of the original wp-publications-archive by
 *	Luis Lino, Siemens Networks, S.A. - http://code.google.com/p/wp-publications-archive/
 */

if ( ! defined('WP_PUB_ARCH_INC_URL') )
	define( 'WP_PUB_ARCH_INC_URL', WP_PLUGIN_URL . '/wp-publication-archive/includes' );
if ( ! defined('WP_PUB_ARCH_IMG_URL') )
	define( 'WP_PUB_ARCH_IMG_URL', WP_PLUGIN_URL . '/wp-publication-archive/images' );
if ( ! defined('WP_PUB_ARCH_LIB_URL') )
	define( 'WP_PUB_ARCH_LIB_URL', WP_PLUGIN_URL . '/wp-publication-archive/lib' );

update_option( 'wp-publication-archive-core', 2, '', 'no' );

require_once( 'lib/class.wp-publication-archive.php' );
require_once( 'lib/class.publication-markup.php' );

add_action( 'init',             array( 'WP_Publication_Archive', 'register_publication' ) );
add_action( 'init',             array( 'WP_Publication_Archive', 'register_author' ) );
add_action( 'init',             array( 'WP_Publication_Archive', 'enqueue_scripts_and_styles' ) );
add_action( 'save_post',        array( 'WP_Publication_Archive', 'save_meta' ) );

add_filter( 'post_type_link',   array( 'WP_Publication_Archive', 'publication_link' ), 10, 2 );
add_filter( 'query_vars',       array( 'WP_Publication_Archive', 'query_vars' ) );
add_filter( 'the_content',      array( 'WP_Publication_Archive', 'the_content' ) );
add_filter( 'the_title',        array( 'WP_Publication_Archive', 'the_title' ), 10, 2 );

add_shortcode( 'wp-publication-archive', array( 'WP_Publication_Archive', 'shortcode_handler' ) );
?>