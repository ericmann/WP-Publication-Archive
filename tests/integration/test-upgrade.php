<?php
/**
 * Implements SPEC.md §8 Phase 0 item 4: the 3.0.1 schema upgrade (D9
 * preserved: it runs at load time, not on a hook, until P2-06).
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

	public function test_absent_schema_is_set_to_3() {
		delete_option( Keys::OPT_SCHEMA );

		$this->upgrade()->maybe_upgrade();

		$this->assertSame( Keys::SCHEMA_VERSION, (int) get_option( Keys::OPT_SCHEMA ) );
	}

	public function test_current_schema_is_left_as_is() {
		update_option( Keys::OPT_SCHEMA, Keys::SCHEMA_VERSION );

		$this->upgrade()->maybe_upgrade();

		$this->assertSame( Keys::SCHEMA_VERSION, (int) get_option( Keys::OPT_SCHEMA ) );
	}
}
