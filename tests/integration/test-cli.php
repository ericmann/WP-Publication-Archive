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

	/** @var array<string, mixed> */
	private $saved_roles;

	/** @var array<string, \WP_Role> */
	private $saved_role_objects;

	public function set_up() {
		parent::set_up();
		\WP_CLI::reset();

		$this->set_permalink_structure( '/%postname%/' );
		Plugin::instance()->post_type()->register();
		Plugin::instance()->rewrites()->register();
		flush_rewrite_rules( false ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.flush_rewrite_rules_flush_rewrite_rules -- reason: test-only, needs this site's own rewrite rules, including the author taxonomy's (registered under plain permalinks at boot, so its rewrite struct is otherwise missing until re-registered here).

		// WP_Roles is a process-wide singleton whose add_cap()/remove_cap()
		// only write to the database when $wp_user_roles (a global
		// populated once, early, for performance) is empty; in this test
		// install it is not, so a capability change here would otherwise
		// outlive the per-test DB transaction rollback entirely.
		$roles                    = wp_roles();
		$this->saved_roles        = $roles->roles;
		$this->saved_role_objects = array();

		foreach ( $roles->role_objects as $name => $role_object ) {
			$this->saved_role_objects[ $name ] = clone $role_object;
		}
	}

	public function tear_down() {
		\WP_CLI::reset();
		remove_filter( 'pre_option_rewrite_rules', '__return_empty_array' );

		$roles = wp_roles();

		$roles->roles = $this->saved_roles;

		foreach ( $this->saved_role_objects as $name => $saved ) {
			if ( isset( $roles->role_objects[ $name ] ) ) {
				$roles->role_objects[ $name ]->capabilities = $saved->capabilities;
			}
		}

		parent::tear_down();
	}

	public function test_doctor_rows_are_the_phase_0_rows_in_order() {
		Plugin::instance()->cli()->doctor( array(), array( 'format' => 'json' ) );

		$rows = json_decode( (string) end( \WP_CLI::$lines ), true );

		$this->assertIsArray( $rows );
		$this->assertSame(
			array( 'lineage', 'version', 'php', 'wp', 'post_type_registered', 'rest_enabled', 'rewrite_rules_present', 'caps_granted', 'dam' ),
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

	public function test_rest_enabled_row_passes() {
		Plugin::instance()->cli()->doctor( array(), array( 'format' => 'json' ) );

		$rows = json_decode( (string) end( \WP_CLI::$lines ), true );

		$rest_rows = array_values(
			array_filter(
				$rows,
				static function ( array $row ): bool {
					return 'rest_enabled' === $row['check'];
				}
			)
		);

		$this->assertCount( 1, $rest_rows );
		$this->assertSame( 'pass', $rest_rows[0]['status'] );
	}

	public function test_rewrite_row_requires_the_author_slug() {
		\WPPA\Plugin::instance()->post_type()->register();
		\WPPA\Plugin::instance()->rewrites()->register();
		flush_rewrite_rules( false ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.flush_rewrite_rules_flush_rewrite_rules -- reason: test-only, needs this site's own rewrite rules.

		// Confirm the endpoint rules alone are not enough once the author
		// slug rule is missing: strip only that rule and expect a fail.
		$strip_author_rule = static function ( $rules ) {
			foreach ( array_keys( $rules ) as $rule ) {
				if ( 0 === strpos( ltrim( (string) $rule, '^' ), Keys::TAX_AUTHOR_REWRITE_SLUG . '/' ) ) {
					unset( $rules[ $rule ] );
				}
			}

			return $rules;
		};

		add_filter( 'option_rewrite_rules', $strip_author_rule );

		try {
			$this->expectException( \WP_CLI\ExitException::class );
			Plugin::instance()->cli()->doctor( array(), array( 'format' => 'json' ) );
		} finally {
			$rows = json_decode( (string) end( \WP_CLI::$lines ), true );

			$rewrite_rows = array_values(
				array_filter(
					$rows,
					static function ( array $row ): bool {
						return 'rewrite_rules_present' === $row['check'];
					}
				)
			);

			$this->assertCount( 1, $rewrite_rows );
			$this->assertSame( 'fail', $rewrite_rows[0]['status'] );

			remove_filter( 'option_rewrite_rules', $strip_author_rule );
		}
	}

	public function test_caps_granted_row_passes_after_grant() {
		Plugin::instance()->capabilities()->grant();

		Plugin::instance()->cli()->doctor( array(), array( 'format' => 'json' ) );

		$rows = json_decode( (string) end( \WP_CLI::$lines ), true );

		$caps_rows = array_values(
			array_filter(
				$rows,
				static function ( array $row ): bool {
					return 'caps_granted' === $row['check'];
				}
			)
		);

		$this->assertCount( 1, $caps_rows );
		$this->assertSame( 'pass', $caps_rows[0]['status'] );
	}

	public function test_caps_granted_row_fails_before_grant() {
		$administrator = get_role( 'administrator' );
		$administrator->remove_cap( Keys::CAP_MAP['edit_posts'] );

		delete_option( Keys::OPT_CAPS );

		try {
			$this->expectException( \WP_CLI\ExitException::class );
			Plugin::instance()->cli()->doctor( array(), array( 'format' => 'json' ) );
		} finally {
			$rows = json_decode( (string) end( \WP_CLI::$lines ), true );

			$caps_rows = array_values(
				array_filter(
					$rows,
					static function ( array $row ): bool {
						return 'caps_granted' === $row['check'];
					}
				)
			);

			$this->assertCount( 1, $caps_rows );
			$this->assertSame( 'fail', $caps_rows[0]['status'] );

			Plugin::instance()->capabilities()->grant();
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
