<?php
/**
 * Implements SPEC.md §8 Phase 0 item 1: the V3_Site fixture builds a
 * 3.0.1-shaped site every later test can rely on.
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA\Tests;

use WPPA\Keys;
use WPPA\Tests\Fixtures\V3_Site;

class Test_V3_Site extends \WP_UnitTestCase {

	public function test_fixture_creates_seven_publications() {
		$data = V3_Site::create( self::factory() );

		$ids = array(
			$data['attached'],
			$data['pipe'],
			$data['alternates'],
			$data['thumbnail'],
			$data['categorised'],
			$data['slug_view'],
			$data['slug_download'],
		);

		foreach ( $ids as $id ) {
			$this->assertSame( Keys::POST_TYPE, get_post_type( $id ) );
			$this->assertSame( 'publish', get_post_status( $id ) );
		}

		$this->assertCount( 7, array_unique( $ids ) );
	}

	public function test_pipe_value_is_stored_raw() {
		$data = V3_Site::create( self::factory() );

		$value = get_post_meta( $data['pipe'], Keys::META_DOC, true );

		$this->assertStringStartsWith( 'https|', $value );
	}

	public function test_alternates_has_two_rows_including_script() {
		$data = V3_Site::create( self::factory() );

		$rows = get_post_meta( $data['alternates'], Keys::META_ALTERNATES, false );

		$this->assertCount( 2, $rows );

		$descriptions = array_column( $rows, 'description' );
		$this->assertContains( 'English', $descriptions );
		$this->assertContains( '<script>alert(1)</script>', $descriptions );
	}

	public function test_slug_view_and_slug_download_exist() {
		$data = V3_Site::create( self::factory() );

		$this->assertSame( 'view', get_post_field( 'post_name', $data['slug_view'] ) );
		$this->assertSame( 'download', get_post_field( 'post_name', $data['slug_download'] ) );
	}
}
