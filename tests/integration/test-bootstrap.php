<?php
/**
 * Implements SPEC.md §4.1: the bootstrap wires the 3.0.1 runtime's behaviour
 * entirely through WPPA services (the transitional lib/ loader is gone as of
 * P0-15).
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA\Tests;

use WPPA\Keys;

class Test_Bootstrap extends \WP_UnitTestCase {

	public function test_legacy_constants_are_defined() {
		$this->assertTrue( defined( 'WP_PUB_ARCH_VERSION' ) );
		$this->assertSame( Keys::VERSION, WP_PUB_ARCH_VERSION );

		$this->assertTrue( defined( 'WP_PUB_ARCH_DIR' ) );
		$this->assertStringEndsWith( '/', WP_PUB_ARCH_DIR );
	}

	public function test_301_runtime_registers_the_publication_post_type() {
		$this->assertTrue( post_type_exists( 'publication' ) );
	}

	public function test_301_shortcode_is_registered() {
		$this->assertTrue( shortcode_exists( Keys::SHORTCODE ) );
	}

	/**
	 * Templates moved from the transitional runtime directory to the frozen
	 * templates/classic/ in P0-12 (SPEC §6.5).
	 */
	public function test_bundled_templates_are_found_under_templates_classic() {
		$this->assertFileExists( WP_PUB_ARCH_DIR . Keys::TEMPLATE_DIR . 'template.wppa_widget.php' );
		$this->assertFileExists( WP_PUB_ARCH_DIR . Keys::TEMPLATE_DIR . 'template.wppa_publication_list.php' );
		$this->assertFileExists( WP_PUB_ARCH_DIR . Keys::TEMPLATE_DIR . 'template.wppa_publication_dropdown.php' );
		$this->assertFileExists( WP_PUB_ARCH_DIR . Keys::TEMPLATE_DIR . 'single-publication.php' );
		$this->assertFileExists( WP_PUB_ARCH_DIR . Keys::TEMPLATE_DIR . 'archive-publication.php' );
	}

	/**
	 * P0-15: the transitional runtime directory and the unused legacy asset
	 * directories are gone.
	 */
	public function test_no_301_directories_remain() {
		$this->assertDirectoryDoesNotExist( WP_PUB_ARCH_DIR . 'lib' );
		$this->assertDirectoryDoesNotExist( WP_PUB_ARCH_DIR . 'lang' );
		$this->assertDirectoryDoesNotExist( WP_PUB_ARCH_DIR . 'images' );
	}
}
