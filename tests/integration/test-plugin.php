<?php
/**
 * Implements SPEC.md §4: Plugin boots once, registers its hooks, loads the
 * 3.0.1 runtime, and lets tests swap services.
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA\Tests\Integration;

use WPPA\FixedClock;
use WPPA\Flags;
use WPPA\Keys;
use WPPA\Plugin;

class Test_Plugin extends \WP_UnitTestCase {

	public function test_boot_is_idempotent() {
		$first = Plugin::instance();

		Plugin::boot();

		$this->assertSame( $first, Plugin::instance() );
	}

	public function test_unregister_and_register_round_trip() {
		$plugin = Plugin::instance();

		$plugin->unregister_hooks();
		$this->assertFalse( has_action( Keys::HOOK_WP_ENQUEUE_SCRIPTS, array( $plugin->assets(), 'register' ) ) );

		$plugin->register_hooks();
		$this->assertSame( 10, has_action( Keys::HOOK_WP_ENQUEUE_SCRIPTS, array( $plugin->assets(), 'register' ) ) );
	}

	public function test_replace_swaps_a_service_and_rejects_wrong_types() {
		$plugin   = Plugin::instance();
		$original = $plugin->flags();
		$flags    = new Flags( new FixedClock( 1 ) );

		$plugin->replace( 'flags', $flags );
		$this->assertSame( $flags, $plugin->flags() );

		$plugin->replace( 'flags', $original );

		$this->expectException( \TypeError::class );
		$plugin->replace( 'flags', new \stdClass() );
	}

	public function test_replace_rejects_unknown_service() {
		$this->expectException( \InvalidArgumentException::class );

		Plugin::instance()->replace( 'nope', new \stdClass() );
	}

	public function test_booted_action_fired() {
		$this->assertGreaterThanOrEqual( 1, did_action( Keys::ACTION_BOOTED ) );
	}

	public function test_boot_loads_the_301_runtime() {
		$this->assertTrue( class_exists( 'WP_Publication_Archive', false ) );
		$this->assertTrue( post_type_exists( 'publication' ) );
	}
}
