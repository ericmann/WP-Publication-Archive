<?php
/**
 * Implements SPEC.md §8 Phase 0 item 4 and §6.1: the 3.0.1 post type and
 * taxonomy, registered verbatim except the menu icon (D16) and D12 (P2-03):
 * both are now exposed to REST and the block editor, with publication
 * capabilities. P2-04 registers the §5.1 meta keys for REST (D12 meta,
 * D1 via REST).
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA\Tests;

use WPPA\Keys;

class Test_Post_Type extends \WP_UnitTestCase {

	public function set_up() {
		parent::set_up();

		// WP_UnitTestCase's tear_down() calls unregister_all_meta_keys()
		// after every test, but this plugin only registers post meta once,
		// at 'init'. Re-register so REST meta tests see it regardless of
		// test order.
		\WPPA\Plugin::instance()->post_type()->register();
	}

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

	public function test_d12_rest_exposes_meta_to_editor_in_edit_context() {
		do_action( 'rest_api_init' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- reason: core hook, test-only.

		$id = self::factory()->post->create(
			array(
				'post_type'   => Keys::POST_TYPE,
				'post_status' => 'publish',
			)
		);

		update_post_meta( $id, Keys::META_DOC, home_url( '/doc.pdf' ) );

		$editor = self::factory()->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $editor );

		$request = new \WP_REST_Request( 'GET', '/wp/v2/' . Keys::REST_BASE . '/' . $id );
		$request->set_param( 'context', 'edit' );

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( home_url( '/doc.pdf' ), $data['meta'][ Keys::META_DOC ] );
	}

	public function test_d12_rest_hides_meta_from_anonymous() {
		do_action( 'rest_api_init' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- reason: core hook, test-only.

		$id = self::factory()->post->create(
			array(
				'post_type'   => Keys::POST_TYPE,
				'post_status' => 'publish',
			)
		);

		update_post_meta( $id, Keys::META_DOC, home_url( '/doc.pdf' ) );

		wp_set_current_user( 0 );

		$response = rest_get_server()->dispatch( new \WP_REST_Request( 'GET', '/wp/v2/' . Keys::REST_BASE . '/' . $id ) );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertArrayNotHasKey( Keys::META_DOC, $data['meta'] );
	}

	public function test_d1_rest_write_of_local_path_stores_empty_string() {
		do_action( 'rest_api_init' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- reason: core hook, test-only.

		$id = self::factory()->post->create( array( 'post_type' => Keys::POST_TYPE ) );

		$editor = self::factory()->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $editor );

		$request = new \WP_REST_Request( 'PUT', '/wp/v2/' . Keys::REST_BASE . '/' . $id );
		$request->set_body_params( array( 'meta' => array( Keys::META_DOC => '/etc/passwd' ) ) );

		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( '', get_post_meta( $id, Keys::META_DOC, true ) );
	}

	public function test_rest_write_of_same_site_url_is_kept() {
		do_action( 'rest_api_init' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- reason: core hook, test-only.

		$id = self::factory()->post->create( array( 'post_type' => Keys::POST_TYPE ) );

		$editor = self::factory()->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $editor );

		$url     = home_url( '/doc.pdf' );
		$request = new \WP_REST_Request( 'PUT', '/wp/v2/' . Keys::REST_BASE . '/' . $id );
		$request->set_body_params( array( 'meta' => array( Keys::META_DOC => $url ) ) );

		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( $url, get_post_meta( $id, Keys::META_DOC, true ) );
	}

	public function test_alternates_rest_write_sanitises_description_and_validates_url() {
		do_action( 'rest_api_init' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- reason: core hook, test-only.

		$id = self::factory()->post->create( array( 'post_type' => Keys::POST_TYPE ) );

		$editor = self::factory()->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $editor );

		$request = new \WP_REST_Request( 'PUT', '/wp/v2/' . Keys::REST_BASE . '/' . $id );
		$request->set_body_params(
			array(
				'meta' => array(
					Keys::META_ALTERNATES => array(
						array(
							'description' => '<b>Alt</b>',
							'url'         => '/etc/passwd',
						),
					),
				),
			)
		);

		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );

		$stored = get_post_meta( $id, Keys::META_ALTERNATES, false );

		$this->assertSame( 'Alt', $stored[0]['description'] );
		$this->assertSame( '', $stored[0]['url'] );
	}

	public function test_rest_meta_write_denied_without_edit_post() {
		do_action( 'rest_api_init' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- reason: core hook, test-only.

		$id = self::factory()->post->create( array( 'post_type' => Keys::POST_TYPE ) );

		$subscriber = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $subscriber );

		$request = new \WP_REST_Request( 'PUT', '/wp/v2/' . Keys::REST_BASE . '/' . $id );
		$request->set_body_params( array( 'meta' => array( Keys::META_DOC => home_url( '/doc.pdf' ) ) ) );

		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 403, $response->get_status() );
	}

	public function test_raw_301_pipe_value_is_untouched_until_written() {
		global $wpdb;

		$id = self::factory()->post->create( array( 'post_type' => Keys::POST_TYPE ) );

		// 3.0.1 stored these as e.g. "http|example.com/doc.pdf"
		// (Url_Policy::normalise()). register_post_meta()'s sanitize_callback
		// only runs on write (update_post_meta()/REST), so a value already in
		// the DB in that pipe form — e.g. from before this plugin registered
		// the key, or migrated data — is untouched until something writes it
		// again. Insert directly via $wpdb to bypass the sanitize_callback and
		// prove the read side alone does not touch it.
		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- reason: test-only, direct insert bypassing register_post_meta()'s sanitize_callback on purpose.
			$wpdb->postmeta,
			array(
				'post_id'    => $id,
				'meta_key'   => Keys::META_DOC, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- reason: test-only, direct insert bypassing register_post_meta()'s sanitize_callback on purpose.
				'meta_value' => 'http|example.com/doc.pdf', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- reason: test-only, see above.
			)
		);

		// A direct $wpdb write bypasses update_post_meta()'s cache
		// invalidation. The post's meta cache may already be primed (e.g.
		// warmed empty by something reading meta right after
		// factory()->post->create()), so clear it before reading back —
		// otherwise this test reflects the object cache, not the DB.
		wp_cache_delete( $id, 'post_meta' );

		$this->assertSame( 'http|example.com/doc.pdf', get_post_meta( $id, Keys::META_DOC, true ) );
	}
}
