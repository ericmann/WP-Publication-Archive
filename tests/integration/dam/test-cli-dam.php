<?php
/**
 * Implements SPEC.md §6.8: the `dam` doctor row, with the DAM loaded.
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA\Tests;

use WPPA\Keys;
use WPPA\Plugin;

/**
 * @group dam
 */
class Test_Cli_Dam extends \WP_UnitTestCase {

	public function set_up() {
		parent::set_up();
		\WP_CLI::reset();
	}

	public function tear_down() {
		\WP_CLI::reset();
		parent::tear_down();
	}

	private function dam_row(): array {
		try {
			Plugin::instance()->cli()->doctor( array(), array( 'format' => 'json' ) );
		} catch ( \WP_CLI\ExitException $e ) {
			unset( $e );
		}

		$rows = json_decode( (string) end( \WP_CLI::$lines ), true );

		foreach ( $rows as $row ) {
			if ( 'dam' === $row['check'] ) {
				return $row;
			}
		}

		$this->fail( 'No dam row found.' );
	}

	public function test_dam_row_reports_version_and_attached_filter() {
		$row = $this->dam_row();

		$this->assertSame( 'pass', $row['status'] );
		$this->assertStringContainsString( Plugin::instance()->dam_bridge()->version(), $row['message'] );
		$this->assertStringContainsString( 'usage filter attached', $row['message'] );
	}

	public function test_dam_row_fails_when_filter_detached() {
		$bridge = Plugin::instance()->dam_bridge();

		remove_filter( Keys::HOOK_DAM_INDEXED_IDS, array( $bridge, 'indexed_attachment_ids' ), 10 );

		try {
			$row = $this->dam_row();

			$this->assertSame( 'fail', $row['status'] );
			$this->assertStringContainsString( 'usage filter not attached', $row['message'] );
		} finally {
			add_filter( Keys::HOOK_DAM_INDEXED_IDS, array( $bridge, 'indexed_attachment_ids' ), 10, 2 );
		}
	}
}
