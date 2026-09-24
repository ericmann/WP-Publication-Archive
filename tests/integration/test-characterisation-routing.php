<?php
/**
 * Implements SPEC.md §8 Phase 0 item 3: pins 3.0.1 routing and link
 * generation before anything moves. These tests must pass unchanged through
 * P0-15, so they touch plugin behaviour only through the 3.0.1 public API
 * (WP_Publication_Archive's static methods), do_shortcode(), the_widget()
 * with the 3.0.1 class names, core functions and Keys.
 *
 * Each test method gets its own set_up(), because a single WP_UnitTestCase
 * test method that calls go_to() more than once observes stale query vars
 * from the first request: WP's test go_to() rebuilds $wp_query but the
 * publication CPT's rewrite permastruct is bound at the 'init' hook that
 * ran once for the whole process, before set_permalink_structure() changed
 * anything, so this plugin's own register_publication() and
 * custom_rewrites() are re-run in set_up() against the fresh $wp_rewrite
 * instance each test gets.
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA\Tests;

use WPPA\Keys;
use WPPA\Tests\Fixtures\V3_Site;

class Test_Characterisation_Routing extends \WP_UnitTestCase {

	/** @var array<string, mixed> */
	private $data;

	public function set_up() {
		parent::set_up();

		$this->data = V3_Site::create( self::factory() );
		V3_Site::reset_link_state();

		$this->set_permalink_structure( '/%postname%/' );
		\WP_Publication_Archive::register_publication();
		\WP_Publication_Archive::custom_rewrites();
		flush_rewrite_rules( false ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.flush_rewrite_rules_flush_rewrite_rules -- reason: test-only, characterisation set_up() needs this site's own rewrite rules.
	}

	public function test_single_publication_url_resolves() {
		$this->go_to( get_permalink( $this->data['attached'] ) );

		$this->assertTrue( is_singular( Keys::POST_TYPE ) );
		$this->assertSame( $this->data['attached'], get_queried_object_id() );
	}

	public function test_view_url_sets_open_query_var() {
		$this->go_to( \WP_Publication_Archive::get_open_link( $this->data['attached'] ) );

		global $wp_query;
		$this->assertSame( 'attached-report', $wp_query->query_vars['publication'] );
		$this->assertSame( 'yes', $wp_query->query_vars['wppa_open'] );
	}

	public function test_download_url_sets_download_query_var() {
		$this->go_to( \WP_Publication_Archive::get_download_link( $this->data['attached'] ) );

		global $wp_query;
		$this->assertSame( 'attached-report', $wp_query->query_vars['publication'] );
		$this->assertSame( 'yes', $wp_query->query_vars['wppa_download'] );
	}

	public function test_altview_url_sets_open_and_alt() {
		$this->go_to( \WP_Publication_Archive::get_alternate_open_link( $this->data['alternates'], 'English' ) );

		global $wp_query;
		$this->assertSame( 'alternates-report', $wp_query->query_vars['publication'] );
		$this->assertSame( 'yes', $wp_query->query_vars['wppa_open'] );
		$this->assertSame( 'English', $wp_query->query_vars['wppa_alt'] );
	}

	public function test_altdown_url_sets_download_and_alt() {
		$this->go_to( \WP_Publication_Archive::get_alternate_download_link( $this->data['alternates'], 'English' ) );

		global $wp_query;
		$this->assertSame( 'alternates-report', $wp_query->query_vars['publication'] );
		$this->assertSame( 'yes', $wp_query->query_vars['wppa_download'] );
		$this->assertSame( 'English', $wp_query->query_vars['wppa_alt'] );
	}

	public function test_category_url_lists_publications_in_category() {
		$this->go_to( site_url( '/publication/category/reports/' ) );

		global $wp_query;
		$this->assertSame( Keys::POST_TYPE, $wp_query->query_vars['post_type'] );
		$this->assertSame( 'reports', $wp_query->query_vars['category_name'] );
		$this->assertSame( 1, $wp_query->found_posts );
		$this->assertSame( $this->data['categorised'], $wp_query->posts[0]->ID );
	}

	public function test_open_query_form_resolves() {
		$this->go_to( add_query_arg( array( 'publication' => 'attached-report', Keys::QV_OPEN => 'yes' ), home_url( '/' ) ) );

		global $wp_query;
		$this->assertSame( 'yes', $wp_query->query_vars['wppa_open'] );
		$this->assertSame( $this->data['attached'], get_queried_object_id() );
	}

	public function test_download_query_form_resolves() {
		$this->go_to( add_query_arg( array( 'publication' => 'attached-report', Keys::QV_DOWNLOAD => 'yes' ), home_url( '/' ) ) );

		global $wp_query;
		$this->assertSame( 'yes', $wp_query->query_vars['wppa_download'] );
		$this->assertSame( $this->data['attached'], get_queried_object_id() );
	}

	/**
	 * Pin: requesting the bare endpoint path with nothing after it does not
	 * match the endpoint rewrite rule (which requires a further [^/]+
	 * segment). It falls through to the CPT's own single-post rule, so
	 * "/publication/view/" opens the publication whose slug is literally
	 * "view" rather than being read as the view endpoint with no slug.
	 * P2-05 (D5) depends on this observed result.
	 */
	public function test_slug_view_publication_at_publication_view() {
		$this->go_to( site_url( '/publication/view/' ) );

		global $wp_query;
		$this->assertSame( 'view', $wp_query->query_vars['publication'] );
		$this->assertArrayNotHasKey( 'wppa_open', $wp_query->query_vars );
		$this->assertSame( $this->data['slug_view'], get_queried_object_id() );
	}

	public function test_slug_download_publication_at_publication_download() {
		$this->go_to( site_url( '/publication/download/' ) );

		global $wp_query;
		$this->assertSame( 'download', $wp_query->query_vars['publication'] );
		$this->assertArrayNotHasKey( 'wppa_download', $wp_query->query_vars );
		$this->assertSame( $this->data['slug_download'], get_queried_object_id() );
	}

	public function test_view_endpoint_for_other_slug_opens_other_slug() {
		$this->go_to( site_url( '/publication/view/attached-report/' ) );

		global $wp_query;
		$this->assertSame( 'attached-report', $wp_query->query_vars['publication'] );
		$this->assertSame( 'yes', $wp_query->query_vars['wppa_open'] );
		$this->assertSame( $this->data['attached'], get_queried_object_id() );
	}

	public function test_open_link_matches_301() {
		$link = \WP_Publication_Archive::get_open_link( $this->data['attached'] );

		$this->assertSame( site_url() . '/publication/view/attached-report', $link );
	}

	public function test_download_link_matches_301() {
		$link = \WP_Publication_Archive::get_download_link( $this->data['attached'] );

		$this->assertSame( site_url() . '/publication/download/attached-report', $link );
	}

	public function test_alternate_open_link_matches_301() {
		$link = \WP_Publication_Archive::get_alternate_open_link( $this->data['alternates'], 'English' );

		$this->assertSame( site_url() . '/publication/altview/alternates-report/English', $link );
	}

	public function test_alternate_download_link_matches_301() {
		$link = \WP_Publication_Archive::get_alternate_download_link( $this->data['alternates'], 'English' );

		$this->assertSame( site_url() . '/publication/altdown/alternates-report/English', $link );
	}

	/**
	 * Pin (spec issues): with plain permalinks, get_link() appends the
	 * literal endpoint name ("view"), not Keys::QV_OPEN ("wppa_open"), as a
	 * query arg on the post's own (query-string) permalink.
	 */
	public function test_open_link_with_plain_permalinks_matches_301() {
		$this->set_permalink_structure( '' );

		$link = \WP_Publication_Archive::get_open_link( $this->data['attached'] );

		$this->assertSame( home_url( '/?publication=attached-report&view=yes' ), $link );
	}

	/**
	 * D8 pin: generating one publication's open link (with no explicit
	 * permalink) leaves the 'post_type_link' filter registered, because
	 * get_link() only removes it for the duration of its own get_permalink()
	 * call. Every later get_permalink() for any publication is hijacked by
	 * publication_link(), which passes the query-var name 'wppa_open'
	 * (Keys::QV_OPEN) as the endpoint, not 'view'.
	 */
	public function test_permalink_after_link_generation_matches_301() {
		\WP_Publication_Archive::get_open_link( $this->data['attached'] );

		$permalink = get_permalink( $this->data['pipe'] );

		$this->assertSame( site_url() . '/publication/' . Keys::QV_OPEN . '/pipe-report', $permalink );
	}
}
