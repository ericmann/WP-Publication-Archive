<?php
/**
 * PHPUnit bootstrap. Unit tests run on the bare host with no WordPress;
 * integration tests run inside wp-env's tests-cli container, where
 * WP_TESTS_DIR points at the WordPress core test library.
 */

require_once __DIR__ . '/../vendor/autoload.php';

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir && getenv( 'WP_PHPUNIT__TESTS_CONFIG' ) ) {
	$_tests_dir = getenv( 'WP_PHPUNIT__DIR' );
}

if ( ! $_tests_dir ) {
	return;
}

require_once $_tests_dir . '/includes/functions.php';

tests_add_filter(
	'muplugins_loaded',
	function () {
		require_once dirname( __DIR__ ) . '/wp-publication-archive.php';
	}
);

require $_tests_dir . '/includes/bootstrap.php';

// Each shim guards itself with class_exists( ..., false ); see the files.
require_once __DIR__ . '/class-wp-cli-exit-exception-shim.php';
require_once __DIR__ . '/wp-cli-utils-shim.php';
require_once __DIR__ . '/class-wp-cli-shim.php';
