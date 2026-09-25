<?php
/**
 * Implements SPEC.md §6.9: Publication_Item::get_the_thumbnail() passes the
 * thumbnail through Dam_Bridge::display_url() (D18), with the DAM loaded.
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA\Tests;

use WPPA\Keys;
use WPPA\Publication_Item;

/**
 * @group dam
 */
class Test_Publication_Item_Dam extends \WP_UnitTestCase {

	public function test_d18_embargoed_thumbnail_renders_placeholder_for_anonymous() {
		$attachment_id = self::factory()->attachment->create_upload_object( DIR_TESTDATA . '/images/canola.jpg' );
		$url           = (string) wp_get_attachment_url( $attachment_id );

		$editor = self::factory()->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $editor );

		\VIP\DAM\Abilities\Rights_Set::execute(
			array(
				'attachment_ids' => array( $attachment_id ),
				'rights'         => array( 'embargo_until' => gmdate( 'Y-m-d', time() + DAY_IN_SECONDS ) ),
			)
		);

		$post_id = self::factory()->post->create( array( 'post_type' => Keys::POST_TYPE ) );
		\WPPA\Tests\Fixtures\V3_Site::raw_meta( $post_id, Keys::META_IMAGE, $url );

		wp_set_current_user( 0 );

		$item = new Publication_Item( $post_id );

		$output = $item->get_the_thumbnail();

		$this->assertStringContainsString( \VIP\DAM\Embargo_Guard::placeholder_url(), $output );
		$this->assertStringNotContainsString( $url, $output );
	}

	public function test_d18_the_thumbnail_echoes_placeholder_for_anonymous() {
		$attachment_id = self::factory()->attachment->create_upload_object( DIR_TESTDATA . '/images/canola.jpg' );
		$url           = (string) wp_get_attachment_url( $attachment_id );

		$editor = self::factory()->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $editor );

		\VIP\DAM\Abilities\Rights_Set::execute(
			array(
				'attachment_ids' => array( $attachment_id ),
				'rights'         => array( 'embargo_until' => gmdate( 'Y-m-d', time() + DAY_IN_SECONDS ) ),
			)
		);

		$post_id = self::factory()->post->create( array( 'post_type' => Keys::POST_TYPE ) );
		\WPPA\Tests\Fixtures\V3_Site::raw_meta( $post_id, Keys::META_IMAGE, $url );

		wp_set_current_user( 0 );

		$item = new Publication_Item( $post_id );

		ob_start();
		$item->the_thumbnail();
		$output = (string) ob_get_clean();

		$this->assertStringContainsString( \VIP\DAM\Embargo_Guard::placeholder_url(), $output );
		$this->assertStringNotContainsString( $url, $output );
	}
}
