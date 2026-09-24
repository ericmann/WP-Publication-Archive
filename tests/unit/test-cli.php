<?php
/**
 * Implements SPEC.md §6: every public method on Cli is a WP-CLI subcommand
 * (P13a), so the public surface must be exactly the documented command set.
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA\Tests\Unit;

use WPPA\Cli;

class Test_Cli extends \PHPUnit\Framework\TestCase {

	public function test_doctor_is_the_only_public_subcommand() {
		$methods = array_map(
			static function ( \ReflectionMethod $m ) {
				return $m->getName();
			},
			( new \ReflectionClass( Cli::class ) )->getMethods( \ReflectionMethod::IS_PUBLIC )
		);

		$methods = array_values( array_diff( $methods, array( '__construct' ) ) );
		sort( $methods );

		$this->assertSame( array( 'doctor' ), $methods );
	}
}
