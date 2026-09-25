<?php
/**
 * Implements SPEC.md §7: .distignore keeps dev-only paths out of the built
 * zip.
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA\Tests;

class Test_Distignore extends \PHPUnit\Framework\TestCase {

	public function test_distignore_excludes_dev_paths() {
		$path = dirname( __DIR__, 2 ) . '/.distignore';
		// phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown -- reason: local source file, not remote data.
		$contents = (string) file_get_contents( $path );

		$lines = array_filter(
			array_map( 'trim', explode( "\n", $contents ) ),
			static function ( $line ) {
				return '' !== $line && '#' !== $line[0];
			}
		);

		$expected = array(
			'/.git',
			'/.github',
			'/.cache',
			'/.claude',
			'/.foundry',
			'.distignore',
			'.gitignore',
			'.editorconfig',
			'CLAUDE.md',
			'/bin',
			'/docs',
			'/tests',
			'/phpstan',
			'/node_modules',
			'/vendor',
			'/dist',
			'.wp-env.json',
			'.wp-env.override.json',
			'package.json',
			'package-lock.json',
			'phpcs.xml.dist',
			'phpstan.neon.dist',
			'phpunit.xml.dist',
			'.phpunit.result.cache',
			'V4_SPEC.md',
			'V4_FOUNDRY.json',
			'V4-ASSESSMENT.md',
		);

		foreach ( $expected as $entry ) {
			$this->assertContains( $entry, $lines, $entry . ' is missing from .distignore' );
		}
	}
}
