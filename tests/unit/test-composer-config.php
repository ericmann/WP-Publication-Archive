<?php
/**
 * Implements REVIEW finding 2: composer's process timeout must be disabled
 * so `composer test`/`composer verify` can finish without a caller-side
 * COMPOSER_PROCESS_TIMEOUT override.
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA\Tests\Unit;

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

class Test_Composer_Config extends TestCase {

	public function test_process_timeout_is_disabled() {
		$path = dirname( __DIR__, 2 ) . '/composer.json';
		// phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown -- reason: local source file, not remote data.
		$contents = (string) file_get_contents( $path );
		$config   = json_decode( $contents, true );

		$this->assertIsArray( $config );
		$this->assertArrayHasKey( 'config', $config );
		$this->assertArrayHasKey( 'process-timeout', $config['config'] );
		$this->assertSame( 0, $config['config']['process-timeout'] );
	}
}
