<?php
/**
 * Implements SPEC.md §8 Phase 0 item 4 and §6.1: the 3.0.1 post type and
 * taxonomy, registered verbatim except the menu icon (D16) and D12 (P2-03):
 * both are now exposed to REST and the block editor, with publication
 * capabilities.
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
	}

	public function test_d16_menu_icon_is_a_dashicon() {
		$post_type = get_post_type_object( Keys::POST_TYPE );

		$this->assertSame( Keys::MENU_ICON, $post_type->menu_icon );
		$this->assertStringStartsWith( 'dashicons-', $post_type->menu_icon );
	}

	public function test_author_taxonomy_registered_with_301_args() {
		$taxonomy = get_taxonomy( Keys::TAX_AUTHOR );

		$this->assertNotFalse( $taxonomy );
	}

	public function test_d12_post_type_shows_in_rest_with_rest_base() {
		$post_type = get_post_type_object( Keys::POST_TYPE );

		$this->assertNotNull( $post_type );
		$this->assertTrue( $post_type->show_in_rest );
		$this->assertSame( Keys::REST_BASE, $post_type->rest_base );
	}

	public function test_d12_author_taxonomy_shows_in_rest() {
		$taxonomy = get_taxonomy( Keys::TAX_AUTHOR );

		$this->assertNotFalse( $taxonomy );
		$this->assertTrue( $taxonomy->show_in_rest );
	}

	public function test_capability_type_is_publication_with_map_meta_cap() {
		$post_type = get_post_type_object( Keys::POST_TYPE );

		$this->assertNotNull( $post_type );
		$this->assertSame( Keys::CAPABILITY_TYPE[0], $post_type->capability_type );
		$this->assertTrue( $post_type->map_meta_cap );
		$this->assertSame( 'edit_publications', $post_type->cap->edit_posts );
	}

	public function test_author_taxonomy_rewrite_slug_and_query_var() {
		$taxonomy = get_taxonomy( Keys::TAX_AUTHOR );

		$this->assertNotFalse( $taxonomy );
		$this->assertSame( Keys::TAX_AUTHOR_QUERY_VAR, $taxonomy->query_var );
		$this->assertIsArray( $taxonomy->rewrite );
		$this->assertSame( Keys::TAX_AUTHOR_REWRITE_SLUG, $taxonomy->rewrite['slug'] );
	}

	public function test_d12_rest_lists_fixture_publications() {
		do_action( 'rest_api_init' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- reason: core hook, test-only.

		$id = self::factory()->post->create(
			array(
				'post_type'   => Keys::POST_TYPE,
				'post_status' => 'publish',
				'post_title'  => 'A REST-visible publication',
			)
		);

		$editor = self::factory()->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $editor );

		$response = rest_get_server()->dispatch( new \WP_REST_Request( 'GET', '/wp/v2/' . Keys::REST_BASE ) );

		$this->assertSame( 200, $response->get_status() );

		$ids = wp_list_pluck( $response->get_data(), 'id' );
		$this->assertContains( $id, $ids );
	}

	public function test_query_matches_301_query_publications_defaults() {
		$post_type = \WPPA\Plugin::instance()->post_type();

		$query = $post_type->query( array() );

		$this->assertInstanceOf( \WP_Query::class, $query );
		$this->assertSame( -1, $query->get( 'posts_per_page' ) );
		$this->assertSame( 'ASC', $query->get( 'order' ) );
		$this->assertSame( 'menu_order', $query->get( 'orderby' ) );
		$this->assertSame( Keys::POST_TYPE, $query->get( 'post_type' ) );
	}

	public function test_query_forces_post_type_even_when_overridden() {
		$post_type = \WPPA\Plugin::instance()->post_type();

		$query = $post_type->query( array( 'post_type' => 'post' ) );

		$this->assertSame( Keys::POST_TYPE, $query->get( 'post_type' ) );
	}
}
