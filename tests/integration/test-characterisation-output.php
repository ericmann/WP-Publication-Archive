<?php
/**
 * Implements SPEC.md §8 Phase 0 item 3: pins 3.0.1 shortcode (list, dropdown,
 * filters, pagination, messages) and widget output as whitespace-normalised
 * expected strings. These tests must pass unchanged through P0-15, so they
 * touch plugin behaviour only through the 3.0.1 public API, do_shortcode()
 * and the_widget() with the 3.0.1 class names.
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA\Tests;

use WPPA\Keys;
use WPPA\Tests\Fixtures\V3_Expected_Output;
use WPPA\Tests\Fixtures\V3_Site;

class Test_Characterisation_Output extends \WP_UnitTestCase {

	/** @var array<string, mixed> */
	private $data;

	/** @var array<string, mixed> */
	private static $widget_args = array(
		'before_widget' => '<div class="widget">',
		'after_widget'  => '</div>',
		'before_title'  => '<h2>',
		'after_title'   => '</h2>',
	);

	public function set_up() {
		parent::set_up();

		$this->data = V3_Site::create( self::factory() );
		V3_Site::reset_link_state();

		$this->set_permalink_structure( '/%postname%/' );
		\WP_Publication_Archive::register_publication();
		\WP_Publication_Archive::custom_rewrites();
		flush_rewrite_rules( false ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.flush_rewrite_rules_flush_rewrite_rules -- reason: test-only, characterisation set_up() needs this site's own rewrite rules.
	}

	private function assert_matches( $expected, $actual ) {
		$this->assertSame(
			V3_Expected_Output::normalise( V3_Expected_Output::expand( $expected, $this->data ) ),
			V3_Expected_Output::normalise( $actual )
		);
	}

	public function test_list_shortcode_output_matches_301() {
		$output = do_shortcode( '[' . Keys::SHORTCODE . ' showas="list"]' );

		$this->assert_matches( V3_Expected_Output::LIST_ALL, $output );
	}

	public function test_list_shortcode_page_2_matches_301() {
		$page_id = self::factory()->post->create( array( 'post_type' => 'page', 'post_title' => 'Pub Page' ) );
		$this->data['page']           = $page_id;
		$this->data['page_permalink'] = get_permalink( $page_id );

		$GLOBALS['post'] = get_post( $page_id );
		$this->go_to( add_query_arg( Keys::QV_PAGED, 2, get_permalink( $page_id ) ) );
		$GLOBALS['post'] = get_post( $page_id );

		$output = do_shortcode( '[' . Keys::SHORTCODE . ' showas="list" limit="2"]' );

		$this->assert_matches( V3_Expected_Output::LIST_LIMIT_2_PAGE_2, $output );
	}

	public function test_dropdown_shortcode_output_matches_301() {
		$output = do_shortcode( '[' . Keys::SHORTCODE . ' showas="dropdown"]' );

		$this->assert_matches( V3_Expected_Output::DROPDOWN_ALL, $output );
	}

	public function test_category_filter_output_matches_301() {
		$output = do_shortcode( '[' . Keys::SHORTCODE . ' categories="reports"]' );

		$this->assert_matches( V3_Expected_Output::LIST_CATEGORY_REPORTS, $output );
	}

	public function test_author_filter_output_matches_301() {
		$output = do_shortcode( '[' . Keys::SHORTCODE . ' author="jane-doe"]' );

		$this->assert_matches( V3_Expected_Output::LIST_AUTHOR_JANE_DOE, $output );
	}

	public function test_unknown_category_message_matches_301() {
		$output = do_shortcode( '[' . Keys::SHORTCODE . ' categories="no-such-category"]' );

		$this->assert_matches( V3_Expected_Output::LIST_UNKNOWN_CATEGORY, $output );
	}

	public function test_archive_widget_output_matches_301() {
		ob_start();
		the_widget(
			'WP_Publication_Archive_Widget',
			array( 'title' => 'Publications', 'number' => 3, 'orderby' => 'date' ),
			self::$widget_args
		);
		$output = ob_get_clean();

		$this->assert_matches( V3_Expected_Output::WIDGET_ARCHIVE, $output );
	}

	public function test_category_count_widget_list_output_matches_301() {
		ob_start();
		the_widget(
			'WP_Publication_Archive_Cat_Count_Widget',
			array( 'title' => 'Categories', 'count' => 1, 'dropdown' => 0 ),
			self::$widget_args
		);
		$output = ob_get_clean();

		$this->assert_matches( V3_Expected_Output::WIDGET_CAT_COUNT_LIST, $output );
	}

	public function test_category_count_widget_dropdown_output_matches_301() {
		ob_start();
		the_widget(
			'WP_Publication_Archive_Cat_Count_Widget',
			array( 'title' => 'Categories', 'count' => 1, 'dropdown' => 1 ),
			self::$widget_args
		);
		$output = ob_get_clean();

		$this->assert_matches( V3_Expected_Output::WIDGET_CAT_COUNT_DROPDOWN, $output );
	}

	public function test_related_widget_output_matches_301() {
		ob_start();
		the_widget(
			'WP_Publication_Archive_Category_Widget',
			array( 'title' => 'Related', 'count' => 3 ),
			self::$widget_args
		);
		$output = ob_get_clean();

		$this->assert_matches( V3_Expected_Output::WIDGET_RELATED, $output );
	}
}
