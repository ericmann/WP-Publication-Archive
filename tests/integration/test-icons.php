<?php
/**
 * Implements SPEC.md §8 Phase 0 item 4 (D16): icon lookup and MIME
 * detection off wp_check_filetype().
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA\Tests;

use WPPA\Icons;
use WPPA\Keys;

class Test_Icons extends \WP_UnitTestCase {

	public function tear_down() {
		remove_all_filters( Keys::FILTER_PUBLICATION_ICON );

		parent::tear_down();
	}

	public function test_pdf_icon_url_points_at_assets_icons() {
		$icons = new Icons();

		$this->assertSame( WP_PUB_ARCH_URL . Keys::ICON_DIR . 'pdf.png', $icons->url_for( 'application/pdf' ) );
	}

	public function test_unknown_type_uses_unknown_icon() {
		$icons = new Icons();

		$this->assertSame( WP_PUB_ARCH_URL . Keys::ICON_DIR . 'unknown.png', $icons->url_for( 'application/x-nonsense' ) );
	}

	public function test_icon_filter_applies() {
		add_filter(
			Keys::FILTER_PUBLICATION_ICON,
			static function () {
				return 'https://example.com/custom.png';
			}
		);

		$icons = new Icons();

		$this->assertSame( 'https://example.com/custom.png', $icons->url_for( 'application/pdf' ) );
	}

	public function test_d16_mime_for_uses_wp_check_filetype() {
		$icons = new Icons();

		$this->assertSame( 'application/pdf', $icons->mime_for( 'report.pdf' ) );
		$this->assertSame( 'application/pdf', $icons->mime_for( 'https://example.com/uploads/report.pdf' ) );
		$this->assertSame( 'application/octet-stream', $icons->mime_for( 'file.unknownext' ) );
	}

	public function test_every_301_icon_file_exists_under_assets_icons() {
		foreach ( array( 'pdf', 'zip', 'audio', 'image', 'doc', 'data', 'video', 'unknown' ) as $name ) {
			$this->assertFileExists( WP_PUB_ARCH_DIR . Keys::ICON_DIR . $name . '.png' );
		}
	}
}
