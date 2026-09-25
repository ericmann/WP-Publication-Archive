<?php
/**
 * Implements SPEC.md §6.4: each Hooks method fires exactly its constant.
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA\Tests;

use WPPA\Hooks;
use WPPA\Keys;
use WPPA\Plugin;

class Test_Hooks extends \WP_UnitTestCase {

	public function tear_down() {
		remove_all_filters( Keys::FILTER_ENABLED );
		remove_all_actions( Keys::ACTION_BOOTED );

		parent::tear_down();
	}

	public function test_filter_enabled_passes_value_and_context() {
		$seen = array();

		add_filter(
			Keys::FILTER_ENABLED,
			static function ( $enabled, $context ) use ( &$seen ) {
				$seen = array( $enabled, $context );

				return ! $enabled;
			},
			10,
			2
		);

		$this->assertTrue( Hooks::filter_enabled( false, 'ctx' ) );
		$this->assertSame( array( false, 'ctx' ), $seen );
	}

	public function test_booted_passes_the_plugin() {
		$seen = null;

		add_action(
			Keys::ACTION_BOOTED,
			static function ( $plugin ) use ( &$seen ) {
				$seen = $plugin;
			}
		);

		Hooks::booted( Plugin::instance() );

		$this->assertSame( Plugin::instance(), $seen );
	}
}
