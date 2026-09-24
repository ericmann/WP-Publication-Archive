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
}
