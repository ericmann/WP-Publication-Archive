<?php
/**
 * Implements SPEC.md §8 Phase 0 item 4 and §6.6: D14 closes here. All three
 * 3.0.1 widgets carry show_instance_in_rest, so they work in the Legacy
 * Widget block, keep their 3.0.1 id_base values, and still render from a
 * 3.0.1-shaped stored option.
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA\Tests;

use WPPA\Keys;
use WPPA\Widgets\Archive_Widget;
use WPPA\Widgets\Category_Count_Widget;
use WPPA\Widgets\Related_Widget;

class Test_Widgets extends \WP_UnitTestCase {

	public function tear_down() {
		// Leave $wp_widget_factory/$wp_registered_widgets alone: Plugin
		// registers the three bundled widgets once, at process-wide
		// widgets_init, and other test files (e.g. characterisation
		// output) rely on that registration surviving for the rest of the
		// run.
		unregister_sidebar( 'test-widgets-sidebar' );
		delete_option( 'sidebars_widgets' );
		delete_option( 'widget_' . Keys::WIDGET_ARCHIVE_ID_BASE );

		parent::tear_down();
	}

	public function test_d14_every_widget_shows_instance_in_rest() {
		foreach ( array( Archive_Widget::class, Category_Count_Widget::class, Related_Widget::class ) as $class ) {
			$widget = new $class();

			$this->assertTrue( $widget->widget_options['show_instance_in_rest'] ?? false, $class . ' is missing show_instance_in_rest' );
		}
	}

	public function test_id_bases_are_the_301_values() {
		$this->assertSame( Keys::WIDGET_ARCHIVE_ID_BASE, ( new Archive_Widget() )->id_base );
		$this->assertSame( Keys::WIDGET_CAT_COUNT_ID_BASE, ( new Category_Count_Widget() )->id_base );
		$this->assertSame( Keys::WIDGET_RELATED_ID_BASE, ( new Related_Widget() )->id_base );
	}

	/**
	 * A 3.0.1-shaped widget option (numeric-keyed instances plus
	 * '_multiwidget') still renders through dynamic_sidebar().
	 */
	public function test_widget_renders_from_301_shaped_option() {
		self::factory()->post->create(
			array(
				'post_type'  => Keys::POST_TYPE,
				'post_title' => 'A Sidebar Publication',
			)
		);

		register_sidebar(
			array(
				'id'            => 'test-widgets-sidebar',
				'before_widget' => '<aside>',
				'after_widget'  => '</aside>',
				'before_title'  => '<h2>',
				'after_title'   => '</h2>',
			)
		);

		update_option(
			'widget_' . Keys::WIDGET_ARCHIVE_ID_BASE,
			array(
				2               => array( 'title' => 'From 3.0.1' ),
				'_multiwidget'  => 1,
			)
		);

		update_option(
			'sidebars_widgets',
			array(
				'test-widgets-sidebar' => array( Keys::WIDGET_ARCHIVE_ID_BASE . '-2' ),
			)
		);

		// Plugin::register_widgets() already registered this widget's
		// id_base at boot, before this test's option existed, so
		// WP_Widget_Factory::_register_widgets() would now skip it as a
		// duplicate id_base. Re-register the same, already-registered
		// widget object directly so it re-reads its (now 3.0.1-shaped)
		// option and picks up instance 2.
		global $wp_widget_factory;
		$widget_object = $wp_widget_factory->get_widget_object( Keys::WIDGET_ARCHIVE_ID_BASE );
		$widget_object->_register();

		ob_start();
		dynamic_sidebar( 'test-widgets-sidebar' );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'From 3.0.1', $output );
		$this->assertStringContainsString( 'A Sidebar Publication', $output );
	}

	/**
	 * D14: the Legacy Widget block's REST encode endpoint (added by core
	 * once a widget has show_instance_in_rest) works for each id_base.
	 */
	public function test_d14_widget_types_encode_returns_raw_instance() {
		do_action( 'rest_api_init' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- reason: core hook, test-only.

		// Plugin::register_widgets() already registered this widget's
		// id_base at boot (widgets_init); the controller looks it up by
		// id_base via $wp_widget_factory->get_widget_object().
		$admin = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin );

		$form_data = http_build_query(
			array(
				'widget-' . Keys::WIDGET_ARCHIVE_ID_BASE => array(
					-1 => array(
						'title'   => 'Encoded',
						'number'  => '3',
						'orderby' => 'date',
					),
				),
			)
		);

		$request = new \WP_REST_Request( 'POST', '/wp/v2/widget-types/' . Keys::WIDGET_ARCHIVE_ID_BASE . '/encode' );
		$request->set_body_params( array( 'form_data' => $form_data ) );

		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertSame( 'Encoded', $data['instance']['raw']['title'] );
	}
}
