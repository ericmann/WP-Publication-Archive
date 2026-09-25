<?php
/**
 * Implements SPEC.md §8 Phase 0 item 4: 3.0.1's category dropdown/list
 * helpers, scoped to publications.
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA\Tests;

use WPPA\Categories;
use WPPA\Keys;
use WPPA\Tests\Fixtures\V3_Site;

class Test_Categories extends \WP_UnitTestCase {

	public function tear_down() {
		global $wp_rewrite;
		$wp_rewrite->remove_permastruct( 'category' );

		parent::tear_down();
	}

	public function test_dropdown_categories_lists_only_publication_categories() {
		$data = V3_Site::create( self::factory() );

		$other_post = self::factory()->post->create( array( 'post_type' => 'post' ) );
		wp_set_post_terms( $other_post, array( $data['category_id'] ), 'category' );

		$categories = \WPPA\Plugin::instance()->categories();

		// The default 'name' argument (the select field name) collides with
		// get_terms()'s own 'name' filter arg, which only matches terms with
		// that exact name (3.0.1 behaviour, preserved verbatim: see the
		// pinned WIDGET_CAT_COUNT_DROPDOWN fixture, which has no Reports
		// option for exactly this reason). Passing '' disables that filter
		// and isolates the post-type scoping this test targets.
		$output = $categories->dropdown_categories(
			array(
				'echo' => 0,
				'name' => '',
			)
		);

		$this->assertStringContainsString( 'Reports', $output );
	}

	public function test_list_categories_scopes_term_link_only_during_the_call() {
		$data = V3_Site::create( self::factory() );

		// The built-in 'category' taxonomy's permastruct was only registered
		// once, at bootstrap, under this test install's default plain
		// permalinks; set_permalink_structure() doesn't re-add it (and
		// re-running create_initial_taxonomies() would drop 'publication'
		// from category's object types). Add the permastruct directly, and
		// remove it again in tear_down() so it doesn't leak into other
		// tests' plain-permalink expectations.
		$this->set_permalink_structure( '/%postname%/' );
		$wp_rewrite = $GLOBALS['wp_rewrite'];
		$wp_rewrite->add_permastruct( 'category', $wp_rewrite->front . 'category/%category%', array( 'hierarchical' => true ) );
		flush_rewrite_rules( false ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.flush_rewrite_rules_flush_rewrite_rules -- reason: test-only, needs this site's own rewrite rules.

		$categories = \WPPA\Plugin::instance()->categories();

		$output = $categories->list_categories( array( 'echo' => 0 ) );

		$this->assertStringContainsString( '/' . Keys::REWRITE_BASE . '/' . Keys::REWRITE_CATEGORY . '/', $output );

		// term_link is un-hijacked once list_categories() returns.
		$link = get_term_link( (int) $data['category_id'], 'category' );
		$this->assertIsString( $link );
		$this->assertStringNotContainsString( '/' . Keys::REWRITE_BASE . '/' . Keys::REWRITE_CATEGORY . '/', $link );
	}

	public function test_get_terms_scopes_terms_clauses_to_publication_post_type() {
		$data = V3_Site::create( self::factory() );

		$categories = new Categories( \WPPA\Plugin::instance()->flags() );

		$terms = $categories->get_terms(
			'category',
			array(
				'post_types' => array( Keys::POST_TYPE ),
				'hide_empty' => true,
			)
		);

		$this->assertIsArray( $terms );

		$found = false;
		foreach ( $terms as $term ) {
			if ( (int) $term->term_id === (int) $data['category_id'] ) {
				$found = true;
			}
		}
		$this->assertTrue( $found );
	}

	public function test_get_terms_without_post_types_falls_back_to_core() {
		$categories = new Categories( \WPPA\Plugin::instance()->flags() );

		$terms = $categories->get_terms( 'category', array( 'hide_empty' => false ) );

		$this->assertIsArray( $terms );
	}
}
