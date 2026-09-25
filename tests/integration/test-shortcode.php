<?php
/**
 * Implements SPEC.md §8 Phase 0 item 4: the 3.0.1 [wp-publication-archive]
 * shortcode, closing D15 (no extract()).
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA\Tests;

use WPPA\Keys;
use WPPA\Tests\Fixtures\V3_Expected_Output;
use WPPA\Tests\Fixtures\V3_Site;

class Test_Shortcode extends \WP_UnitTestCase {

	/** @var array<string, mixed> */
	private $data;

	public function set_up() {
		parent::set_up();

		$this->data = V3_Site::create( self::factory() );
		V3_Site::reset_link_state();

		$this->set_permalink_structure( '/%postname%/' );
		\WPPA\Plugin::instance()->post_type()->register();
		\WPPA\Plugin::instance()->rewrites()->register();
		flush_rewrite_rules( false ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.flush_rewrite_rules_flush_rewrite_rules -- reason: test-only, needs this site's own rewrite rules.
	}

	public function tear_down() {
		remove_all_filters( Keys::FILTER_LIST_LIMIT );
		remove_all_filters( Keys::FILTER_PUBS_PER_PAGE );
		remove_all_filters( Keys::FILTER_LIST_CONTAINER );

		parent::tear_down();
	}

	public function test_shortcode_registered_with_301_tag() {
		$this->assertTrue( shortcode_exists( Keys::SHORTCODE ) );
	}

	public function test_list_uses_bundled_template() {
		$output = do_shortcode( '[' . Keys::SHORTCODE . ']' );

		$this->assertStringContainsString( 'publication-archive', $output );
		$this->assertStringContainsString( 'single-publication', $output );
	}

	public function test_container_filter_applies() {
		$seen = null;

		add_filter(
			Keys::FILTER_LIST_CONTAINER,
			static function ( $container ) use ( &$seen ) {
				$seen = $container;

				return $container;
			}
		);

		do_shortcode( '[' . Keys::SHORTCODE . ']' );

		$this->assertIsArray( $seen );
		$this->assertArrayHasKey( 'publications', $seen );
		$this->assertArrayHasKey( 'total_pubs', $seen );
	}

	public function test_limit_filters_apply_in_301_order() {
		$order = array();

		add_filter(
			Keys::FILTER_PUBS_PER_PAGE,
			static function ( $limit ) use ( &$order ) {
				$order[] = 'pubs_per_page';

				return $limit;
			}
		);
		add_filter(
			Keys::FILTER_LIST_LIMIT,
			static function ( $limit ) use ( &$order ) {
				$order[] = 'list_limit';

				return $limit;
			}
		);

		do_shortcode( '[' . Keys::SHORTCODE . ']' );

		$this->assertSame( array( 'pubs_per_page', 'list_limit' ), $order );
	}

	public function test_page_number_comes_from_the_query_var() {
		$page_id = self::factory()->post->create( array( 'post_type' => 'page' ) );
		$this->go_to( add_query_arg( Keys::QV_PAGED, 2, get_permalink( $page_id ) ) );

		$seen = null;

		add_filter(
			Keys::FILTER_LIST_CONTAINER,
			static function ( $container ) use ( &$seen ) {
				$seen = $container;

				return $container;
			}
		);

		do_shortcode( '[' . Keys::SHORTCODE . ' limit="2"]' );

		$this->assertSame( 2, $seen['paged'] );
	}

	/**
	 * D14 (P2-09): confirms this class's own render() — the live shortcode
	 * handler since P0-12 — still matches the pinned 3.0.1 output, the same
	 * way test-characterisation-output.php pins do_shortcode() itself.
	 */
	public function test_shortcode_output_matches_characterisation_strings() {
		$list = do_shortcode( '[' . Keys::SHORTCODE . ' showas="list"]' );
		$this->assertSame(
			V3_Expected_Output::normalise( V3_Expected_Output::expand( V3_Expected_Output::LIST_ALL, $this->data ) ),
			V3_Expected_Output::normalise( $list )
		);

		$dropdown = do_shortcode( '[' . Keys::SHORTCODE . ' showas="dropdown"]' );
		$this->assertSame(
			V3_Expected_Output::normalise( V3_Expected_Output::expand( V3_Expected_Output::DROPDOWN_ALL, $this->data ) ),
			V3_Expected_Output::normalise( $dropdown )
		);
	}

	/**
	 * D15: fails on the pre-task lib/templates/ files, which used extract().
	 */
	public function test_d15_no_extract_in_shortcode_or_bundled_templates() {
		$files = array_merge(
			array( dirname( __DIR__, 2 ) . '/includes/class-shortcode.php' ),
			glob( dirname( __DIR__, 2 ) . '/templates/classic/*.php' )
		);

		$this->assertNotEmpty( $files );

		foreach ( $files as $file ) {
			// phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown -- reason: local source file, not remote data.
			$contents = file_get_contents( $file );

			$this->assertNotFalse( $contents );
			$this->assertDoesNotMatchRegularExpression( '/\bextract\s*\(/', $contents, $file . ' calls extract()' );
		}
	}
}
