<?php
/**
 * Implements SPEC.md §8 Phase 0 item 4: the 3.0.1 view/download endpoints,
 * ported onto Delivery/Streamer/Icons. D1, D6 and D17 fixes are Phase 1, so
 * the 3.0.1 default (wppa_mask_url true, i.e. stream mode) is unchanged;
 * these tests force redirect mode with the filter to stay off the network.
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA\Tests;

use WPPA\Delivery;
use WPPA\Icons;
use WPPA\Keys;
use WPPA\Plugin;
use WPPA\Streamer;
use WPPA\Tests\Fixtures\V3_Site;

class Test_Delivery extends \WP_UnitTestCase {

	private $exited = false;

	/** @var list<string> */
	private $headers = array();

	public function set_up() {
		parent::set_up();

		$this->exited  = false;
		$this->headers = array();

		add_filter( Keys::FILTER_MASK_URL, '__return_false' );

		$exit = function () {
			$this->exited = true;

			throw new \RuntimeException( 'exit' );
		};

		$header = function ( $line ) {
			$this->headers[] = $line;
		};

		$delivery = new Delivery( new Streamer( $exit, $header ), new Icons(), $exit, $header );

		Plugin::instance()->replace( 'delivery', $delivery );
	}

	public function tear_down() {
		remove_filter( Keys::FILTER_MASK_URL, '__return_false' );

		parent::tear_down();
	}

	private function go_to_publication( $id, $extra_query = array() ) {
		$this->set_permalink_structure( '/%postname%/' );
		Plugin::instance()->post_type()->register();
		Plugin::instance()->rewrites()->register();
		flush_rewrite_rules( false ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.flush_rewrite_rules_flush_rewrite_rules -- reason: test-only, needs this site's own rewrite rules.

		$this->go_to( add_query_arg( $extra_query, get_permalink( $id ) ) );
	}

	public function test_no_endpoint_query_var_does_nothing() {
		$id = self::factory()->post->create( array( 'post_type' => Keys::POST_TYPE ) );

		$this->go_to_publication( $id );

		Plugin::instance()->delivery()->handle();

		$this->assertFalse( $this->exited );
		$this->assertSame( array(), $this->headers );
	}

	public function test_view_redirect_mode_emits_303_location() {
		$data = V3_Site::create( self::factory() );

		$this->go_to_publication( $data['attached'], array( Keys::QV_OPEN => 'yes' ) );

		try {
			Plugin::instance()->delivery()->handle();
			$this->fail( 'Expected exit.' );
		} catch ( \RuntimeException $e ) {
			unset( $e );
		}

		$this->assertTrue( $this->exited );
		$this->assertSame( 'HTTP/1.1 303 See Other', $this->headers[0] );
		$this->assertSame( 'Location: ' . $data['attachment_url'], $this->headers[1] );
	}

	public function test_download_redirect_mode_emits_303_location() {
		$data = V3_Site::create( self::factory() );

		$this->go_to_publication( $data['attached'], array( Keys::QV_DOWNLOAD => 'yes' ) );

		try {
			Plugin::instance()->delivery()->handle();
			$this->fail( 'Expected exit.' );
		} catch ( \RuntimeException $e ) {
			unset( $e );
		}

		$this->assertTrue( $this->exited );
		$this->assertSame( 'HTTP/1.1 303 See Other', $this->headers[0] );
		$this->assertSame( 'Location: ' . $data['attachment_url'], $this->headers[1] );
	}

	public function test_alternate_is_matched_by_description() {
		$data = V3_Site::create( self::factory() );

		$this->go_to_publication(
			$data['alternates'],
			array(
				Keys::QV_OPEN => 'yes',
				Keys::QV_ALT  => 'English',
			)
		);

		try {
			Plugin::instance()->delivery()->handle();
			$this->fail( 'Expected exit.' );
		} catch ( \RuntimeException $e ) {
			unset( $e );
		}

		$this->assertSame( 'Location: ' . $data['attachment_url'], $this->headers[1] );
	}

	public function test_pipe_url_is_normalised_before_redirect() {
		$data = V3_Site::create( self::factory() );

		$this->go_to_publication( $data['pipe'], array( Keys::QV_OPEN => 'yes' ) );

		try {
			Plugin::instance()->delivery()->handle();
			$this->fail( 'Expected exit.' );
		} catch ( \RuntimeException $e ) {
			unset( $e );
		}

		$pipe_url = get_post_meta( $data['pipe'], Keys::META_DOC, true );
		$normalised = str_replace( 'https|', 'https://', $pipe_url );

		$this->assertSame( 'Location: ' . $normalised, $this->headers[1] );
		$this->assertStringStartsWith( 'https://', $normalised );
	}
}
