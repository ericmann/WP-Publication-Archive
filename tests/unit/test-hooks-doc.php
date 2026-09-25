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

	public function test_every_documented_method_exists() {
		$path = dirname( __DIR__, 2 ) . '/docs/HOOKS.md';
		// phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown -- reason: local source file, not remote data.
		$contents = file_get_contents( $path );

		$this->assertNotFalse( $contents );

		preg_match_all( '/^\|\s*`[^`]+`\s*\|\s*`([a-z_]+)`\s*\|/m', $contents, $matches );

		$this->assertNotEmpty( $matches[1] );

		foreach ( array_unique( $matches[1] ) as $method ) {
			$this->assertTrue(
				method_exists( \WPPA\Hooks::class, $method ),
				sprintf( 'docs/HOOKS.md names Hooks::%s(), which does not exist', $method )
			);

			$reflection = new \ReflectionMethod( \WPPA\Hooks::class, $method );
			$this->assertTrue( $reflection->isStatic() );
		}
	}
}
