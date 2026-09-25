<?php
/**
 * Implements SPEC.md §3 P5: stubs throw NotImplementedException( __METHOD__ ).
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA\Tests;

use WPPA\NotImplementedException;

class Test_Not_Implemented_Exception extends \PHPUnit\Framework\TestCase {

	public function test_message_is_the_method_name() {
		try {
			throw new NotImplementedException( __METHOD__ );
		} catch ( NotImplementedException $e ) {
			$this->assertSame( __METHOD__, $e->getMessage() );

			return;
		}

		$this->fail( 'Expected NotImplementedException to be thrown.' );
	}
}
