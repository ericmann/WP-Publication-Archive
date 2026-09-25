<?php
/**
 * PHPStan-only bootstrap. Defines the legacy constants the bootstrap file
 * sets at runtime so analysis of code that reads them does not fail.
 */

if ( ! defined( 'WP_PUB_ARCH_VERSION' ) ) {
	define( 'WP_PUB_ARCH_VERSION', '3.1.0-dev' );
}

if ( ! defined( 'WP_PUB_ARCH_URL' ) ) {
	define( 'WP_PUB_ARCH_URL', 'https://example.com/wp-content/plugins/wp-publication-archive/' );
}

if ( ! defined( 'WP_PUB_ARCH_DIR' ) ) {
	define( 'WP_PUB_ARCH_DIR', __DIR__ . '/../' );
}
