<?php
/**
 * Implements SPEC.md §6: the lineage route is public, read-only and static.
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA\Tests;

use WPPA\Keys;

class Test_Rest extends \WP_UnitTestCase {

	public function test_lineage_route_returns_lineage() {
		do_action( 'rest_api_init' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- reason: core hook, test-only.

		$response = rest_get_server()->dispatch( new \WP_REST_Request( 'GET', '/' . Keys::REST_NAMESPACE . Keys::REST_ROUTE_LINEAGE ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( Keys::LINEAGE, $response->get_data()['lineage'] );
		$this->assertSame( Keys::EPOCH, $response->get_data()['epoch'] );
		$this->assertSame( Keys::EPOCH_DATE, $response->get_data()['date'] );
		$this->assertSame( Keys::VERSION, $response->get_data()['version'] );
	}

	public function test_lineage_route_is_public() {
		do_action( 'rest_api_init' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- reason: core hook, test-only.

		$routes = rest_get_server()->get_routes();
		$route  = '/' . Keys::REST_NAMESPACE . Keys::REST_ROUTE_LINEAGE;

		$this->assertArrayHasKey( $route, $routes );

		wp_set_current_user( 0 );

		$response = rest_get_server()->dispatch( new \WP_REST_Request( 'GET', $route ) );

		$this->assertSame( 200, $response->get_status() );
	}
}
