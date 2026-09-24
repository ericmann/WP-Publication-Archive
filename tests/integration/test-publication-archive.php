<?php
/**
 * Implements SPEC.md §8 Phase 0 item 4: the 3.0.1 WP_Publication_Archive
 * delegate.
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA\Tests;

use WPPA\Keys;
use WPPA\Legacy\Publication_Archive;

class Test_Publication_Archive extends \WP_UnitTestCase {

	public function test_link_delegates_match_rewrites() {
		$id = self::factory()->post->create( array( 'post_type' => Keys::POST_TYPE ) );

		$rewrites = \WPPA\Plugin::instance()->rewrites();

		$this->assertSame( $rewrites->open_link( $id ), Publication_Archive::get_open_link( $id ) );
		$this->assertSame( $rewrites->download_link( $id ), Publication_Archive::get_download_link( $id ) );
		$this->assertSame( $rewrites->alternate_open_link( $id, 'a' ), Publication_Archive::get_alternate_open_link( $id, 'a' ) );
		$this->assertSame( $rewrites->alternate_download_link( $id, 'a' ), Publication_Archive::get_alternate_download_link( $id, 'a' ) );
	}

	public function test_search_methods_return_their_argument() {
		$this->assertSame( 'where-clause', Publication_Archive::search( 'where-clause' ) );
		$this->assertSame( 'join-clause', Publication_Archive::search_join( 'join-clause' ) );
		$this->assertSame( 'distinct', Publication_Archive::search_distinct( 'distinct' ) );
	}

	public function test_no_hook_is_registered_with_a_legacy_callable() {
		global $wp_filter;

		foreach ( $wp_filter as $hook_name => $hook ) {
			foreach ( $hook->callbacks as $callbacks ) {
				foreach ( $callbacks as $callback ) {
					$function = $callback['function'];

					$class = null;
					if ( is_array( $function ) && is_object( $function[0] ) ) {
						$class = get_class( $function[0] );
					} elseif ( is_array( $function ) && is_string( $function[0] ) ) {
						$class = $function[0];
					} elseif ( is_string( $function ) && false !== strpos( $function, '::' ) ) {
						$class = strtok( $function, ':' );
					}

					if ( null === $class ) {
						continue;
					}

					$this->assertStringStartsNotWith( 'WPPA\\Legacy\\', $class, "Hook '$hook_name' is registered with a WPPA\\Legacy callable." );
					$this->assertStringStartsNotWith( 'WP_Publication_Archive', $class, "Hook '$hook_name' is registered with a 3.0.1 callable." );
				}
			}
		}
	}
}
