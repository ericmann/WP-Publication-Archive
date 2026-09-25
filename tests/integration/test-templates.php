<?php
/**
 * Implements SPEC.md §8 Phase 0 item 4: 3.0.1's template location and the
 * excerpt_length filter, including the Related widget's summary-length
 * scope (P3).
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA\Tests;

use WPPA\Keys;
use WPPA\Templates;
use WPPA\Tests\Fixtures\V3_Site;

class Test_Templates extends \WP_UnitTestCase {

	/** @var string */
	private $original_theme;

	public function set_up() {
		parent::set_up();

		$this->set_permalink_structure( '/%postname%/' );
		\WPPA\Plugin::instance()->post_type()->register();
		\WPPA\Plugin::instance()->rewrites()->register();
		flush_rewrite_rules( false ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.flush_rewrite_rules_flush_rewrite_rules -- reason: test-only, needs this site's own rewrite rules.
	}

	public function tear_down() {
		if ( null !== $this->original_theme ) {
			switch_theme( $this->original_theme );
			$this->original_theme = null;
		}

		parent::tear_down();
	}

	private function switch_to_characterisation_theme() {
		$this->original_theme = get_stylesheet();

		register_theme_directory( dirname( __DIR__ ) . '/fixtures/theme' );
		wp_clean_themes_cache(); // phpcs:ignore WordPress.WP.DeprecatedFunctions.wp_clean_themes_cacheFound -- reason: test-only, forces theme discovery of the newly registered directory.
		switch_theme( 'characterisation-theme' );
	}

	public function test_single_template_prefers_theme_then_bundled() {
		$id = self::factory()->post->create( array( 'post_type' => Keys::POST_TYPE ) );
		$this->go_to( get_permalink( $id ) );

		$templates = new Templates();

		$bundled = $templates->single_template( 'default.php' );
		$this->assertSame( WP_PUB_ARCH_DIR . Keys::TEMPLATE_DIR . Keys::TEMPLATE_SINGLE, $bundled );

		$this->switch_to_characterisation_theme();
		$this->go_to( get_permalink( $id ) );
		$this->assertSame( get_stylesheet_directory() . '/' . Keys::TEMPLATE_SINGLE, $templates->single_template( 'default.php' ) );
	}

	public function test_archive_template_for_publication_archive() {
		$data = V3_Site::create( self::factory() );

		$this->go_to( site_url( '/?post_type=' . Keys::POST_TYPE ) );

		$templates = new Templates();
		$path      = $templates->archive_template( 'default.php' );

		$this->assertSame( WP_PUB_ARCH_DIR . Keys::TEMPLATE_DIR . Keys::TEMPLATE_ARCHIVE, $path );
	}

	public function test_excerpt_length_applies_summary_filter_for_publications() {
		$id = self::factory()->post->create( array( 'post_type' => Keys::POST_TYPE ) );
		$this->go_to( get_permalink( $id ) );

		add_filter( Keys::FILTER_SUMMARY_LENGTH, function () {
			return 42;
		} );

		$templates = new Templates();

		$this->assertSame( 42, $templates->excerpt_length( 55 ) );

		remove_all_filters( Keys::FILTER_SUMMARY_LENGTH );
	}

	public function test_widget_summary_scope_sets_and_clears_even_on_exception() {
		$templates = new Templates();

		add_filter( Keys::FILTER_WIDGET_SUMMARY_LENGTH, function () {
			return 99;
		} );

		$this->assertSame( 55, $templates->excerpt_length( 55 ) );

		try {
			$templates->with_widget_summary_length(
				function () use ( $templates ) {
					$this->assertSame( 99, $templates->excerpt_length( 55 ) );

					throw new \RuntimeException( 'boom' );
				}
			);
			$this->fail( 'Expected the exception to propagate.' );
		} catch ( \RuntimeException $e ) {
			$this->assertSame( 'boom', $e->getMessage() );
		}

		$this->assertSame( 55, $templates->excerpt_length( 55 ) );

		remove_all_filters( Keys::FILTER_WIDGET_SUMMARY_LENGTH );
	}
}
