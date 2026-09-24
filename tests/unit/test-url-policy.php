<?php
/**
 * Implements SPEC.md §6.2: Url_Policy is a pure leaf, tested with no
 * WordPress loaded.
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA\Tests\Unit;

use WPPA\Keys;
use WPPA\Url_Policy;

class Test_Url_Policy extends \PHPUnit\Framework\TestCase {

	const SITE_HOST = 'example.org';

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

	/**
	 * @return array<string, array{0: string, 1: string, 2: bool, 3: ?string}>
	 */
	public function provide_validate_table() {
		return array(
			'local path'                    => array( '/etc/passwd', 'never', false, null ),
			'relative traversal'             => array( '../wp-config.php', 'never', false, null ),
			'file scheme'                    => array( 'file:///etc/passwd', 'never', false, null ),
			'javascript scheme'              => array( 'javascript:alert(1)', 'never', false, null ),
			'loopback, stub rejects'         => array( 'http://127.0.0.1/', 'reject', false, null ),
			'metadata service, stub rejects' => array( 'http://169.254.169.254/latest/meta-data/', 'reject', false, null ),
			'same-site, stub never called'   => array( 'http://' . self::SITE_HOST . '/wp-content/uploads/a.pdf', 'never', true, 'http://' . self::SITE_HOST . '/wp-content/uploads/a.pdf' ),
			'external, stub accepts'         => array( 'https://example.com/a.pdf', 'accept', true, 'https://example.com/a.pdf' ),
			'external pipe form, stub accepts' => array( 'https|example.com/a.pdf', 'accept', true, 'https://example.com/a.pdf' ),
			'empty string'                   => array( '', 'never', false, null ),
		);
	}

	/**
	 * @dataProvider provide_validate_table
	 */
	public function test_d1_validate_table( string $url, string $stub_mode, bool $expect_accept, ?string $expected_value ) {
		$called = false;

		$is_safe_external = function ( string $checked_url ) use ( &$called, $stub_mode ): bool {
			$called = true;

			return 'accept' === $stub_mode;
		};

		$policy = new Url_Policy( self::SITE_HOST, $is_safe_external );

		$result = $policy->validate( $url );

		if ( 'never' === $stub_mode ) {
			$this->assertFalse( $called, 'The external validator must not be called for this row.' );
		}

		if ( $expect_accept ) {
			$this->assertIsString( $result );
			$this->assertSame( $expected_value, $result );
		} else {
			$this->assertInstanceOf( \WP_Error::class, $result );
			$this->assertSame( Keys::ERR_INVALID_URL, $result->get_error_code() );
		}
	}

	public function test_same_site_url_never_calls_the_external_validator() {
		$called = false;

		$policy = new Url_Policy(
			self::SITE_HOST,
			function () use ( &$called ) {
				$called = true;

				return true;
			}
		);

		$result = $policy->validate( 'http://' . self::SITE_HOST . '/wp-content/uploads/a.pdf' );

		$this->assertFalse( $called );
		$this->assertSame( 'http://' . self::SITE_HOST . '/wp-content/uploads/a.pdf', $result );
	}
}
