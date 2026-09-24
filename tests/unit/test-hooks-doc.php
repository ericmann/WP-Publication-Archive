<?php
/**
 * Implements SPEC.md §6.4: every exposed hook is documented in docs/HOOKS.md.
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA\Tests;

use WPPA\Keys;

class Test_Hooks_Doc extends \PHPUnit\Framework\TestCase {

	public function test_every_exposed_and_core_hook_is_documented() {
		$path = dirname( __DIR__, 2 ) . '/docs/HOOKS.md';
		// phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown -- reason: local source file, not remote data.
		$contents = file_get_contents( $path );

		$this->assertNotFalse( $contents );

		$reflection = new \ReflectionClass( Keys::class );

		foreach ( $reflection->getConstants() as $name => $value ) {
			$is_hook_name = 0 === strpos( $name, 'FILTER_' )
				|| 0 === strpos( $name, 'ACTION_' )
				|| 0 === strpos( $name, 'CORE_FILTER_' );

			if ( ! $is_hook_name ) {
				continue;
			}

			$this->assertStringContainsString(
				'`' . $value . '`',
				$contents,
				sprintf( 'Keys::%s (%s) is not documented in docs/HOOKS.md', $name, $value )
			);
		}
	}
}
