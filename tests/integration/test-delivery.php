<?php
/**
 * Implements SPEC.md §6.2: the view/download endpoints. Every request
 * validates its stored URL and checks Dam_Bridge before it leaves this
 * class (D1, D17 end to end); default mode redirects, opt-in proxy mode
 * streams through a temp file.
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA\Tests;

use WPPA\Delivery;
use WPPA\Keys;
use WPPA\Plugin;
use WPPA\Streamer;
use WPPA\Tests\Fixtures\V3_Site;

/**
 * Thrown by the test's wp_redirect capture filter, standing in for the
 * exit that always follows a real wp_safe_redirect() call.
 */
class Test_Delivery_Redirect_Interrupt extends \RuntimeException {
}

class Test_Delivery extends \WP_UnitTestCase {

	/** @var \WPPA\Delivery */
	private $original_delivery;

	private $exited = false;

	/** @var list<string> */
	private $headers = array();

	/** @var string|null */
	private $redirect_location;

	/** @var int|null */
	private $redirect_status;

	public function set_up() {
		parent::set_up();

		$this->original_delivery = Plugin::instance()->delivery();
		$this->exited            = false;
		$this->headers           = array();
		$this->redirect_location = null;
		$this->redirect_status   = null;

		add_filter( 'wp_redirect', array( $this, 'capture_redirect' ), 10, 2 );
	}

	public function tear_down() {
		remove_filter( 'wp_redirect', array( $this, 'capture_redirect' ) );
		remove_all_filters( Keys::FILTER_MASK_URL );
		remove_all_filters( Keys::FILTER_PROXY_MAX_BYTES );
		remove_all_filters( 'pre_http_request' );

		$replaced = Plugin::instance()->delivery();
		remove_filter( Keys::HOOK_ALLOWED_REDIRECT_HOSTS, array( $replaced, 'allowed_redirect_hosts' ), 10 );

		Plugin::instance()->replace( 'delivery', $this->original_delivery );
		add_filter( Keys::HOOK_ALLOWED_REDIRECT_HOSTS, array( $this->original_delivery, 'allowed_redirect_hosts' ), 10, 1 );

		parent::tear_down();
	}

	/**
	 * @param string $location
	 * @param int    $status
	 *
	 * @return string
	 */
	public function capture_redirect( $location, $status ) { // phpcs:ignore WordPressVIPMinimum.Hooks.AlwaysReturnInFilter.TerminatingInsteadOfReturn -- reason: test-only, deliberately interrupts wp_safe_redirect() to capture its arguments without a real exit.
		$this->redirect_location = $location;
		$this->redirect_status   = $status;

		throw new Test_Delivery_Redirect_Interrupt( 'redirected' );
	}

	/**
	 * Swaps Plugin's 'delivery' for one with injectable exit/header
	 * callables, using the real Url_Policy/Dam_Bridge/Icons so is_same_site()
	 * and the DAM checks behave exactly as they do in production. Re-binds
	 * the allowed_redirect_hosts hook to the new instance (Plugin wires it
	 * to the object that existed at boot; replace() alone doesn't move it).
	 */
	private function install_delivery(): Delivery {
		$exit = function () {
			$this->exited = true;

			throw new \RuntimeException( 'exit' );
		};

		$header = function ( $line ) {
			$this->headers[] = $line;
		};

		$streamer = new Streamer( get_temp_dir(), $exit, $header, ob_get_level() );

		$delivery = new Delivery(
			Plugin::instance()->url_policy(),
			$streamer,
			Plugin::instance()->dam_bridge(),
			Plugin::instance()->icons(),
			$exit,
			$header
		);

		$original = Plugin::instance()->delivery();
		remove_filter( Keys::HOOK_ALLOWED_REDIRECT_HOSTS, array( $original, 'allowed_redirect_hosts' ), 10 );

		Plugin::instance()->replace( 'delivery', $delivery );
		add_filter( Keys::HOOK_ALLOWED_REDIRECT_HOSTS, array( $delivery, 'allowed_redirect_hosts' ), 10, 1 );

		return $delivery;
	}

	/**
	 * @param int                  $id
	 * @param array<string, mixed> $extra_query
	 */
	private function go_to_publication( $id, $extra_query = array() ): void {
		$this->set_permalink_structure( '/%postname%/' );
		Plugin::instance()->post_type()->register();
		Plugin::instance()->rewrites()->register();
		flush_rewrite_rules( false ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.flush_rewrite_rules_flush_rewrite_rules -- reason: test-only, needs this site's own rewrite rules.

		$this->go_to( add_query_arg( $extra_query, get_permalink( $id ) ) );
	}

	public function test_no_endpoint_query_var_does_nothing() {
		$this->install_delivery();

		$id = self::factory()->post->create( array( 'post_type' => Keys::POST_TYPE ) );

		$this->go_to_publication( $id );

		Plugin::instance()->delivery()->handle();

		$this->assertFalse( $this->exited );
		$this->assertNull( $this->redirect_location );
	}

	public function test_mask_url_defaults_to_false() {
		$this->assertFalse( Keys::DEFAULT_MASK_URL );
	}

	public function test_open_query_var_on_non_publication_is_ignored() {
		$this->install_delivery();

		$id = self::factory()->post->create( array( 'post_type' => 'post' ) );

		$this->go_to_publication( $id, array( Keys::QV_OPEN => 'yes' ) );

		ob_start();
		Plugin::instance()->delivery()->handle();
		$output = ob_get_clean();

		$this->assertSame( '', $output );
		$this->assertFalse( $this->exited );
		$this->assertNull( $this->redirect_location );
	}

	public function test_delivery_does_not_build_the_excerpt() {
		$this->install_delivery();

		$data = V3_Site::create( self::factory() );

		$this->go_to_publication( $data['attached'], array( Keys::QV_OPEN => 'yes' ) );

		$calls = 0;

		$count_excerpt_filter = static function ( $excerpt ) use ( &$calls ) {
			++$calls;

			return $excerpt;
		};

		add_filter( 'get_the_excerpt', $count_excerpt_filter );

		try {
			Plugin::instance()->delivery()->handle();
			$this->fail( 'Expected a redirect.' );
		} catch ( Test_Delivery_Redirect_Interrupt $e ) {
			unset( $e );
		} finally {
			remove_filter( 'get_the_excerpt', $count_excerpt_filter );
		}

		$this->assertSame( 0, $calls );
	}

	public function test_d1_same_site_file_redirects_302() {
		$this->install_delivery();

		$data = V3_Site::create( self::factory() );

		$this->go_to_publication( $data['attached'], array( Keys::QV_OPEN => 'yes' ) );

		try {
			Plugin::instance()->delivery()->handle();
			$this->fail( 'Expected a redirect.' );
		} catch ( Test_Delivery_Redirect_Interrupt $e ) {
			unset( $e );
		}

		$this->assertSame( $data['attachment_url'], $this->redirect_location );
		$this->assertSame( 302, $this->redirect_status );
		$this->assertFalse( $this->exited );
	}

	public function test_d1_stored_local_path_gets_404_with_no_file_bytes() {
		$this->install_delivery();

		$id = self::factory()->post->create( array( 'post_type' => Keys::POST_TYPE ) );
		V3_Site::raw_meta( $id, Keys::META_DOC, '/etc/passwd' );

		$this->go_to_publication( $id, array( Keys::QV_OPEN => 'yes' ) );

		ob_start();

		try {
			$this->expectException( \WPDieException::class );
			$this->expectExceptionCode( 404 );

			Plugin::instance()->delivery()->handle();
		} finally {
			$output = ob_get_clean();
			$this->assertSame( '', $output );
		}
	}

	public function test_d1_internal_host_gets_404() {
		$this->install_delivery();

		$id = self::factory()->post->create( array( 'post_type' => Keys::POST_TYPE ) );
		V3_Site::raw_meta( $id, Keys::META_DOC, 'http://169.254.169.254/latest/meta-data/' );

		$this->go_to_publication( $id, array( Keys::QV_OPEN => 'yes' ) );

		$this->expectException( \WPDieException::class );
		$this->expectExceptionCode( 404 );

		Plugin::instance()->delivery()->handle();
	}

	public function test_external_public_url_redirects_to_its_host() {
		$this->install_delivery();

		$id = self::factory()->post->create( array( 'post_type' => Keys::POST_TYPE ) );
		V3_Site::raw_meta( $id, Keys::META_DOC, 'https://93.184.216.34/doc.pdf' );

		$this->go_to_publication( $id, array( Keys::QV_OPEN => 'yes' ) );

		try {
			Plugin::instance()->delivery()->handle();
			$this->fail( 'Expected a redirect.' );
		} catch ( Test_Delivery_Redirect_Interrupt $e ) {
			unset( $e );
		}

		$this->assertSame( 'https://93.184.216.34/doc.pdf', $this->redirect_location );
	}

	public function test_alternate_endpoint_uses_the_alternate_url() {
		$this->install_delivery();

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
			$this->fail( 'Expected a redirect.' );
		} catch ( Test_Delivery_Redirect_Interrupt $e ) {
			unset( $e );
		}

		$this->assertSame( $data['attachment_url'], $this->redirect_location );
	}

	public function test_allowed_redirect_hosts_unchanged_when_no_redirect_in_progress() {
		$hosts = array( 'example.com' );

		$this->assertSame( $hosts, Plugin::instance()->delivery()->allowed_redirect_hosts( $hosts ) );
	}

	public function test_proxy_mode_streams_mocked_body_and_headers() {
		ob_start();

		$this->install_delivery();

		add_filter( Keys::FILTER_MASK_URL, '__return_true' );

		$data = V3_Site::create( self::factory() );

		add_filter(
			'pre_http_request',
			function ( $preempt, $args, $url ) {
				unset( $preempt, $url );

				if ( 'HEAD' === ( $args['method'] ?? '' ) ) {
					return array(
						'headers'  => array( 'content-length' => '9' ),
						'body'     => '',
						'response' => array( 'code' => 200 ),
					);
				}

				// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_file_put_contents -- reason: test-only mock of WP_Http's own stream-to-file behaviour (pre_http_request intercepts before the real transport runs).
				file_put_contents( $args['filename'], 'mock-body' );

				return array(
					'headers'  => array( 'content-type' => 'application/pdf' ),
					'body'     => '',
					'response' => array( 'code' => 200 ),
				);
			},
			10,
			3
		);

		$this->go_to_publication( $data['attached'], array( Keys::QV_OPEN => 'yes' ) );

		try {
			Plugin::instance()->delivery()->handle();
			$this->fail( 'Expected the exit callable to run.' );
		} catch ( \RuntimeException $e ) {
			unset( $e );
		}

		$output = ob_get_clean();

		$this->assertTrue( $this->exited );
		$this->assertSame( 'mock-body', $output );
		$this->assertStringContainsString( 'Content-Type: application/pdf', implode( "\n", $this->headers ) );
	}

	public function test_proxy_mode_over_size_cap_redirects() {
		$this->install_delivery();

		add_filter( Keys::FILTER_MASK_URL, '__return_true' );
		add_filter(
			Keys::FILTER_PROXY_MAX_BYTES,
			function () {
				return 10;
			}
		);

		$data = V3_Site::create( self::factory() );

		add_filter(
			'pre_http_request',
			function ( $preempt, $args, $url ) {
				unset( $preempt, $args, $url );

				return array(
					'headers'  => array( 'content-length' => '1000' ),
					'body'     => '',
					'response' => array( 'code' => 200 ),
				);
			},
			10,
			3
		);

		$this->go_to_publication( $data['attached'], array( Keys::QV_OPEN => 'yes' ) );

		try {
			Plugin::instance()->delivery()->handle();
			$this->fail( 'Expected a redirect.' );
		} catch ( Test_Delivery_Redirect_Interrupt $e ) {
			unset( $e );
		}

		$this->assertSame( $data['attachment_url'], $this->redirect_location );
		$this->assertFalse( $this->exited );
	}

	public function test_proxy_mode_request_error_redirects() {
		$this->install_delivery();

		add_filter( Keys::FILTER_MASK_URL, '__return_true' );

		$data = V3_Site::create( self::factory() );

		add_filter(
			'pre_http_request',
			function ( $preempt, $args, $url ) {
				unset( $preempt, $url );

				if ( 'HEAD' === ( $args['method'] ?? '' ) ) {
					return array(
						'headers'  => array(),
						'body'     => '',
						'response' => array( 'code' => 200 ),
					);
				}

				return new \WP_Error( 'http_request_failed', 'mock failure' );
			},
			10,
			3
		);

		$this->go_to_publication( $data['attached'], array( Keys::QV_OPEN => 'yes' ) );

		try {
			Plugin::instance()->delivery()->handle();
			$this->fail( 'Expected a redirect.' );
		} catch ( Test_Delivery_Redirect_Interrupt $e ) {
			unset( $e );
		}

		$this->assertSame( $data['attachment_url'], $this->redirect_location );
	}

	public function test_proxy_mode_download_adds_disposition() {
		ob_start();

		$this->install_delivery();

		add_filter( Keys::FILTER_MASK_URL, '__return_true' );

		$data = V3_Site::create( self::factory() );

		add_filter(
			'pre_http_request',
			function ( $preempt, $args, $url ) {
				unset( $preempt, $url );

				if ( 'HEAD' === ( $args['method'] ?? '' ) ) {
					return array(
						'headers'  => array( 'content-length' => '9' ),
						'body'     => '',
						'response' => array( 'code' => 200 ),
					);
				}

				// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_file_put_contents -- reason: test-only mock of WP_Http's own stream-to-file behaviour (pre_http_request intercepts before the real transport runs).
				file_put_contents( $args['filename'], 'mock-body' );

				return array(
					'headers'  => array( 'content-type' => 'application/pdf' ),
					'body'     => '',
					'response' => array( 'code' => 200 ),
				);
			},
			10,
			3
		);

		$this->go_to_publication( $data['attached'], array( Keys::QV_DOWNLOAD => 'yes' ) );

		try {
			Plugin::instance()->delivery()->handle();
			$this->fail( 'Expected the exit callable to run.' );
		} catch ( \RuntimeException $e ) {
			unset( $e );
		}

		ob_get_clean();

		$joined = implode( "\n", $this->headers );
		$this->assertStringContainsString( 'Content-Disposition: attachment; filename=', $joined );
	}
}
