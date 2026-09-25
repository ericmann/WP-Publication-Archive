<?php
/**
 * Implements SPEC.md §8 Phase 0 item 3 and §6.5: pins 3.0.1's
 * theme-overridable template location before anything moves. These tests
 * must pass unchanged through P0-15, so they touch plugin behaviour only
 * through the 3.0.1 public API, do_shortcode(), the_widget() and core
 * functions.
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA\Tests;

use WPPA\Keys;
use WPPA\Tests\Fixtures\V3_Site;

class Test_Characterisation_Templates extends \WP_UnitTestCase {

	/** @var array<string, mixed> */
	private $data;

	/** @var string */
	private $original_theme;

	public function set_up() {
		parent::set_up();

		$this->data = V3_Site::create( self::factory() );
		V3_Site::reset_link_state();

		$this->set_permalink_structure( '/%postname%/' );
		\WP_Publication_Archive::register_publication();
		\WP_Publication_Archive::custom_rewrites();
		flush_rewrite_rules( false ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.flush_rewrite_rules_flush_rewrite_rules -- reason: test-only, characterisation set_up() needs this site's own rewrite rules.
	}

	private function switch_to_characterisation_theme() {
		$this->original_theme = get_stylesheet();

		$theme_root = dirname( __DIR__ ) . '/fixtures/theme';
		register_theme_directory( $theme_root );

		// phpcs:ignore WordPress.WP.DeprecatedFunctions.wp_clean_themes_cacheFound -- reason: test-only, forces theme discovery of the newly registered directory.
		wp_clean_themes_cache();
		switch_theme( 'characterisation-theme' );
	}

	public function tear_down() {
		if ( null !== $this->original_theme ) {
			switch_theme( $this->original_theme );
		}

		parent::tear_down();
	}

	public function test_theme_list_template_overrides_bundled() {
		$this->switch_to_characterisation_theme();

		$output = do_shortcode( '[' . Keys::SHORTCODE . ' showas="list"]' );

		$this->assertStringContainsString( 'CHAR-LIST', $output );
	}

	public function test_theme_dropdown_template_overrides_bundled() {
		$this->switch_to_characterisation_theme();

		$output = do_shortcode( '[' . Keys::SHORTCODE . ' showas="dropdown"]' );

		$this->assertStringContainsString( 'CHAR-DROPDOWN', $output );
	}

	public function test_theme_widget_template_overrides_bundled() {
		$this->switch_to_characterisation_theme();

		ob_start();
		the_widget( 'WP_Publication_Archive_Widget', array( 'title' => 'Publications' ), array() );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'CHAR-WIDGET', $output );
	}

	public function test_theme_single_template_is_located() {
		$this->switch_to_characterisation_theme();

		$this->go_to( get_permalink( $this->data['attached'] ) );

		$template = apply_filters( 'template_include', '' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- reason: core hook, test-only.

		$this->assertSame( get_stylesheet_directory() . '/single-publication.php', $template );
	}

	public function test_theme_archive_template_is_located() {
		$this->switch_to_characterisation_theme();

		$this->go_to( site_url( '/?post_type=' . Keys::POST_TYPE ) );

		$template = apply_filters( 'template_include', '' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- reason: core hook, test-only.

		$this->assertSame( get_stylesheet_directory() . '/archive-publication.php', $template );
	}

	public function test_bundled_list_template_used_without_theme_override() {
		$output = do_shortcode( '[' . Keys::SHORTCODE . ' showas="list"]' );

		$this->assertStringNotContainsString( 'CHAR-LIST', $output );
		$this->assertStringContainsString( 'publication-archive', $output );
	}

	/**
	 * D15 (SPEC §6.5): a 3.0.1-shaped theme copy that still calls
	 * extract( $wppa_container ) renders correctly.
	 */
	public function test_theme_copy_of_301_template_using_extract_still_renders() {
		$this->switch_to_characterisation_theme();

		$output = do_shortcode( '[' . Keys::SHORTCODE . ' showas="list"]' );

		$this->assertMatchesRegularExpression( '/CHAR-LIST \d+/', $output );
	}
}
