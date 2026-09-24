<?php
/**
 * Implements SPEC.md §8 Phase 0 item 4 and §6.1: the 3.0.1 schema upgrade.
 * D9 (P2-06): maybe_upgrade() runs once on init, after the rules are
 * registered, flushes rewrites once, and does no option work on later
 * requests.
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA\Tests;

use WPPA\FixedClock;
use WPPA\Flags;
use WPPA\Keys;
use WPPA\Upgrade;
use WPPA\Tests\Fixtures\V3_Site;

class Test_Upgrade extends \WP_UnitTestCase {

	public function tear_down() {
		delete_option( Keys::OPT_SCHEMA );

		parent::tear_down();
	}

	private function upgrade() {
		return new Upgrade( new Flags( new FixedClock( 1 ) ) );
	}

	public function test_upgrade_from_2_moves_legacy_description_into_content() {
		$id = self::factory()->post->create(
			array(
				'post_type'    => Keys::POST_TYPE,
				'post_content' => '',
			)
		);
		V3_Site::raw_meta( $id, Keys::META_LEGACY_DESC, 'Legacy description.' );

		$this->upgrade()->run( 2 );

		$this->assertSame( 'Legacy description.', get_post( $id )->post_content );
	}

	public function test_absent_schema_upgrades_to_3() {
		delete_option( Keys::OPT_SCHEMA );

		$this->upgrade()->maybe_upgrade();

		$this->assertSame( Keys::SCHEMA_VERSION, (int) get_option( Keys::OPT_SCHEMA ) );
	}

	public function test_current_schema_is_left_as_is() {
		update_option( Keys::OPT_SCHEMA, Keys::SCHEMA_VERSION );

		$this->upgrade()->maybe_upgrade();

		$this->assertSame( Keys::SCHEMA_VERSION, (int) get_option( Keys::OPT_SCHEMA ) );
	}

	/**
	 * D9: the flush inside maybe_upgrade() runs after this request's rules
	 * are registered, so the flushed rule set includes this plugin's own
	 * publication/view/... rule.
	 */
	public function test_d9_upgrade_flush_includes_the_publication_rules() {
		delete_option( Keys::OPT_SCHEMA );

		$this->set_permalink_structure( '/%postname%/' );
		\WPPA\Plugin::instance()->post_type()->register();
		\WPPA\Plugin::instance()->rewrites()->register();

		$this->upgrade()->maybe_upgrade();

		$found = false;

		foreach ( array_keys( $this->upgrade()->flags()->rewrite_rules() ) as $rule ) {
			if ( 0 === strpos( $rule, '^' . Keys::REWRITE_BASE . '/' . Keys::ENDPOINT_VIEW . '/' ) ) {
				$found = true;
				break;
			}
		}

		$this->assertTrue( $found, 'Expected a flushed rule starting ' . Keys::REWRITE_BASE . '/' . Keys::ENDPOINT_VIEW . '/' );
	}

	/**
	 * D9: once the schema is current, later requests write no option and
	 * flush nothing.
	 */
	public function test_d9_second_run_writes_no_option_and_does_not_flush() {
		update_option( Keys::OPT_SCHEMA, Keys::SCHEMA_VERSION );

		$add_option_count       = 0;
		$update_option_count    = 0;
		$generate_rules_count   = 0;

		add_action(
			'add_option',
			static function () use ( &$add_option_count ) {
				++$add_option_count;
			}
		);
		add_action(
			'update_option',
			static function () use ( &$update_option_count ) {
				++$update_option_count;
			}
		);
		add_filter(
			'generate_rewrite_rules',
			static function ( $wp_rewrite ) use ( &$generate_rules_count ) {
				++$generate_rules_count;

				return $wp_rewrite;
			}
		);

		$this->upgrade()->maybe_upgrade();

		$this->assertSame( 0, $add_option_count );
		$this->assertSame( 0, $update_option_count );
		$this->assertSame( 0, $generate_rules_count );
	}

	public function test_schema_option_autoload_is_off() {
		global $wpdb;

		delete_option( Keys::OPT_SCHEMA );

		$this->upgrade()->maybe_upgrade();

		$autoload = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- reason: test-only, asserting the autoload column WP's option API does not expose.
			$wpdb->prepare(
				"SELECT autoload FROM {$wpdb->options} WHERE option_name = %s", // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- reason: test-only, {$wpdb->options} is the table-name interpolation WordPress's own $wpdb->prepare() docs use.
				Keys::OPT_SCHEMA
			)
		);

		$this->assertContains( $autoload, array( 'no', 'off' ) );
	}
}
