<?php
/**
 * Implements SPEC.md §6.2: Delivery::decide(), the pure redirect-or-proxy
 * decision, tested with no WordPress loaded.
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA\Tests\Unit;

use WPPA\Delivery;

class Test_Delivery extends \PHPUnit\Framework\TestCase {

	public function test_decide_redirects_when_mask_is_off() {
		$this->assertSame( 'redirect', Delivery::decide( false, null, 100 ) );
		$this->assertSame( 'redirect', Delivery::decide( false, 10, 100 ) );
		$this->assertSame( 'redirect', Delivery::decide( false, 1000, 100 ) );
	}

	public function test_decide_redirects_when_length_exceeds_cap() {
		$this->assertSame( 'redirect', Delivery::decide( true, 101, 100 ) );
	}

	public function test_decide_proxies_when_under_cap_or_length_unknown() {
		$this->assertSame( 'proxy', Delivery::decide( true, 100, 100 ) );
		$this->assertSame( 'proxy', Delivery::decide( true, 50, 100 ) );
		$this->assertSame( 'proxy', Delivery::decide( true, null, 100 ) );
	}
}
