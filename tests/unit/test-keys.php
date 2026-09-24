<?php
/**
 * Implements SPEC.md §5: Keys is the single declaration of every name.
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA\Tests;

use WPPA\Keys;

class Test_Keys extends \PHPUnit\Framework\TestCase {

	public function test_identity() {
		$this->assertSame( 'wp-publication-archive', Keys::SLUG );
		$this->assertSame( 'wp-publication-archive', Keys::TEXT_DOMAIN );
		$this->assertSame( 'wppa', Keys::PREFIX );
		$this->assertSame( 'publication-archive', Keys::CLI_COMMAND );
		$this->assertSame( 'wp-publication-archive', Keys::CACHE_GROUP );
		$this->assertSame( 'publication', Keys::POST_TYPE );
		$this->assertSame( 'wp-publication-archive', Keys::SHORTCODE );
	}

	public function test_versions() {
		$this->assertSame( '7.4', Keys::MIN_PHP );
		$this->assertSame( '6.7', Keys::MIN_WP );
		$this->assertSame( '3.1.0-dev', Keys::VERSION );
	}

	public function test_lineage_epoch_is_the_ninth_of_november_1983() {
		$this->assertSame( 437184000, Keys::EPOCH );
		$this->assertSame( '1983-11-09', Keys::EPOCH_DATE );
		$this->assertSame( '19831109', Keys::ASSET_VERSION );
		$this->assertSame( 'eamann/plugin-template by Eric A. Mann (EAM)', Keys::LINEAGE );
	}

	public function test_plugin_header_matches_keys() {
		$path = dirname( __DIR__, 2 ) . '/wp-publication-archive.php';
		// phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown -- reason: local source file, not remote data.
		$contents = file_get_contents( $path );

		$this->assertNotFalse( $contents );
		$this->assertMatchesRegularExpression( '/^ \* Version: 3\.1\.0-dev$/m', $contents );
		$this->assertMatchesRegularExpression( '/^ \* Requires PHP: 7\.4$/m', $contents );
		$this->assertMatchesRegularExpression( '/^ \* Requires at least: 6\.7$/m', $contents );
		$this->assertMatchesRegularExpression( '/^ \* Text Domain: wp-publication-archive$/m', $contents );
		$this->assertMatchesRegularExpression( '/^ \* Domain Path: \/languages$/m', $contents );
	}

	public function test_wp_env_conf_uses_the_slug() {
		$path = dirname( __DIR__, 2 ) . '/bin/wp-env.conf';
		// phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown -- reason: local source file, not remote data.
		$contents = file_get_contents( $path );

		$this->assertNotFalse( $contents );
		$this->assertStringContainsString( 'PLUGIN_SLUG="wp-publication-archive"', $contents );
	}

	public function test_keys_is_final_and_has_no_methods() {
		$reflection = new \ReflectionClass( Keys::class );

		$this->assertTrue( $reflection->isFinal() );
		$this->assertSame( array(), $reflection->getMethods() );
	}
}
