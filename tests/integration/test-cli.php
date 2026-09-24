<?php
/**
 * Implements SPEC.md §6: `doctor` runs, reports the Phase 0 rows in order,
 * and halts on a failed check. Uses the WP_CLI shim from tests/.
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA\Tests\Integration;

use WPPA\Keys;
use WPPA\Plugin;

class Test_Cli extends \WP_UnitTestCase {

	public function set_up() {
		parent::set_up();
		\WP_CLI::reset();

		$this->set_permalink_structure( '/%postname%/' );
	}

	public function tear_down() {
		\WP_CLI::reset();
		remove_filter( 'pre_option_rewrite_rules', '__return_empty_array' );
		parent::tear_down();
	}

	public function test_doctor_rows_are_the_phase_0_rows_in_order() {
		Plugin::instance()->cli()->doctor( array(), array( 'format' => 'json' ) );

		$rows = json_decode( (string) end( \WP_CLI::$lines ), true );

		$this->assertIsArray( $rows );
		$this->assertSame(
			array( 'lineage', 'version', 'php', 'wp', 'post_type_registered', 'rewrite_rules_present', 'dam' ),
			array_column( $rows, 'check' )
		);
	}

	public function test_doctor_passes_on_this_site() {
		Plugin::instance()->cli()->doctor( array(), array( 'format' => 'json' ) );

		$rows = json_decode( (string) end( \WP_CLI::$lines ), true );

		$this->assertNotContains( 'fail', array_column( $rows, 'status' ) );
	}

	public function test_rewrite_rules_row_fails_when_endpoint_rules_missing() {
		add_filter( 'pre_option_rewrite_rules', '__return_empty_array' );

		try {
			$this->expectException( \WP_CLI\ExitException::class );
			Plugin::instance()->cli()->doctor( array(), array( 'format' => 'json' ) );
		} finally {
			remove_filter( 'pre_option_rewrite_rules', '__return_empty_array' );
		}
	}

	/**
	 * @group nodam
	 */
	public function test_dam_row_reports_absent_and_passes() {
		Plugin::instance()->cli()->doctor( array(), array( 'format' => 'json' ) );

		$rows = json_decode( (string) end( \WP_CLI::$lines ), true );

		$dam_rows = array_values(
			array_filter(
				$rows,
				static function ( array $row ): bool {
					return 'dam' === $row['check'];
				}
			)
		);

		$this->assertCount( 1, $dam_rows );
		$this->assertSame( 'pass', $dam_rows[0]['status'] );
		$this->assertSame( 'absent', $dam_rows[0]['message'] );
	}
}
