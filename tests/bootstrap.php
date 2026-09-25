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
	// Each shim guards itself with class_exists( ..., false ); see the file.
	require_once __DIR__ . '/class-wp-error-shim.php';

	return;
}

require_once $_tests_dir . '/includes/functions.php';

tests_add_filter(
	'muplugins_loaded',
	function () {
		if ( '1' === getenv( 'WPPA_TEST_DAM' ) ) {
			$dam_file = WP_PLUGIN_DIR . '/vip-digital-asset-manager/index.php';

			if ( ! file_exists( $dam_file ) ) {
				throw new \RuntimeException( 'WPPA_TEST_DAM=1 but ' . $dam_file . ' is missing. Run bash bin/fetch-dam.sh.' );
			}

			require_once $dam_file;
		}

		require_once dirname( __DIR__ ) . '/wp-publication-archive.php';
	}
);

require $_tests_dir . '/includes/bootstrap.php';

// Each shim guards itself with class_exists( ..., false ); see the files.
require_once __DIR__ . '/class-wp-cli-exit-exception-shim.php';
require_once __DIR__ . '/wp-cli-utils-shim.php';
require_once __DIR__ . '/class-wp-cli-shim.php';
