<?php
/**
 * Implements SPEC.md §8 Phase 0 item 4: the German translation loads from
 * languages/, and lang/ is gone.
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA\Tests;

use WPPA\Keys;
use WPPA\Plugin;

class Test_I18n extends \WP_UnitTestCase {

	public function tear_down() {
		unload_textdomain( Keys::TEXT_DOMAIN );
		remove_all_filters( 'pre_determine_locale' );

		parent::tear_down();
	}

	public function test_german_translation_loads_from_languages_dir() {
		// WordPress 6.7 no longer honours 'plugin_locale' for the
		// just-in-time loader that actually reads the .mo file (it calls
		// determine_locale() directly); 'pre_determine_locale' is the
		// filter that short-circuits determine_locale() itself.
		add_filter(
			'pre_determine_locale',
			static function () {
				return 'de_DE';
			}
		);

		unload_textdomain( Keys::TEXT_DOMAIN );

		Plugin::instance()->load_textdomain();

		// phpcs:ignore WordPress.WP.I18n.LowLevelTranslationFunction, WordPress.WP.I18n.NonSingularStringLiteralDomain -- reason: test-only, asserts the compiled .po/.mo translation directly; the just-in-time loader only reads the file on the first translate() call for the domain.
		$this->assertSame( 'Publikation herunterladen', translate( 'Download Publication', 'wp-publication-archive' ) );
		$this->assertTrue( is_textdomain_loaded( Keys::TEXT_DOMAIN ) );
	}

	public function test_no_lang_directory_remains() {
		$this->assertDirectoryDoesNotExist( WP_PUB_ARCH_DIR . 'lang' );
		$this->assertDirectoryExists( WP_PUB_ARCH_DIR . Keys::LANGUAGES_DIR );
	}
}
