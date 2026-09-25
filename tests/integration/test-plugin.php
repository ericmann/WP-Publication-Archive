<?php
/**
 * Implements SPEC.md §4: Plugin boots once, registers its hooks, loads the
 * 3.0.1 runtime, and lets tests swap services.
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA\Tests\Integration;

use WPPA\FixedClock;
use WPPA\Flags;
use WPPA\Keys;
use WPPA\Plugin;

class Test_Plugin extends \WP_UnitTestCase {

	public function test_boot_is_idempotent() {
		$first = Plugin::instance();

		Plugin::boot();

		$this->assertSame( $first, Plugin::instance() );
	}

	public function test_unregister_and_register_round_trip() {
		$plugin = Plugin::instance();

		$plugin->unregister_hooks();
		$this->assertFalse( has_action( Keys::HOOK_WP_ENQUEUE_SCRIPTS, array( $plugin->assets(), 'register' ) ) );

		$plugin->register_hooks();
		$this->assertSame( 10, has_action( Keys::HOOK_WP_ENQUEUE_SCRIPTS, array( $plugin->assets(), 'register' ) ) );
	}

	public function test_replace_swaps_a_service_and_rejects_wrong_types() {
		$plugin   = Plugin::instance();
		$original = $plugin->flags();
		$flags    = new Flags( new FixedClock( 1 ) );

		$plugin->replace( 'flags', $flags );
		$this->assertSame( $flags, $plugin->flags() );

		$plugin->replace( 'flags', $original );

		$this->expectException( \TypeError::class );
		$plugin->replace( 'flags', new \stdClass() );
	}

	public function test_replace_rejects_unknown_service() {
		$this->expectException( \InvalidArgumentException::class );

		Plugin::instance()->replace( 'nope', new \stdClass() );
	}

	public function test_booted_action_fired() {
		$this->assertGreaterThanOrEqual( 1, did_action( Keys::ACTION_BOOTED ) );
	}

	public function test_boot_loads_the_301_runtime() {
		$this->assertTrue( class_exists( 'WP_Publication_Archive', false ) );
		$this->assertTrue( post_type_exists( 'publication' ) );
	}

	/**
	 * D4: fails on the pre-task code, where WP_Publication_Archive::search()
	 * is added to posts_where_request.
	 */
	public function test_d4_no_plugin_callback_on_search_request_filters() {
		foreach ( array( Keys::HOOK_POSTS_WHERE, Keys::HOOK_POSTS_JOIN, Keys::HOOK_POSTS_DISTINCT ) as $hook ) {
			$plugin_callback_found = false;

			if ( ! empty( $GLOBALS['wp_filter'][ $hook ] ) ) {
				foreach ( $GLOBALS['wp_filter'][ $hook ]->callbacks as $callbacks ) {
					foreach ( $callbacks as $callback ) {
						$function = $callback['function'];

						$class = is_array( $function ) ? ( is_object( $function[0] ) ? get_class( $function[0] ) : $function[0] ) : null;

						if ( null !== $class && ( 0 === strpos( $class, 'WPPA' ) || 'WP_Publication_Archive' === $class ) ) {
							$plugin_callback_found = true;
						}
					}
				}
			}

			$this->assertFalse( $plugin_callback_found, 'Unexpected plugin callback on ' . $hook );
		}
	}

	public function test_d4_legacy_search_methods_return_their_argument() {
		$this->assertSame( 'where', \WP_Publication_Archive::search( 'where' ) );
		$this->assertSame( 'join', \WP_Publication_Archive::search_join( 'join' ) );
		$this->assertSame( 'distinct', \WP_Publication_Archive::search_distinct( 'distinct' ) );
	}

	public function test_model_and_routing_hooks_registered() {
		$plugin = Plugin::instance();

		$this->assertSame( 10, has_action( Keys::HOOK_INIT, array( $plugin->post_type(), 'register' ) ) );
		$this->assertSame( 10, has_action( Keys::HOOK_INIT, array( $plugin->rewrites(), 'register' ) ) );
		$this->assertSame( 10, has_action( Keys::HOOK_INIT, array( $plugin, 'load_textdomain' ) ) );
		$this->assertSame( 10, has_filter( Keys::HOOK_QUERY_VARS, array( $plugin->rewrites(), 'query_vars' ) ) );
	}

	public function test_phase_1_services_are_constructed_and_replaceable() {
		$plugin = Plugin::instance();

		$this->assertInstanceOf( \WPPA\Url_Policy::class, $plugin->url_policy() );
		$this->assertInstanceOf( \WPPA\Dam_Bridge::class, $plugin->dam_bridge() );

		$original = $plugin->url_policy();
		$replacement = new \WPPA\Url_Policy(
			'example.org',
			static function () {
				return true;
			}
		);

		$plugin->replace( 'url_policy', $replacement );
		$this->assertSame( $replacement, $plugin->url_policy() );
		$plugin->replace( 'url_policy', $original );

		$original_dam = $plugin->dam_bridge();
		$replacement_dam = new \WPPA\Dam_Bridge( $plugin->url_policy() );

		$plugin->replace( 'dam_bridge', $replacement_dam );
		$this->assertSame( $replacement_dam, $plugin->dam_bridge() );
		$plugin->replace( 'dam_bridge', $original_dam );
	}

	public function test_allowed_redirect_hosts_filter_registered() {
		$plugin = Plugin::instance();

		$this->assertSame( 10, has_filter( Keys::HOOK_ALLOWED_REDIRECT_HOSTS, array( $plugin->delivery(), 'allowed_redirect_hosts' ) ) );
	}

	/**
	 * P1-02 registers this unconditionally (D19); with the DAM absent the
	 * filter never fires.
	 */
	public function test_dam_filter_registered() {
		$plugin = Plugin::instance();

		$this->assertSame( 10, has_filter( Keys::HOOK_DAM_INDEXED_IDS, array( $plugin->dam_bridge(), 'indexed_attachment_ids' ) ) );
	}

	/**
	 * D11 (P2-01): the allow_url_fopen admin notice is misleading (nothing
	 * in 3.1.0 needs allow_url_fopen) and is deleted, not ported.
	 */
	public function test_d11_no_allow_url_fopen_notice_is_defined_or_registered() {
		$this->assertFalse( method_exists( Plugin::class, 'fopen_notice' ) );

		$plugin_callback_found = false;

		if ( ! empty( $GLOBALS['wp_filter'][ Keys::HOOK_ADMIN_NOTICES ] ) ) {
			foreach ( $GLOBALS['wp_filter'][ Keys::HOOK_ADMIN_NOTICES ]->callbacks as $callbacks ) {
				foreach ( $callbacks as $callback ) {
					$function = $callback['function'];

					$class = is_array( $function ) ? ( is_object( $function[0] ) ? get_class( $function[0] ) : $function[0] ) : null;

					if ( null !== $class && 0 === strpos( $class, 'WPPA' ) ) {
						$plugin_callback_found = true;
					}
				}
			}
		}

		$this->assertFalse( $plugin_callback_found, 'Unexpected plugin callback on ' . Keys::HOOK_ADMIN_NOTICES );
	}

	/**
	 * D8 (P2-01): the post_type_link hijack is dead code, deleted rather
	 * than ported.
	 */
	public function test_d8_post_type_link_not_hooked_by_plugin() {
		$plugin = Plugin::instance();

		$this->assertFalse( method_exists( $plugin->rewrites(), 'filter_post_type_link' ) );
		$this->assertFalse( has_filter( Keys::HOOK_POST_TYPE_LINK ) );
	}

	/**
	 * D9 timing (P2-06 fixes the body): the schema upgrade now runs on init,
	 * not at Plugin::boot().
	 */
	public function test_upgrade_runs_on_init_not_at_boot() {
		$plugin  = Plugin::instance();
		$upgrade = $plugin->upgrade();

		$this->assertSame( Keys::UPGRADE_PRIORITY, has_action( Keys::HOOK_INIT, array( $upgrade, 'maybe_upgrade' ) ) );
	}
}
