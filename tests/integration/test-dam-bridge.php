<?php
/**
 * Implements SPEC.md §6.9: the bridge to the VIP Digital Asset Manager
 * (D17–D19).
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA\Tests;

use WPPA\Dam_Bridge;
use WPPA\Keys;
use WPPA\Plugin;
use WPPA\Tests\Fixtures\V3_Site;

class Test_Dam_Bridge extends \WP_UnitTestCase {

	public function test_is_final() {
		$reflection = new \ReflectionClass( Dam_Bridge::class );

		$this->assertTrue( $reflection->isFinal() );
	}

	public function test_attachment_id_for_resolves_same_site_url() {
		$data = V3_Site::create( self::factory() );

		$this->assertSame(
			(int) $data['attachment_id'],
			Plugin::instance()->dam_bridge()->attachment_id_for( $data['attachment_url'] )
		);
	}

	public function test_attachment_id_for_strips_size_suffix() {
		$data = V3_Site::create( self::factory() );

		$suffixed = preg_replace( '/(\.[a-zA-Z0-9]+)$/', '-150x150$1', $data['attachment_url'] );

		$this->assertSame(
			(int) $data['attachment_id'],
			Plugin::instance()->dam_bridge()->attachment_id_for( $suffixed )
		);
	}

	public function test_attachment_id_for_resolves_pipe_form() {
		$data = V3_Site::create( self::factory() );

		$pipe_url = get_post_meta( $data['pipe'], Keys::META_DOC, true );

		$this->assertStringStartsWith( 'https|', $pipe_url );
		$this->assertSame(
			(int) $data['pipe_attachment_id'],
			Plugin::instance()->dam_bridge()->attachment_id_for( $pipe_url )
		);
	}

	public function test_attachment_id_for_external_url_is_zero() {
		$this->assertSame( 0, Plugin::instance()->dam_bridge()->attachment_id_for( 'https://example.com/a.pdf' ) );
	}

	/**
	 * @group nodam
	 */
	public function test_d19_indexed_ids_unchanged_when_dam_inactive() {
		$dam_bridge = Plugin::instance()->dam_bridge();

		$this->assertFalse( $dam_bridge->active() );

		$data = V3_Site::create( self::factory() );

		$ids = array( 999 );

		$this->assertSame( $ids, $dam_bridge->indexed_attachment_ids( $ids, get_post( $data['alternates'] ) ) );
	}
}
