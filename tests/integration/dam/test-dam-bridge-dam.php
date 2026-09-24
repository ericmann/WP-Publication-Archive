<?php
/**
 * Implements SPEC.md §6.9: the bridge to the VIP Digital Asset Manager
 * (D17–D19), with the DAM loaded.
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA\Tests;

use WPPA\Keys;
use WPPA\Plugin;
use WPPA\Tests\Fixtures\V3_Site;

/**
 * @group dam
 */
class Test_Dam_Bridge_Dam extends \WP_UnitTestCase {

	public function test_active_with_dam_loaded() {
		$this->assertTrue( Plugin::instance()->dam_bridge()->active() );
	}

	public function test_d19_indexed_ids_include_doc_image_and_alternates_once_each() {
		$doc_id   = self::factory()->attachment->create_upload_object( DIR_TESTDATA . '/images/wordpress-gsoc-flyer.pdf' );
		$image_id = self::factory()->attachment->create_upload_object( DIR_TESTDATA . '/images/canola.jpg' );
		$alt_id   = self::factory()->attachment->create_upload_object( DIR_TESTDATA . '/images/test-image.jpg' );

		$post_id = self::factory()->post->create( array( 'post_type' => Keys::POST_TYPE ) );

		V3_Site::raw_meta( $post_id, Keys::META_DOC, wp_get_attachment_url( $doc_id ) );
		V3_Site::raw_meta( $post_id, Keys::META_IMAGE, wp_get_attachment_url( $image_id ) );
		V3_Site::raw_meta(
			$post_id,
			Keys::META_ALTERNATES,
			array(
				'description' => 'Alt',
				'url'         => wp_get_attachment_url( $alt_id ),
			)
		);

		$ids = Plugin::instance()->dam_bridge()->indexed_attachment_ids( array(), get_post( $post_id ) );

		$this->assertEqualsCanonicalizing( array( $doc_id, $image_id, $alt_id ), $ids );
	}

	public function test_d19_other_post_types_untouched() {
		$post = self::factory()->post->create_and_get( array( 'post_type' => 'post' ) );

		$ids = array( 123, 456 );

		$this->assertSame( $ids, Plugin::instance()->dam_bridge()->indexed_attachment_ids( $ids, $post ) );
	}

	public function test_d19_usage_index_counts_pipe_form_publication() {
		$data = V3_Site::create( self::factory() );

		\VIP\DAM\Usage_Index::index_post( $data['pipe'] );

		$usage = \VIP\DAM\Usage_Index::get_usage( $data['pipe_attachment_id'] );

		$this->assertGreaterThan( 0, $usage['usage_count'] );
	}

	public function test_d19_dam_refuses_media_delete_of_attachment_a_publication_uses() {
		$data = V3_Site::create( self::factory() );

		\VIP\DAM\Usage_Index::index_post( $data['attached'] );

		$admin = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin );

		$result = \VIP\DAM\Abilities\Media_Delete::execute(
			array(
				'attachment_id' => $data['attachment_id'],
				'confirm'       => true,
			)
		);

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'asset_in_use', $result->get_error_code() );
	}
}
