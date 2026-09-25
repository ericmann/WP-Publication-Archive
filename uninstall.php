<?php
/**
 * Implements SPEC.md §4.1: uninstall deletes the three §5.2 options this
 * plugin ever writes, and nothing else — never post data, meta, terms or
 * roles.
 *
 * @author Eric Mann <eric@eamann.com>
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

require_once __DIR__ . '/vendor/autoload.php';

( new \WPPA\Flags( new \WPPA\SystemClock() ) )->delete_all();
