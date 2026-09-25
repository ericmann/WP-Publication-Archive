<?php
/**
 * Implements SPEC.md §5.5: readme.txt and CHANGELOG.md carry the 3.1.0
 * release headers, changelog and upgrade notice.
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA\Tests;

use WPPA\Keys;

class Test_Readme extends \PHPUnit\Framework\TestCase {

	private function readme(): string {
		$path = dirname( __DIR__, 2 ) . '/readme.txt';
		// phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown -- reason: local source file, not remote data.
		$contents = file_get_contents( $path );

		$this->assertNotFalse( $contents );

		return $contents;
	}

	public function test_readme_headers_match_keys() {
		$contents = $this->readme();

		$this->assertStringContainsString( 'Tested up to: 7.1', $contents );
		$this->assertStringContainsString( 'Stable tag: ' . Keys::VERSION, $contents );
		$this->assertStringContainsString( 'Requires PHP: ' . Keys::MIN_PHP, $contents );
		$this->assertStringContainsString( 'Requires at least: ' . Keys::MIN_WP, $contents );
	}

	public function test_readme_has_310_changelog_and_upgrade_notice() {
		$contents = $this->readme();

		$this->assertStringContainsString( '= ' . Keys::VERSION . ' =', $contents );

		$changelog_pos = strpos( $contents, '== Changelog ==' );
		$upgrade_pos   = strpos( $contents, '== Upgrade Notice ==' );

		$this->assertIsInt( $changelog_pos );
		$this->assertIsInt( $upgrade_pos );
		$this->assertGreaterThan( $changelog_pos, $upgrade_pos );

		$changelog_section = substr( $contents, $changelog_pos, $upgrade_pos - $changelog_pos );
		$upgrade_section   = substr( $contents, $upgrade_pos );

		foreach ( range( 1, 19 ) as $d ) {
			$this->assertMatchesRegularExpression( '/\bD' . $d . '\b/', $changelog_section, 'Changelog is missing D' . $d );
		}

		$this->assertStringContainsString( Keys::FILTER_MASK_URL, $upgrade_section );
	}

	public function test_changelog_md_has_310_section() {
		$path = dirname( __DIR__, 2 ) . '/CHANGELOG.md';
		// phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown -- reason: local source file, not remote data.
		$contents = file_get_contents( $path );

		$this->assertNotFalse( $contents );
		$this->assertStringContainsString( '## ' . Keys::VERSION, $contents );

		foreach ( range( 1, 19 ) as $d ) {
			$this->assertMatchesRegularExpression( '/\bD' . $d . '\b/', $contents, 'CHANGELOG.md is missing D' . $d );
		}
	}
}
