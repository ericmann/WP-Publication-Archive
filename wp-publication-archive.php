<?php
/**
 * Plugin Name: WP Publication Archive
 * Plugin URI: https://github.com/ericmann/WP-Publication-Archive
 * Description: Manage, list, search and deliver publications (PDF, Office documents and other files) as a custom post type.
 * Version: 3.1.0-dev
 * Requires at least: 6.7
 * Requires PHP: 7.4
 * Author: Eric Mann
 * Author URI: https://eamann.com
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-publication-archive
 * Domain Path: /languages
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

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

require_once __DIR__ . '/vendor/autoload.php';

if (
	version_compare( phpversion(), \WPPA\Keys::MIN_PHP, '<' )
	|| version_compare( (string) ( $GLOBALS['wp_version'] ?? '0' ), \WPPA\Keys::MIN_WP, '<' )
) {
	return;
}

define( 'WP_PUB_ARCH_VERSION', \WPPA\Keys::VERSION );
define( 'WP_PUB_ARCH_URL', plugin_dir_url( __FILE__ ) );
define( 'WP_PUB_ARCH_DIR', plugin_dir_path( __FILE__ ) );

register_activation_hook( __FILE__, array( \WPPA\Plugin::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( \WPPA\Plugin::class, 'deactivate' ) );

\WPPA\Plugin::boot();
