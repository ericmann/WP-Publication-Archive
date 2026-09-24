<?php
/**
 * Implements SPEC.md §8 Phase 0 item 4: the three 3.0.1 publication meta
 * boxes and save_meta(), with D1, D2, D3, D10 and D13 preserved.
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA\Tests;

use WPPA\Keys;
use WPPA\Plugin;

class Test_Meta_Boxes extends \WP_UnitTestCase {

	public function test_three_meta_boxes_added_with_301_ids() {
		global $wp_meta_boxes;
		$wp_meta_boxes = array();

		Plugin::instance()->meta_boxes()->add();

		$ids = array();
		foreach ( $wp_meta_boxes[ Keys::POST_TYPE ]['normal']['high'] as $box ) {
			$ids[] = $box['id'];
		}

		$this->assertContains( Keys::META_BOX_DOC, $ids );
		$this->assertContains( Keys::META_BOX_ALTERNATES, $ids );
		$this->assertContains( Keys::META_BOX_THUMB, $ids );
	}

	public function test_doc_box_prints_field_with_301_id_and_name() {
		$id = self::factory()->post->create( array( 'post_type' => Keys::POST_TYPE ) );

		ob_start();
		Plugin::instance()->meta_boxes()->render_doc( get_post( $id ) );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'id="' . Keys::FIELD_DOC . '"', $output );
		$this->assertStringContainsString( 'name="' . Keys::FIELD_DOC . '"', $output );
	}

	public function test_save_requires_valid_nonce() {
		$id = self::factory()->post->create( array( 'post_type' => Keys::POST_TYPE ) );

		$_POST[ Keys::FIELD_DOC ] = 'https://example.com/a.pdf';

		Plugin::instance()->meta_boxes()->save( $id );

		$this->assertSame( '', get_post_meta( $id, Keys::META_DOC, true ) );

		unset( $_POST[ Keys::FIELD_DOC ] );
	}

	public function test_save_stores_doc_and_image_for_publications() {
		$id = self::factory()->post->create( array( 'post_type' => Keys::POST_TYPE ) );

		$_POST[ Keys::FIELD_NONCE ] = wp_create_nonce( Keys::NONCE_ACTION );
		$_POST[ Keys::FIELD_DOC ]   = 'https://example.com/a.pdf';
		$_POST[ Keys::FIELD_IMAGE ] = 'https://example.com/a.jpg';

		Plugin::instance()->meta_boxes()->save( $id );

		$this->assertSame( 'https://example.com/a.pdf', get_post_meta( $id, Keys::META_DOC, true ) );
		$this->assertSame( 'https://example.com/a.jpg', get_post_meta( $id, Keys::META_IMAGE, true ) );

		unset( $_POST[ Keys::FIELD_NONCE ], $_POST[ Keys::FIELD_DOC ], $_POST[ Keys::FIELD_IMAGE ] );
	}

	public function test_save_ignores_other_post_types() {
		$id = self::factory()->post->create( array( 'post_type' => 'post' ) );

		$_POST[ Keys::FIELD_NONCE ] = wp_create_nonce( Keys::NONCE_ACTION );
		$_POST[ Keys::FIELD_DOC ]   = 'https://example.com/a.pdf';

		Plugin::instance()->meta_boxes()->save( $id );

		$this->assertSame( '', get_post_meta( $id, Keys::META_DOC, true ) );

		unset( $_POST[ Keys::FIELD_NONCE ], $_POST[ Keys::FIELD_DOC ] );
	}

	/**
	 * DOING_AUTOSAVE is a constant: defining it here would leak into every
	 * other test in the same process, so this runs isolated.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_save_skips_autosave() {
		$id = self::factory()->post->create( array( 'post_type' => Keys::POST_TYPE ) );

		$_POST[ Keys::FIELD_NONCE ] = wp_create_nonce( Keys::NONCE_ACTION );
		$_POST[ Keys::FIELD_DOC ]   = 'https://example.com/a.pdf';

		if ( ! defined( 'DOING_AUTOSAVE' ) ) {
			define( 'DOING_AUTOSAVE', true );
		}

		Plugin::instance()->meta_boxes()->save( $id );

		$this->assertSame( '', get_post_meta( $id, Keys::META_DOC, true ) );

		unset( $_POST[ Keys::FIELD_NONCE ], $_POST[ Keys::FIELD_DOC ] );
	}
}
