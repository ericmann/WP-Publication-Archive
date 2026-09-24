<?php
/**
 * Implements SPEC.md §8 Phase 0 item 4: 3.0.1's WP_Publication_Archive_Item,
 * now WPPA\Publication_Item, with D2, D3 and D7 preserved.
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA\Tests;

use WPPA\Keys;
use WPPA\Publication_Item;
use WPPA\Tests\Fixtures\V3_Site;

class Test_Publication_Item extends \WP_UnitTestCase {

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

	public function test_item_reads_301_meta_and_normalises_pipe_uri() {
		$item = new Publication_Item( $this->data['pipe'] );

		$this->assertSame( $this->data['pipe'], $item->ID );
		$this->assertStringStartsWith( 'https://', $item->uri );
		$this->assertStringNotContainsString( 'https|', $item->uri );
	}

	public function test_get_the_uri_markup_matches_301() {
		$item = new Publication_Item( $this->data['attached'] );

		$output = $item->get_the_uri();

		$this->assertStringContainsString( 'class="publication_download"', $output );
		$this->assertStringContainsString( 'assets/icons/pdf.png', $output );
		$this->assertStringContainsString( \WPPA\Plugin::instance()->rewrites()->open_link( $this->data['attached'] ), $output );
		$this->assertStringContainsString( \WPPA\Plugin::instance()->rewrites()->download_link( $this->data['attached'] ), $output );
	}

	public function test_get_the_authors_includes_the_date() {
		$item = new Publication_Item( $this->data['categorised'] );

		$output = $item->get_the_authors();

		$this->assertStringContainsString( 'Jane Doe', $output );
		$this->assertStringContainsString( '<span class="date">', $output );
	}

	public function test_title_filter_receives_title_and_id() {
		$seen = array();

		add_filter(
			Keys::FILTER_TITLE,
			static function ( $title, $id ) use ( &$seen ) {
				$seen = array( $title, $id );

				return $title;
			},
			10,
			2
		);

		$item = new Publication_Item( $this->data['attached'] );
		$item->get_the_title();

		$this->assertSame( array( 'Attached Report', $this->data['attached'] ), $seen );

		remove_all_filters( Keys::FILTER_TITLE );
	}

	public function test_list_downloads_prints_view_and_download_links() {
		$item = new Publication_Item( $this->data['alternates'] );

		ob_start();
		$item->list_downloads();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'English', $output );
		$this->assertStringContainsString( __( 'View', 'wp-publication-archive' ), $output );
		$this->assertStringContainsString( __( 'Download', 'wp-publication-archive' ), $output );
	}
}
