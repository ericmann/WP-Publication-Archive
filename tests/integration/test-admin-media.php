<?php
/**
 * Implements SPEC.md §8 Phase 0 item 4, §6.6 and §6.7: D13 closes here.
 * assets/js/admin-media.js and Assets::enqueue_admin() replace Thickbox and
 * the meta boxes' inline scripts.
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA\Tests;

use WPPA\Keys;
use WPPA\Plugin;

class Test_Admin_Media extends \WP_UnitTestCase {

	public function tear_down() {
		wp_dequeue_script( Keys::ADMIN_SCRIPT_HANDLE );
		wp_deregister_script( Keys::ADMIN_SCRIPT_HANDLE );
		wp_dequeue_script( 'media-upload' );
		wp_dequeue_script( 'thickbox' );
		wp_dequeue_style( 'thickbox' );
		set_current_screen( 'front' );

		parent::tear_down();
	}

	public function test_d13_script_enqueued_on_publication_edit_screen() {
		set_current_screen( Keys::POST_TYPE );

		Plugin::instance()->assets()->enqueue_admin( 'post.php' );

		$this->assertTrue( wp_script_is( Keys::ADMIN_SCRIPT_HANDLE, 'enqueued' ) );

		$registered = wp_scripts()->registered[ Keys::ADMIN_SCRIPT_HANDLE ] ?? null;
		$this->assertNotNull( $registered );
		$this->assertContains( 'media-editor', $registered->deps );
	}

	public function test_d13_script_not_enqueued_on_post_edit_screen() {
		set_current_screen( 'post' );

		Plugin::instance()->assets()->enqueue_admin( 'post.php' );

		$this->assertFalse( wp_script_is( Keys::ADMIN_SCRIPT_HANDLE, 'enqueued' ) );
	}

	public function test_d13_thickbox_not_enqueued() {
		set_current_screen( Keys::POST_TYPE );

		Plugin::instance()->assets()->enqueue_admin( 'post.php' );

		$this->assertFalse( wp_script_is( 'media-upload', 'enqueued' ) );
		$this->assertFalse( wp_script_is( 'thickbox', 'enqueued' ) );
		$this->assertFalse( wp_style_is( 'thickbox', 'enqueued' ) );
	}

	public function test_d13_meta_box_markup_has_no_script_or_thickbox() {
		$id = self::factory()->post->create( array( 'post_type' => Keys::POST_TYPE ) );
		$post = get_post( $id );

		ob_start();
		Plugin::instance()->meta_boxes()->render_doc( $post );
		Plugin::instance()->meta_boxes()->render_thumb( $post );
		Plugin::instance()->meta_boxes()->render_alternates( $post );
		$output = ob_get_clean();

		$this->assertStringNotContainsString( '<script', $output );
		$this->assertStringNotContainsString( 'TB_iframe', $output );
	}

	public function test_meta_box_ids_and_field_names_unchanged() {
		$id = self::factory()->post->create( array( 'post_type' => Keys::POST_TYPE ) );
		$post = get_post( $id );

		ob_start();
		Plugin::instance()->meta_boxes()->render_doc( $post );
		$doc_output = ob_get_clean();

		ob_start();
		Plugin::instance()->meta_boxes()->render_thumb( $post );
		$thumb_output = ob_get_clean();

		ob_start();
		Plugin::instance()->meta_boxes()->render_alternates( $post );
		$alternates_output = ob_get_clean();

		$this->assertStringContainsString( 'id="upload_doc_button"', $doc_output );
		$this->assertStringContainsString( 'name="' . Keys::FIELD_DOC . '"', $doc_output );

		$this->assertStringContainsString( 'id="wpa-upload_image_button"', $thumb_output );
		$this->assertStringContainsString( 'name="' . Keys::FIELD_IMAGE . '"', $thumb_output );

		$this->assertStringContainsString( 'class="wpa-upload-row"', $alternates_output );
		$this->assertStringContainsString( 'class="wpa-delete-row"', $alternates_output );
		$this->assertStringContainsString( 'id="wpa-alternates-button"', $alternates_output );
		$this->assertStringContainsString( 'id="wpa-alternate-table"', $alternates_output );
		$this->assertStringContainsString( 'name="' . Keys::FIELD_ALTERNATES . '[description][]"', $alternates_output );
		$this->assertStringContainsString( 'name="' . Keys::FIELD_ALTERNATES . '[url][]"', $alternates_output );
	}

	public function test_admin_media_js_never_touches_send_to_editor() {
		$path = WP_PUB_ARCH_DIR . Keys::ADMIN_SCRIPT_PATH;
		// phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown -- reason: local source file, not remote data.
		$contents = file_get_contents( $path );

		$this->assertNotFalse( $contents );
		$this->assertStringNotContainsString( 'send_to_editor', $contents );
		$this->assertStringNotContainsString( 'tb_show', $contents );
		$this->assertStringNotContainsString( 'TB_iframe', $contents );
	}

	public function test_admin_media_js_uses_jquery_only_for_document_delegation() {
		$path = WP_PUB_ARCH_DIR . Keys::ADMIN_SCRIPT_PATH;
		// phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown -- reason: local source file, not remote data.
		$contents = file_get_contents( $path );

		$this->assertNotFalse( $contents );

		$matches = array();
		preg_match_all( '/(?:\$|jQuery)\(\s*([^)]*?)\s*\)/', (string) $contents, $matches );

		$this->assertNotEmpty( $matches[1] );

		foreach ( $matches[1] as $argument ) {
			$this->assertSame( 'document', $argument );
		}
	}
}
