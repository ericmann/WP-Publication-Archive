<?php
/**
 * Implements SPEC.md §8 Phase 0 item 4 and Decisions D8: Rewrites owns the
 * 3.0.1 rewrite rules, query vars and link generation, including the
 * post_type_link hijack.
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

	public function test_permalink_is_hijacked_after_link_generation_until_d8() {
		\WPPA\Plugin::instance()->rewrites()->open_link( $this->data['attached'] );

		$permalink = get_permalink( $this->data['pipe'] );

		$this->assertSame( site_url() . '/' . Keys::REWRITE_BASE . '/' . Keys::QV_OPEN . '/pipe-report', $permalink );
	}

	public function test_disarm_clears_the_hijack() {
		$rewrites = \WPPA\Plugin::instance()->rewrites();

		$rewrites->open_link( $this->data['attached'] );
		$rewrites->disarm();

		$permalink = get_permalink( $this->data['pipe'] );

		$this->assertSame( site_url() . '/publication/pipe-report/', $permalink );
	}
}
