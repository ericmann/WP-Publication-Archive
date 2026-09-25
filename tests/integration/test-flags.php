<?php
/**
 * Implements SPEC.md §5.2: Flags reads the option and applies the filter.
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA\Tests;

use WPPA\FixedClock;
use WPPA\Flags;
use WPPA\Keys;

class Test_Flags extends \WP_UnitTestCase {

	public function tear_down() {
		delete_option( Keys::OPT_ENABLED );
		remove_all_filters( Keys::FILTER_ENABLED );

		parent::tear_down();
	}

	public function test_enabled_defaults_to_true_when_option_absent() {
		$this->assertTrue( ( new Flags( new FixedClock( 1 ) ) )->enabled() );
	}

	public function test_enabled_reads_the_option() {
		update_option( Keys::OPT_ENABLED, 0 );

		$this->assertFalse( ( new Flags( new FixedClock( 1 ) ) )->enabled() );
	}

	public function test_enabled_is_filterable_with_context() {
		add_filter(
			Keys::FILTER_ENABLED,
			static function ( $enabled, $context ) {
				return 'canary' === $context;
			},
			10,
			2
		);

		$flags = new Flags( new FixedClock( 1 ) );

		$this->assertFalse( $flags->enabled() );
		$this->assertTrue( $flags->enabled( 'canary' ) );
	}

	public function test_delete_all_removes_the_three_options() {
		update_option( Keys::OPT_SCHEMA, Keys::SCHEMA_VERSION );
		update_option( Keys::OPT_CAPS, 1 );
		update_option( Keys::OPT_ENABLED, 0 );

		( new Flags( new FixedClock( 1 ) ) )->delete_all();

		$this->assertFalse( get_option( Keys::OPT_SCHEMA ) );
		$this->assertFalse( get_option( Keys::OPT_CAPS ) );
		$this->assertFalse( get_option( Keys::OPT_ENABLED ) );
	}

	public function test_delete_all_leaves_publications_and_meta() {
		$id = self::factory()->post->create( array( 'post_type' => Keys::POST_TYPE ) );
		\WPPA\Tests\Fixtures\V3_Site::raw_meta( $id, Keys::META_DOC, 'https://example.com/a.pdf' );

		( new Flags( new FixedClock( 1 ) ) )->delete_all();

		$this->assertSame( Keys::POST_TYPE, get_post_type( $id ) );
		$this->assertSame( 'https://example.com/a.pdf', get_post_meta( $id, Keys::META_DOC, true ) );
	}

	public function test_uninstall_file_guards_and_calls_delete_all() {
		$path = dirname( __DIR__, 2 ) . '/uninstall.php';
		// phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown -- reason: local source file, not remote data.
		$contents = file_get_contents( $path );

		$this->assertNotFalse( $contents );
		$this->assertStringContainsString( "if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) )", $contents );
		$this->assertStringContainsString( '->delete_all();', $contents );
	}
}
