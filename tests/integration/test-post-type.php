<?php
/**
 * Implements SPEC.md §8 Phase 0 item 4: the 3.0.1 post type and taxonomy,
 * registered verbatim except the menu icon (D16).
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA\Tests;

use WPPA\Keys;

class Test_Post_Type extends \WP_UnitTestCase {

	public function test_publication_post_type_registered_with_301_args() {
		$post_type = get_post_type_object( Keys::POST_TYPE );

		$this->assertNotNull( $post_type );
		$this->assertTrue( $post_type->public );
		$this->assertTrue( $post_type->has_archive );
		$this->assertSame( 20, $post_type->menu_position );
		$this->assertTrue( post_type_supports( Keys::POST_TYPE, 'title' ) );
		$this->assertTrue( post_type_supports( Keys::POST_TYPE, 'editor' ) );
		$this->assertContains( 'category', get_object_taxonomies( Keys::POST_TYPE ) );
		$this->assertContains( 'post_tag', get_object_taxonomies( Keys::POST_TYPE ) );
		$this->assertSame( 'post', $post_type->capability_type );
	}

	public function test_d16_menu_icon_is_a_dashicon() {
		$post_type = get_post_type_object( Keys::POST_TYPE );

		$this->assertSame( Keys::MENU_ICON, $post_type->menu_icon );
		$this->assertStringStartsWith( 'dashicons-', $post_type->menu_icon );
	}

	public function test_author_taxonomy_registered_with_301_args() {
		$taxonomy = get_taxonomy( Keys::TAX_AUTHOR );

		$this->assertNotFalse( $taxonomy );
		$this->assertFalse( $taxonomy->query_var );
		$this->assertFalse( $taxonomy->rewrite );
	}
}
