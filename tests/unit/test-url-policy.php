<?php
/**
 * Implements SPEC.md §6.2: Url_Policy is a pure leaf, tested with no
 * WordPress loaded.
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA\Tests\Unit;

use WPPA\Url_Policy;

class Test_Url_Policy extends \PHPUnit\Framework\TestCase {

	public function test_is_final() {
		$reflection = new \ReflectionClass( Url_Policy::class );

		$this->assertTrue( $reflection->isFinal() );
	}

	public function test_normalise_turns_pipe_forms_into_schemes() {
		$policy = new Url_Policy(
			'example.org',
			static function () {
				return true;
			}
		);

		$this->assertSame( 'http://example.org/a.pdf', $policy->normalise( 'http|example.org/a.pdf' ) );
		$this->assertSame( 'https://example.org/a.pdf', $policy->normalise( 'https|example.org/a.pdf' ) );
		$this->assertSame( 'https://example.org/a.pdf', $policy->normalise( 'https://example.org/a.pdf' ) );
	}

	public function test_normalise_trims() {
		$policy = new Url_Policy(
			'example.org',
			static function () {
				return true;
			}
		);

		$this->assertSame( 'https://example.org/a.pdf', $policy->normalise( "  https://example.org/a.pdf\n" ) );
	}

	public function test_is_same_site_compares_host_case_insensitively() {
		$policy = new Url_Policy(
			'Example.org',
			static function () {
				return true;
			}
		);

		$this->assertTrue( $policy->is_same_site( 'https://EXAMPLE.ORG/a.pdf' ) );
		$this->assertTrue( $policy->is_same_site( 'https://example.org/a.pdf' ) );
		$this->assertFalse( $policy->is_same_site( 'https://other.example/a.pdf' ) );
		$this->assertFalse( $policy->is_same_site( '/etc/passwd' ) );
	}

	public function test_validate_is_not_implemented_yet() {
		$policy = new Url_Policy(
			'example.org',
			static function () {
				return true;
			}
		);

		$this->expectException( \WPPA\NotImplementedException::class );

		$policy->validate( 'https://example.org/a.pdf' );
	}
}
