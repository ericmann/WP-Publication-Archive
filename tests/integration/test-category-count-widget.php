<?php
/**
 * Implements SPEC.md §8 Phase 0 item 4 and §6.6: 3.0.1's
 * WP_Publication_Archive_Cat_Count_Widget, aliased back by that name.
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA\Tests;

use WPPA\Keys;
use WPPA\Tests\Fixtures\V3_Site;
use WPPA\Widgets\Category_Count_Widget;

class Test_Category_Count_Widget extends \WP_UnitTestCase {

	public function test_widget_class_is_registered_under_its_legacy_name() {
		global $wp_widget_factory;

		\WPPA\Plugin::instance()->register_widgets();

		$this->assertArrayHasKey( Keys::LEGACY_CLASS_CAT_COUNT_WIDGET, $wp_widget_factory->widgets );
		$this->assertInstanceOf( Category_Count_Widget::class, $wp_widget_factory->widgets[ Keys::LEGACY_CLASS_CAT_COUNT_WIDGET ] );
	}

	public function test_widget_is_not_final() {
		$reflection = new \ReflectionClass( Category_Count_Widget::class );

		$this->assertFalse( $reflection->isFinal() );
	}

	public function test_widget_outputs_list_of_categories_by_default() {
		V3_Site::create( self::factory() );

		$widget = new Category_Count_Widget();

		ob_start();
		$widget->widget(
			array(
				'before_widget' => '<aside>',
				'after_widget'  => '</aside>',
				'before_title'  => '<h2>',
				'after_title'   => '</h2>',
			),
			array( 'title' => 'Categories' )
		);
		$output = ob_get_clean();

		$this->assertStringContainsString( '<h2>Categories</h2>', $output );
		$this->assertStringContainsString( 'Reports', $output );
		$this->assertStringContainsString( '<ul>', $output );
	}

	public function test_widget_outputs_dropdown_when_configured() {
		V3_Site::create( self::factory() );

		$widget = new Category_Count_Widget();

		ob_start();
		$widget->widget(
			array(
				'before_widget' => '<aside>',
				'after_widget'  => '</aside>',
				'before_title'  => '<h2>',
				'after_title'   => '</h2>',
			),
			array(
				'title'    => 'Categories',
				'dropdown' => 1,
			)
		);
		$output = ob_get_clean();

		$this->assertStringContainsString( '<select', $output );
		$this->assertStringContainsString( Keys::FIELD_CAT_DROPDOWN, $output );
	}

	public function test_update_strips_tags_from_title() {
		$widget = new Category_Count_Widget();

		$instance = $widget->update(
			array(
				'title'    => '<b>Reports</b>',
				'count'    => '1',
				'dropdown' => '',
			),
			array()
		);

		$this->assertSame( 'Reports', $instance['title'] );
		$this->assertSame( 1, $instance['count'] );
		$this->assertSame( 0, $instance['dropdown'] );
	}
}
