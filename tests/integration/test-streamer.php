<?php
/**
 * Implements SPEC.md §6.2 Streamer::send(): the plugin's one file-read call
 * (P11).
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA\Tests;

use WPPA\Streamer;

class Test_Streamer extends \WP_UnitTestCase {

	public function test_send_accepts_a_wp_tempnam_file() {
		$path = wp_tempnam( 'streamer-send-' );
		// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_file_put_contents -- reason: test-only fixture file.
		file_put_contents( $path, 'file-bytes' );

		$seen_headers = array();

		ob_start();

		$streamer = new Streamer(
			get_temp_dir(),
			static function () {
				throw new \RuntimeException( 'exit called' );
			},
			static function ( $line ) use ( &$seen_headers ) {
				$seen_headers[] = $line;
			},
			ob_get_level()
		);

		try {
			$streamer->send( $path, 'text/plain', null );
			$this->fail( 'Expected the exit callable to run.' );
		} catch ( \RuntimeException $e ) {
			unset( $e );
		}

		$output = ob_get_clean();

		$this->assertSame( 'Content-Type: text/plain', $seen_headers[0] );
		$this->assertSame( 'file-bytes', $output );
		$this->assertFileDoesNotExist( $path );
	}
}
