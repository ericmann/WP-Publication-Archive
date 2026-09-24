<?php
/**
 * Implements SPEC.md §8 Phase 0 item 4: the interim 3.0.1 proxy transfer,
 * the one readfile() in the plugin (P11).
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA\Tests;

use WPPA\Streamer;

class Test_Streamer extends \WP_UnitTestCase {

	public function test_passthrough_emits_headers_then_file_bytes() {
		$path = get_temp_dir() . 'streamer-test.txt';
		// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_file_put_contents -- reason: test-only fixture file.
		file_put_contents( $path, 'file-bytes' );

		$seen_headers = array();

		$streamer = new Streamer(
			static function () {
				throw new \RuntimeException( 'exit called' );
			},
			static function ( $line ) use ( &$seen_headers ) {
				$seen_headers[] = $line;
			}
		);

		ob_start();

		try {
			$streamer->passthrough( $path, array( 'HTTP/1.1 200 OK', 'Content-type: text/plain' ) );
			$this->fail( 'Expected the exit callable to run.' );
		} catch ( \RuntimeException $e ) {
			$this->assertSame( 'exit called', $e->getMessage() );
		}

		$output = ob_get_clean();

		$this->assertSame( array( 'HTTP/1.1 200 OK', 'Content-type: text/plain' ), $seen_headers );
		$this->assertSame( 'file-bytes', $output );

		// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_unlink -- reason: test-only fixture cleanup.
		unlink( $path );
	}

	public function test_passthrough_calls_exit() {
		$called = false;

		$streamer = new Streamer(
			static function () use ( &$called ) {
				$called = true;

				throw new \RuntimeException( 'stop' );
			},
			static function () {}
		);

		$path = get_temp_dir() . 'streamer-exit-test.txt';
		// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_file_put_contents -- reason: test-only fixture file.
		file_put_contents( $path, 'x' );

		ob_start();

		try {
			$streamer->passthrough( $path, array() );
		} catch ( \RuntimeException $e ) {
			unset( $e );
		}

		ob_end_clean();

		$this->assertTrue( $called );

		// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_unlink -- reason: test-only fixture cleanup.
		unlink( $path );
	}
}
