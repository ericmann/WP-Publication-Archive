<?php
/**
 * Implements SPEC.md §8 Phase 0 item 4: Rewrites owns the 3.0.1 rewrite
 * rules, query vars and link generation. D8 (Decisions, P2-01) deleted the
 * dead post_type_link hijack.
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA\Tests;

use WPPA\FixedClock;
use WPPA\Flags;
use WPPA\Keys;
use WPPA\Rewrites;
use WPPA\Tests\Fixtures\V3_Site;

class Test_Rewrites extends \WP_UnitTestCase {

	/** @var array<string, mixed> */
	private $data;

	public function set_up() {
		parent::set_up();

		$this->data = V3_Site::create( self::factory() );

		$this->set_permalink_structure( '/%postname%/' );
		\WPPA\Plugin::instance()->post_type()->register();
		\WPPA\Plugin::instance()->rewrites()->register();
		flush_rewrite_rules( false ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.flush_rewrite_rules_flush_rewrite_rules -- reason: test-only, needs this site's own rewrite rules.

		V3_Site::reset_link_state();
	}

	public function test_eight_301_rules_registered_top() {
		global $wp_rewrite;

		$prefixes = array(
			'^' . Keys::REWRITE_BASE . '/' . Keys::ENDPOINT_DOWNLOAD . '/',
			'^' . Keys::REWRITE_BASE . '/' . Keys::ENDPOINT_VIEW . '/',
			'^' . Keys::REWRITE_BASE . '/' . Keys::ENDPOINT_ALTDOWN . '/',
			'^' . Keys::REWRITE_BASE . '/' . Keys::ENDPOINT_ALTVIEW . '/',
			'^' . Keys::REWRITE_BASE . '/' . Keys::REWRITE_CATEGORY . '/(.+?)/feed/',
			'^' . Keys::REWRITE_BASE . '/' . Keys::REWRITE_CATEGORY . '/(.+?)/(feed|rdf',
			'^' . Keys::REWRITE_BASE . '/' . Keys::REWRITE_CATEGORY . '/(.+?)/page/',
			'^' . Keys::REWRITE_BASE . '/' . Keys::REWRITE_CATEGORY . '/(.+?)/?$',
		);

		$keys = array_keys( $wp_rewrite->extra_rules_top );

		foreach ( $prefixes as $prefix ) {
			$found = false;

			foreach ( $keys as $key ) {
				if ( 0 === strpos( $key, $prefix ) ) {
					$found = true;
					break;
				}
			}

			$this->assertTrue( $found, 'Expected a top rule starting ' . $prefix );
		}
	}

	public function test_query_vars_include_paged() {
		$rewrites = \WPPA\Plugin::instance()->rewrites();

		$this->assertContains( Keys::QV_PAGED, $rewrites->query_vars( array() ) );
	}

	public function test_link_matches_301_with_pretty_permalinks() {
		$link = \WPPA\Plugin::instance()->rewrites()->open_link( $this->data['attached'] );

		$this->assertSame( site_url() . '/' . Keys::REWRITE_BASE . '/' . Keys::ENDPOINT_VIEW . '/attached-report', $link );
	}

	public function test_link_matches_301_with_plain_permalinks() {
		$this->set_permalink_structure( '' );

		$link = \WPPA\Plugin::instance()->rewrites()->open_link( $this->data['attached'] );

		$this->assertSame( home_url( '/?publication=attached-report&view=yes' ), $link );
	}

	/**
	 * D5: a publication whose slug is literally "view" is reachable at its
	 * own permalink. The endpoint rule '^publication/view/([^/]+)...'
	 * requires a further non-empty segment, so a bare "/publication/view/"
	 * request does not match it and falls through to the CPT's own
	 * single-post rule instead.
	 */
	public function test_d5_slug_view_publication_resolves_at_publication_view() {
		$this->go_to( site_url( '/publication/view/' ) );

		global $wp_query;
		$this->assertSame( 'view', $wp_query->query_vars['publication'] );
		$this->assertArrayNotHasKey( Keys::QV_OPEN, $wp_query->query_vars );
		$this->assertSame( $this->data['slug_view'], get_queried_object_id() );
	}

	public function test_d5_slug_download_publication_resolves_at_publication_download() {
		$this->go_to( site_url( '/publication/download/' ) );

		global $wp_query;
		$this->assertSame( 'download', $wp_query->query_vars['publication'] );
		$this->assertArrayNotHasKey( Keys::QV_DOWNLOAD, $wp_query->query_vars );
		$this->assertSame( $this->data['slug_download'], get_queried_object_id() );
	}

	public function test_d5_view_endpoint_for_other_slug_opens_other_slug() {
		$this->go_to( site_url( '/publication/view/attached-report/' ) );

		global $wp_query;
		$this->assertSame( 'attached-report', $wp_query->query_vars['publication'] );
		$this->assertSame( 'yes', $wp_query->query_vars[ Keys::QV_OPEN ] );
		$this->assertSame( $this->data['attached'], get_queried_object_id() );
	}

	public function test_d5_download_endpoint_for_slug_view_opens_view() {
		$this->go_to( site_url( '/publication/download/view/' ) );

		global $wp_query;
		$this->assertSame( 'view', $wp_query->query_vars['publication'] );
		$this->assertSame( 'yes', $wp_query->query_vars[ Keys::QV_DOWNLOAD ] );
		$this->assertSame( $this->data['slug_view'], get_queried_object_id() );
	}

}
