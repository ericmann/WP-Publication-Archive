<?php
/**
 * Implements SPEC.md §7.4: proves the pinned DAM ref exposes the contract
 * Dam_Bridge will depend on (Phase 1). Runs only when the DAM is loaded.
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA\Tests;

use WPPA\Keys;

/**
 * @group dam
 */
class Test_Dam_Contract extends \WP_UnitTestCase {

	public function test_embargo_guard_is_hidden_exists_with_one_parameter() {
		$this->assertTrue( class_exists( '\\VIP\\DAM\\Embargo_Guard' ) );

		$method = new \ReflectionMethod( '\\VIP\\DAM\\Embargo_Guard', 'is_hidden' );

		$this->assertTrue( $method->isStatic() );
		$this->assertSame( 1, $method->getNumberOfParameters() );
	}

	public function test_embargo_guard_placeholder_url_exists_with_no_parameters() {
		$method = new \ReflectionMethod( '\\VIP\\DAM\\Embargo_Guard', 'placeholder_url' );

		$this->assertTrue( $method->isStatic() );
		$this->assertSame( 0, $method->getNumberOfParameters() );
	}

	public function test_usage_index_get_usage_exists_with_one_parameter() {
		$this->assertTrue( class_exists( '\\VIP\\DAM\\Usage_Index' ) );

		$method = new \ReflectionMethod( '\\VIP\\DAM\\Usage_Index', 'get_usage' );

		$this->assertTrue( $method->isStatic() );
		$this->assertSame( 1, $method->getNumberOfParameters() );
	}

	public function test_lifecycle_class_exists() {
		$this->assertTrue( class_exists( '\\VIP\\DAM\\Lifecycle' ) );
	}

	public function test_dam_applies_the_indexed_attachment_ids_filter() {
		$inc_dir = WP_PLUGIN_DIR . '/' . Keys::DAM_PLUGIN_SLUG . '/inc';

		$this->assertDirectoryExists( $inc_dir );

		$found     = false;
		$iterator  = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $inc_dir, \FilesystemIterator::SKIP_DOTS ) );
		$needle    = "apply_filters( '" . Keys::HOOK_DAM_INDEXED_IDS . "'";
		$needle_no_space = "apply_filters('" . Keys::HOOK_DAM_INDEXED_IDS . "'";

		foreach ( $iterator as $file ) {
			if ( ! $file->isFile() || 'php' !== $file->getExtension() ) {
				continue;
			}

			// phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown -- reason: scanning local DAM source for its own filter call, not remote data.
			$contents = file_get_contents( $file->getPathname() );

			if ( false !== $contents && ( false !== strpos( $contents, $needle ) || false !== strpos( $contents, $needle_no_space ) ) ) {
				$found = true;
				break;
			}
		}

		$this->assertTrue( $found, 'Expected an apply_filters() call naming ' . Keys::HOOK_DAM_INDEXED_IDS . ' under ' . $inc_dir );
	}
}
