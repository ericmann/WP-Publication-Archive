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

require_once('includes/class.wp-publication-archive.php');
$wpa = new WP_Publication_Archive();

function wp_publication_archive() {
	return $wpa->shortcode_handler();
}