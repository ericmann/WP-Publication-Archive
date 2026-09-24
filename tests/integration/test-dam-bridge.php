<?php
/**
 * Implements SPEC.md §6.9: the bridge to the VIP Digital Asset Manager
 * (D17–D19).
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA\Tests;

use WPPA\Dam_Bridge;

class Test_Dam_Bridge extends \WP_UnitTestCase {

	public function test_is_final() {
		$reflection = new \ReflectionClass( Dam_Bridge::class );

		$this->assertTrue( $reflection->isFinal() );
	}
}
