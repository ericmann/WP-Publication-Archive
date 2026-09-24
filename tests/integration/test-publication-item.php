<?php
/**
 * Implements SPEC.md §8 Phase 0 item 4 and §6.9: 3.0.1's
 * WP_Publication_Archive_Item, now WPPA\Publication_Item. P1-08 closes D2
 * (output) and D3 (front); D7 stays open until P2-08.
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA\Tests;

use WPPA\Keys;
use WPPA\Plugin;
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

	public function test_d2_script_alternate_description_renders_escaped() {
		$item = new Publication_Item( $this->data['alternates'] );

		ob_start();
		$item->list_downloads();
		$output = ob_get_clean();

		$this->assertStringContainsString( '&lt;script&gt;alert(1)&lt;/script&gt;', $output );
		$this->assertStringNotContainsString( '<script>alert(1)', $output );
	}

	public function test_d3_thumbnail_url_is_escaped() {
		$id = self::factory()->post->create( array( 'post_type' => Keys::POST_TYPE ) );
		V3_Site::raw_meta( $id, Keys::META_IMAGE, 'x" onerror="alert(1)' );

		$item = new Publication_Item( $id );

		$output = $item->get_the_thumbnail();

		$this->assertStringNotContainsString( 'onerror="alert(1)', $output );
	}

	public function test_d3_filtered_title_is_escaped() {
		add_filter(
			Keys::FILTER_TITLE,
			static function () {
				return '<b>Bold</b> Title';
			}
		);

		$item = new Publication_Item( $this->data['attached'] );

		$output = $item->get_the_title();

		$this->assertStringContainsString( '&lt;b&gt;Bold&lt;/b&gt; Title', $output );
		$this->assertStringNotContainsString( '<b>Bold</b>', $output );

		remove_all_filters( Keys::FILTER_TITLE );
	}

	public function test_d3_dropdown_escapes_post_title() {
		global $wpdb;

		$id = self::factory()->post->create(
			array(
				'post_type'   => Keys::POST_TYPE,
				'post_title'  => 'Plain title',
				'post_status' => 'publish',
			)
		);
		V3_Site::raw_meta( $id, Keys::META_DOC, $this->data['attachment_url'] );

		$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- reason: test-only, writes a raw 3.0.1-shaped post_title the same way V3_Site::raw_meta() writes meta.
			$wpdb->posts,
			array( 'post_title' => '<b>Bold</b> Title' ),
			array( 'ID' => $id )
		);
		clean_post_cache( $id );

		$output = do_shortcode( '[' . Keys::SHORTCODE . ' showas="dropdown"]' );

		$this->assertStringContainsString( '&lt;b&gt;Bold&lt;/b&gt; Title', $output );
		$this->assertStringNotContainsString( '<b>Bold</b>', $output );
	}

	/**
	 * @group nodam
	 */
	public function test_thumbnail_passes_through_display_url_unchanged_without_dam() {
		$item = new Publication_Item( $this->data['thumbnail'] );

		$this->assertFalse( Plugin::instance()->dam_bridge()->active() );

		$output = $item->get_the_thumbnail();

		$this->assertStringContainsString( 'src="' . esc_url( (string) $item->upload_image ) . '"', $output );
	}
}
