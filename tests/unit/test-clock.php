<?php
/**
 * Implements SPEC.md §3 P8: Clock is the only place time is read or formatted.
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA\Tests;

use WPPA\FixedClock;
use WPPA\SystemClock;

class Test_Clock extends \PHPUnit\Framework\TestCase {

	public function test_system_clock_now_is_current() {
		$clock = new SystemClock();

		$this->assertEqualsWithDelta( time(), $clock->now(), 2 );
	}

	public function test_fixed_clock_returns_its_time() {
		$clock = new FixedClock( 12345 );

		$this->assertSame( 12345, $clock->now() );
	}

	public function test_fixed_clock_formats_the_epoch() {
		$clock = new FixedClock( 437184000 );

		$this->assertSame( '1983-11-09', $clock->format( 'Y-m-d', 437184000 ) );
	}
}
