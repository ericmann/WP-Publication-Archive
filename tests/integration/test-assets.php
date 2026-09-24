<?php
/**
 * Implements SPEC.md §6.7: the 3.0.1 front-end stylesheet is registered
 * under its original handle on every request and enqueued only when the
 * plugin is enabled.
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA\Tests;

use WPPA\Assets;
use WPPA\FixedClock;
use WPPA\Flags;
use WPPA\Keys;

class Test_Assets extends \WP_UnitTestCase {

	public function tear_down() {
		wp_dequeue_style( Keys::STYLE_HANDLE );
		wp_deregister_style( Keys::STYLE_HANDLE );
		delete_option( Keys::OPT_ENABLED );
		wp_dequeue_script( 'media-upload' );
		wp_dequeue_script( 'thickbox' );
		wp_dequeue_style( 'thickbox' );

		parent::tear_down();
	}

	private function assets() {
		return new Assets( new Flags( new FixedClock( Keys::EPOCH ) ) );
	}

	public function test_front_style_registered_under_301_handle_with_asset_version() {
		$this->assets()->register();

		$registered = wp_styles()->registered[ Keys::STYLE_HANDLE ] ?? null;

		$this->assertNotNull( $registered );
		$this->assertSame( Keys::ASSET_VERSION, $registered->ver );
		$this->assertStringEndsWith( Keys::STYLE_PATH, (string) $registered->src );
	}

	public function test_front_style_enqueued_by_default() {
		$assets = $this->assets();
		$assets->register();
		$assets->enqueue_front();

		$this->assertTrue( wp_style_is( Keys::STYLE_HANDLE, 'enqueued' ) );
	}

	public function test_front_style_not_enqueued_when_disabled() {
		update_option( Keys::OPT_ENABLED, 0 );

		$assets = $this->assets();
		$assets->register();
		$assets->enqueue_front();

		$this->assertFalse( wp_style_is( Keys::STYLE_HANDLE, 'enqueued' ) );
	}

	/**
	 * D13, until P2-07: 3.0.1 enqueued Thickbox unconditionally on every
	 * admin page.
	 */
	public function test_admin_enqueues_thickbox_until_d13() {
		$this->assets()->enqueue_admin();

		$this->assertTrue( wp_script_is( 'media-upload', 'enqueued' ) );
		$this->assertTrue( wp_script_is( 'thickbox', 'enqueued' ) );
		$this->assertTrue( wp_style_is( 'thickbox', 'enqueued' ) );
	}

	public function test_base_css_contains_the_301_rules() {
		$path = WP_PUB_ARCH_DIR . Keys::STYLE_PATH;
		// phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown -- reason: local source file, not remote data.
		$contents = file_get_contents( $path );

		$this->assertNotFalse( $contents );
		$this->assertStringContainsString( '.publication_title', $contents );
	}
}
