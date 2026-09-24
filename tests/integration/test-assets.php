<?php
/**
 * Implements SPEC.md §6.7: the 3.0.1 front-end stylesheet is registered
 * under its original handle on every request and enqueued only when the
 * plugin is enabled. D13 (P2-07): admin media enqueue replaces Thickbox.
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
		wp_dequeue_script( Keys::ADMIN_SCRIPT_HANDLE );
		wp_deregister_script( Keys::ADMIN_SCRIPT_HANDLE );
		set_current_screen( 'front' );

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
	 * D13: the admin media script is limited to the publication edit
	 * screens (Keys::ADMIN_SCREENS) and to a screen whose post type is this
	 * plugin's; it never enqueues on any other admin page.
	 */
	public function test_admin_enqueue_is_limited_to_publication_screens() {
		set_current_screen( Keys::POST_TYPE );
		$this->assets()->enqueue_admin( 'post.php' );
		$this->assertTrue( wp_script_is( Keys::ADMIN_SCRIPT_HANDLE, 'enqueued' ) );
		wp_dequeue_script( Keys::ADMIN_SCRIPT_HANDLE );

		set_current_screen( 'edit-post' );
		$this->assets()->enqueue_admin( 'edit.php' );
		$this->assertFalse( wp_script_is( Keys::ADMIN_SCRIPT_HANDLE, 'enqueued' ) );

		set_current_screen( 'post' );
		$this->assets()->enqueue_admin( 'post.php' );
		$this->assertFalse( wp_script_is( Keys::ADMIN_SCRIPT_HANDLE, 'enqueued' ) );
	}

	public function test_base_css_contains_the_301_rules() {
		$path = WP_PUB_ARCH_DIR . Keys::STYLE_PATH;
		// phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown -- reason: local source file, not remote data.
		$contents = file_get_contents( $path );

		$this->assertNotFalse( $contents );
		$this->assertStringContainsString( '.publication_title', $contents );
	}
}
