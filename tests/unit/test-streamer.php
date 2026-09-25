<?php
/**
 * Implements SPEC.md §6.2 Streamer::send(): the one temp-file readfile()
 * (P11), tested with no WordPress loaded.
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA\Tests\Unit;

use WPPA\Streamer;

class Test_Streamer extends \PHPUnit\Framework\TestCase {

	/** @var string */
	private $temp_dir;

	public function setUp(): void {
		$this->temp_dir = sys_get_temp_dir() . '/wppa-streamer-test-' . uniqid( '', true );
		mkdir( $this->temp_dir ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir, WordPressVIPMinimum.Functions.RestrictedFunctions.directory_mkdir -- reason: test-only fixture directory on the bare host, no WordPress loaded.
	}

	public function tearDown(): void {
		if ( is_dir( $this->temp_dir ) ) {
			foreach ( glob( $this->temp_dir . '/*' ) ?: array() as $leftover ) {
				unlink( $leftover ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink, WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_unlink -- reason: test-only fixture cleanup on the bare host, no WordPress loaded.
			}
			rmdir( $this->temp_dir ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir, WordPressVIPMinimum.Functions.RestrictedFunctions.directory_rmdir -- reason: test-only fixture cleanup on the bare host, no WordPress loaded.
		}
	}

	private function make_file( string $contents ): string {
		$path = $this->temp_dir . '/' . uniqid( 'send-', true ) . '.bin';
		file_put_contents( $path, $contents ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents, WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_file_put_contents -- reason: test-only fixture file on the bare host, no WordPress loaded.

		return $path;
	}

	public function test_send_emits_type_and_length_headers_in_order() {
		$headers = array();

		ob_start();

		$streamer = new Streamer(
			$this->temp_dir,
			static function () {
				throw new \RuntimeException( 'exit called' );
			},
			static function ( $line ) use ( &$headers ) {
				$headers[] = $line;
			},
			ob_get_level()
		);

		$path = $this->make_file( 'hello' );

		try {
			$streamer->send( $path, 'text/plain', null );
		} catch ( \RuntimeException $e ) {
			unset( $e );
		}

		ob_get_clean();

		$this->assertSame( 'Content-Type: text/plain', $headers[0] );
		$this->assertSame( 'Content-Length: 5', $headers[1] );
	}

	public function test_send_adds_disposition_only_when_a_filename_is_given() {
		$headers = array();

		ob_start();

		$streamer = new Streamer(
			$this->temp_dir,
			static function () {
				throw new \RuntimeException( 'exit called' );
			},
			static function ( $line ) use ( &$headers ) {
				$headers[] = $line;
			},
			ob_get_level()
		);

		$path = $this->make_file( 'x' );

		try {
			$streamer->send( $path, 'text/plain', null );
		} catch ( \RuntimeException $e ) {
			unset( $e );
		}

		ob_get_clean();

		foreach ( $headers as $header ) {
			$this->assertStringNotContainsStringIgnoringCase( 'Content-Disposition', $header );
		}

		$headers2 = array();

		ob_start();

		$streamer2 = new Streamer(
			$this->temp_dir,
			static function () {
				throw new \RuntimeException( 'exit called' );
			},
			static function ( $line ) use ( &$headers2 ) {
				$headers2[] = $line;
			},
			ob_get_level()
		);

		$path2 = $this->make_file( 'x' );

		try {
			$streamer2->send( $path2, 'text/plain', 'a.pdf' );
		} catch ( \RuntimeException $e ) {
			unset( $e );
		}

		ob_get_clean();

		$this->assertSame( 'Content-Disposition: attachment; filename="a.pdf"', $headers2[3] );
	}

	public function test_send_strips_quotes_and_newlines_from_the_filename() {
		$headers = array();

		ob_start();

		$streamer = new Streamer(
			$this->temp_dir,
			static function () {
				throw new \RuntimeException( 'exit called' );
			},
			static function ( $line ) use ( &$headers ) {
				$headers[] = $line;
			},
			ob_get_level()
		);

		$path = $this->make_file( 'x' );

		try {
			$streamer->send( $path, 'text/plain', "a\"\r\n.pdf" );
		} catch ( \RuntimeException $e ) {
			unset( $e );
		}

		ob_get_clean();

		$this->assertSame( 'Content-Disposition: attachment; filename="a.pdf"', $headers[3] );
	}

	public function test_send_outputs_the_file_bytes_and_deletes_the_file() {
		ob_start();

		$streamer = new Streamer(
			$this->temp_dir,
			static function () {
				throw new \RuntimeException( 'exit called' );
			},
			static function () {},
			ob_get_level()
		);

		$path = $this->make_file( 'file-bytes' );

		try {
			$streamer->send( $path, 'text/plain', null );
			$this->fail( 'Expected the exit callable to run.' );
		} catch ( \RuntimeException $e ) {
			unset( $e );
		}

		$output = ob_get_clean();

		$this->assertSame( 'file-bytes', $output );
		$this->assertFileDoesNotExist( $path );
	}

	public function test_send_calls_the_exit_callable() {
		$exits = array();

		ob_start();

		$streamer = new Streamer(
			$this->temp_dir,
			static function () use ( &$exits ) {
				$exits[] = true;

				throw new \RuntimeException( 'exit called' );
			},
			static function () {},
			ob_get_level()
		);

		$path = $this->make_file( 'x' );

		try {
			$streamer->send( $path, 'text/plain', null );
		} catch ( \RuntimeException $e ) {
			unset( $e );
		}

		ob_get_clean();

		$this->assertSame( array( true ), $exits );
	}

	public function test_send_always_sends_nosniff() {
		foreach ( array( null, 'a.pdf' ) as $filename ) {
			$headers = array();

			ob_start();

			$streamer = new Streamer(
				$this->temp_dir,
				static function () {
					throw new \RuntimeException( 'exit called' );
				},
				static function ( $line ) use ( &$headers ) {
					$headers[] = $line;
				},
				ob_get_level()
			);

			$path = $this->make_file( 'x' );

			try {
				$streamer->send( $path, 'text/plain', $filename );
			} catch ( \RuntimeException $e ) {
				unset( $e );
			}

			ob_get_clean();

			$this->assertSame( 'X-Content-Type-Options: nosniff', $headers[2] );
		}
	}

	/**
	 * @dataProvider active_content_type_provider
	 */
	public function test_send_forces_attachment_for_active_content_with_no_filename( string $content_type ) {
		$headers = array();

		ob_start();

		$streamer = new Streamer(
			$this->temp_dir,
			static function () {
				throw new \RuntimeException( 'exit called' );
			},
			static function ( $line ) use ( &$headers ) {
				$headers[] = $line;
			},
			ob_get_level()
		);

		$path = $this->make_file( 'x' );

		try {
			$streamer->send( $path, $content_type, null );
		} catch ( \RuntimeException $e ) {
			unset( $e );
		}

		ob_get_clean();

		$dispositions = array_filter(
			$headers,
			static function ( $header ) {
				return 0 === stripos( $header, 'Content-Disposition' );
			}
		);

		$this->assertNotEmpty( $dispositions, 'Expected a Content-Disposition header for ' . $content_type );
		$this->assertStringContainsStringIgnoringCase( 'attachment', reset( $dispositions ) );
	}

	/**
	 * @return array<int, array<int, string>>
	 */
	public function active_content_type_provider(): array {
		return array(
			array( 'text/html' ),
			array( 'text/html; charset=UTF-8' ),
			array( 'IMAGE/SVG+XML' ),
			array( 'application/javascript' ),
		);
	}

	public function test_send_sends_no_disposition_for_pdf_view() {
		$headers = array();

		ob_start();

		$streamer = new Streamer(
			$this->temp_dir,
			static function () {
				throw new \RuntimeException( 'exit called' );
			},
			static function ( $line ) use ( &$headers ) {
				$headers[] = $line;
			},
			ob_get_level()
		);

		$path = $this->make_file( 'x' );

		try {
			$streamer->send( $path, 'application/pdf', null );
		} catch ( \RuntimeException $e ) {
			unset( $e );
		}

		ob_get_clean();

		foreach ( $headers as $header ) {
			$this->assertStringNotContainsStringIgnoringCase( 'Content-Disposition', $header );
		}
	}

	public function test_send_refuses_a_sibling_dir_sharing_the_temp_dir_prefix() {
		$streamer = new Streamer(
			$this->temp_dir,
			static function () {
				throw new \RuntimeException( 'exit called' );
			},
			static function () {},
			ob_get_level()
		);

		$sibling = $this->temp_dir . '-evil';
		mkdir( $sibling ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir, WordPressVIPMinimum.Functions.RestrictedFunctions.directory_mkdir -- reason: test-only fixture directory on the bare host, no WordPress loaded.

		$outside = $sibling . '/evil.bin';
		file_put_contents( $outside, 'x' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents, WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_file_put_contents -- reason: test-only fixture file on the bare host, no WordPress loaded.

		try {
			$this->expectException( \InvalidArgumentException::class );

			$streamer->send( $outside, 'text/plain', null );
		} finally {
			unlink( $outside ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink, WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_unlink -- reason: test-only fixture cleanup on the bare host, no WordPress loaded.
			rmdir( $sibling ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir, WordPressVIPMinimum.Functions.RestrictedFunctions.directory_rmdir -- reason: test-only fixture cleanup on the bare host, no WordPress loaded.
		}
	}

	public function test_send_refuses_a_path_outside_the_temp_dir() {
		$streamer = new Streamer(
			$this->temp_dir,
			static function () {
				throw new \RuntimeException( 'exit called' );
			},
			static function () {},
			ob_get_level()
		);

		$outside = sys_get_temp_dir() . '/wppa-outside-' . uniqid( '', true ) . '.bin';
		file_put_contents( $outside, 'x' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents, WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_file_put_contents -- reason: test-only fixture file on the bare host, no WordPress loaded.

		try {
			$this->expectException( \InvalidArgumentException::class );

			$streamer->send( $outside, 'text/plain', null );
		} finally {
			unlink( $outside ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink, WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_unlink -- reason: test-only fixture cleanup on the bare host, no WordPress loaded.
		}
	}

	/**
	 * D6: ob_end_clean() must not run when there is no buffer to end.
	 * Spawns a child process with zero output buffers so PHP 8's notice, if
	 * any, would land on the child's stderr rather than this process' own
	 * (already-buffered-by-PHPUnit) output.
	 */
	public function test_d6_no_notice_with_zero_output_buffers() {
		$path = $this->make_file( 'child-bytes' );

		$descriptors = array(
			0 => array( 'pipe', 'r' ),
			1 => array( 'pipe', 'w' ),
			2 => array( 'pipe', 'w' ),
		);

		$process = proc_open( // phpcs:ignore WordPress.WP.AlternativeFunctions.proc_open_proc_open, WordPress.PHP.DiscouragedPHPFunctions.system_calls_proc_open -- reason: test-only, spawns a bare-PHP child with zero output buffers to observe stderr in isolation.
			array( PHP_BINARY, dirname( __DIR__ ) . '/fixtures/streamer-child.php', $path ),
			$descriptors,
			$pipes
		);

		$this->assertIsResource( $process );

		fclose( $pipes[0] );
		$stdout = stream_get_contents( $pipes[1] );
		$stderr = stream_get_contents( $pipes[2] );
		fclose( $pipes[1] );
		fclose( $pipes[2] );
		proc_close( $process );

		$this->assertSame( '', $stderr );
		$this->assertSame( 'child-bytes', $stdout );
	}
}
