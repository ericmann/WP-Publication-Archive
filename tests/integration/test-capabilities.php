<?php
/**
 * Implements SPEC.md §6.1: publication capabilities, granted once to the
 * three §6.1 roles.
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA\Tests;

use WPPA\Capabilities;

class Test_Capabilities extends \WP_UnitTestCase {

	public function test_is_final() {
		$reflection = new \ReflectionClass( Capabilities::class );

		$this->assertTrue( $reflection->isFinal() );
	}
}
