<?php
/*
Plugin Name: WP Publication Archive
Plugin URI: http://jumping-duck.com/wordpress
Description: Allows users to upload, manage, search, and download publications, documents, and similar content (PDF, Power-Point, etc.).
Version: 2.1.1
Author: Eric Mann
Author URI: http://eamann.com
License: GPLv3
*/

/*  Copyright 2010  Eric Mann  (email : eric@eamann.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; version 3.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/*	This plug-in is a fork and continuation of the original wp-publications-archive by
	Luis Lino, Siemens Networks, S.A. - http://code.google.com/p/wp-publications-archive/
*/

if ( ! defined('WP_PUB_ARCH_INC_URL') )
	define( 'WP_PUB_ARCH_INC_URL', WP_PLUGIN_URL . '/wp-publication-archive/includes' );
if ( ! defined('WP_PUB_ARCH_IMG_URL') )
	define( 'WP_PUB_ARCH_IMG_URL', WP_PLUGIN_URL . '/wp-publication-archive/images' );
if ( ! defined('WP_PUB_ARCH_LIB_URL') )
	define( 'WP_PUB_ARCH_LIB_URL', WP_PLUGIN_URL . '/wp-publication-archive/lib' );

require_once('lib/class.wp-publication-archive.php');

add_action( 'init',         array( 'WP_Publication_Archive', 'register_publication' ) );
add_action( 'init',         array( 'WP_Publication_Archive', 'register_author' ) );
add_action( 'init',         array( 'WP_Publication_Archive', 'enqueue_scripts_and_styles' ) );
add_action( 'save_post',    array( 'WP_Publication_Archive', 'save_meta' ) );

add_shortcode( 'wp-publication-archive', array( 'WP_Publication_Archive', 'shortcode_handler' ) );
?>