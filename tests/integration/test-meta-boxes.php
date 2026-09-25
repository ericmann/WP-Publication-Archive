<?php
/**
 * Implements SPEC.md §8 Phase 0 item 4 and §6.2: the three 3.0.1 publication
 * meta boxes and save_meta(). P1-04 closes D1 (save), D2 (save), D3 (admin)
 * and D10 here; D13 stays open until P2-07.
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA\Tests;

use WPPA\Keys;
use WPPA\Plugin;
use WPPA\Tests\Fixtures\V3_Site;

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

	public function test_d1_save_stores_empty_string_for_local_path_doc() {
		$id = self::factory()->post->create( array( 'post_type' => Keys::POST_TYPE ) );

		$_POST[ Keys::FIELD_NONCE ] = wp_create_nonce( Keys::NONCE_ACTION );
		$_POST[ Keys::FIELD_DOC ]   = '/etc/passwd';

		Plugin::instance()->meta_boxes()->save( $id );

		$this->assertSame( '', get_post_meta( $id, Keys::META_DOC, true ) );

		unset( $_POST[ Keys::FIELD_NONCE ], $_POST[ Keys::FIELD_DOC ] );
	}

	public function test_d1_save_stores_empty_string_for_javascript_image() {
		$id = self::factory()->post->create( array( 'post_type' => Keys::POST_TYPE ) );

		$_POST[ Keys::FIELD_NONCE ] = wp_create_nonce( Keys::NONCE_ACTION );
		$_POST[ Keys::FIELD_IMAGE ] = 'javascript:alert(1)';

		Plugin::instance()->meta_boxes()->save( $id );

		$this->assertSame( '', get_post_meta( $id, Keys::META_IMAGE, true ) );

		unset( $_POST[ Keys::FIELD_NONCE ], $_POST[ Keys::FIELD_IMAGE ] );
	}

	public function test_d1_save_keeps_same_site_url() {
		$id = self::factory()->post->create( array( 'post_type' => Keys::POST_TYPE ) );

		$same_site_url = home_url( '/wp-content/uploads/a.pdf' );

		$_POST[ Keys::FIELD_NONCE ] = wp_create_nonce( Keys::NONCE_ACTION );
		$_POST[ Keys::FIELD_DOC ]   = $same_site_url;

		Plugin::instance()->meta_boxes()->save( $id );

		$this->assertSame( $same_site_url, get_post_meta( $id, Keys::META_DOC, true ) );

		unset( $_POST[ Keys::FIELD_NONCE ], $_POST[ Keys::FIELD_DOC ] );
	}

	public function test_d1_save_normalises_pipe_form() {
		$id = self::factory()->post->create( array( 'post_type' => Keys::POST_TYPE ) );

		$_POST[ Keys::FIELD_NONCE ] = wp_create_nonce( Keys::NONCE_ACTION );
		$_POST[ Keys::FIELD_DOC ]   = 'https|example.com/a.pdf';

		Plugin::instance()->meta_boxes()->save( $id );

		$this->assertSame( 'https://example.com/a.pdf', get_post_meta( $id, Keys::META_DOC, true ) );

		unset( $_POST[ Keys::FIELD_NONCE ], $_POST[ Keys::FIELD_DOC ] );
	}

	public function test_save_preserves_percent_encoded_urls() {
		$id = self::factory()->post->create( array( 'post_type' => Keys::POST_TYPE ) );

		$_POST[ Keys::FIELD_NONCE ]      = wp_create_nonce( Keys::NONCE_ACTION );
		$_POST[ Keys::FIELD_DOC ]        = 'https://example.com/My%20Report.pdf';
		$_POST[ Keys::FIELD_IMAGE ]      = 'https://example.com/r%C3%A9sum%C3%A9.png';
		$_POST[ Keys::FIELD_ALTERNATES ] = array(
			'description' => array( 'English' ),
			'url'         => array( 'https://example.com/a.pdf?x=a%2Fb' ),
		);

		Plugin::instance()->meta_boxes()->save( $id );

		$this->assertSame( 'https://example.com/My%20Report.pdf', get_post_meta( $id, Keys::META_DOC, true ) );
		$this->assertSame( 'https://example.com/r%C3%A9sum%C3%A9.png', get_post_meta( $id, Keys::META_IMAGE, true ) );

		$alternates = get_post_meta( $id, Keys::META_ALTERNATES );

		$this->assertCount( 1, $alternates );
		$this->assertSame( 'https://example.com/a.pdf?x=a%2Fb', $alternates[0]['url'] );

		unset( $_POST[ Keys::FIELD_NONCE ], $_POST[ Keys::FIELD_DOC ], $_POST[ Keys::FIELD_IMAGE ], $_POST[ Keys::FIELD_ALTERNATES ] );
	}

	public function test_save_preserves_ampersands_in_urls() {
		$id = self::factory()->post->create( array( 'post_type' => Keys::POST_TYPE ) );

		$_POST[ Keys::FIELD_NONCE ]      = wp_create_nonce( Keys::NONCE_ACTION );
		$_POST[ Keys::FIELD_DOC ]        = 'https://example.com/a.pdf?x=1&y=2';
		$_POST[ Keys::FIELD_IMAGE ]      = 'https://example.com/t.png?w=1&h=2';
		$_POST[ Keys::FIELD_ALTERNATES ] = array(
			'description' => array( 'English' ),
			'url'         => array( 'https://example.com/b.pdf?id=3&export=download' ),
		);

		Plugin::instance()->meta_boxes()->save( $id );

		$this->assertSame( 'https://example.com/a.pdf?x=1&y=2', get_post_meta( $id, Keys::META_DOC, true ) );
		$this->assertSame( 'https://example.com/t.png?w=1&h=2', get_post_meta( $id, Keys::META_IMAGE, true ) );

		$alternates = get_post_meta( $id, Keys::META_ALTERNATES );

		$this->assertCount( 1, $alternates );
		$this->assertSame( 'https://example.com/b.pdf?id=3&export=download', $alternates[0]['url'] );

		unset( $_POST[ Keys::FIELD_NONCE ], $_POST[ Keys::FIELD_DOC ], $_POST[ Keys::FIELD_IMAGE ], $_POST[ Keys::FIELD_ALTERNATES ] );
	}

	public function test_d2_save_strips_markup_from_alternate_description() {
		$id = self::factory()->post->create( array( 'post_type' => Keys::POST_TYPE ) );

		$_POST[ Keys::FIELD_NONCE ]      = wp_create_nonce( Keys::NONCE_ACTION );
		$_POST[ Keys::FIELD_ALTERNATES ] = array(
			'description' => array( '<script>alert(1)</script>English' ),
			'url'         => array( 'https://example.com/a.pdf' ),
		);

		Plugin::instance()->meta_boxes()->save( $id );

		$alternates = get_post_meta( $id, Keys::META_ALTERNATES );

		$this->assertCount( 1, $alternates );
		$this->assertSame( 'English', $alternates[0]['description'] );
		$this->assertStringNotContainsString( '<script>', $alternates[0]['description'] );
		$this->assertStringNotContainsString( 'alert(1)', $alternates[0]['description'] );

		unset( $_POST[ Keys::FIELD_NONCE ], $_POST[ Keys::FIELD_ALTERNATES ] );
	}

	public function test_d10_saving_two_alternates_stores_two_rows_without_warning() {
		$id = self::factory()->post->create( array( 'post_type' => Keys::POST_TYPE ) );

		$_POST[ Keys::FIELD_NONCE ]      = wp_create_nonce( Keys::NONCE_ACTION );
		$_POST[ Keys::FIELD_ALTERNATES ] = array(
			'description' => array( 'English', 'French' ),
			'url'         => array( 'https://example.com/en.pdf', 'https://example.com/fr.pdf' ),
		);

		Plugin::instance()->meta_boxes()->save( $id );

		$alternates = get_post_meta( $id, Keys::META_ALTERNATES );

		$this->assertCount( 2, $alternates );
		$this->assertSame( 'https://example.com/en.pdf', $alternates[0]['url'] );
		$this->assertSame( 'https://example.com/fr.pdf', $alternates[1]['url'] );

		unset( $_POST[ Keys::FIELD_NONCE ], $_POST[ Keys::FIELD_ALTERNATES ] );
	}

	public function test_d3_doc_box_escapes_stored_value() {
		$id = self::factory()->post->create( array( 'post_type' => Keys::POST_TYPE ) );

		V3_Site::raw_meta( $id, Keys::META_DOC, '"><script>x</script>' );

		ob_start();
		Plugin::instance()->meta_boxes()->render_doc( get_post( $id ) );
		$output = ob_get_clean();

		$this->assertStringContainsString( '&quot;&gt;&lt;script', $output );
		$this->assertStringNotContainsString( '"><script', $output );
	}

	public function test_d3_thumb_box_escapes_stored_value() {
		$id = self::factory()->post->create( array( 'post_type' => Keys::POST_TYPE ) );

		V3_Site::raw_meta( $id, Keys::META_IMAGE, '"><script>x</script>' );

		ob_start();
		Plugin::instance()->meta_boxes()->render_thumb( get_post( $id ) );
		$output = ob_get_clean();

		$this->assertStringContainsString( '&quot;&gt;&lt;script', $output );
		$this->assertStringNotContainsString( '"><script', $output );
	}
}
