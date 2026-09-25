<?php
/**
 * Implements SPEC.md §8 Phase 0 item 4 and §6.6: 3.0.1's
 * WP_Publication_Archive_Widget, aliased back by that name.
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA\Tests;

use WPPA\Keys;
use WPPA\Widgets\Archive_Widget;

class Test_Archive_Widget extends \WP_UnitTestCase {

	public function test_id_base_is_the_301_value() {
		$widget = new Archive_Widget();

		$this->assertSame( Keys::WIDGET_ARCHIVE_ID_BASE, $widget->id_base );
	}

	public function test_widget_is_not_final() {
		$reflection = new \ReflectionClass( Archive_Widget::class );

		$this->assertFalse( $reflection->isFinal() );
	}

	public function test_renders_publications_through_the_widget_template() {
		self::factory()->post->create(
			array(
				'post_type'  => Keys::POST_TYPE,
				'post_title' => 'A Sample Publication',
			)
		);

		$widget = new Archive_Widget();

		ob_start();
		$widget->widget(
			array(
				'before_widget' => '<aside>',
				'after_widget'  => '</aside>',
				'before_title'  => '<h2>',
				'after_title'   => '</h2>',
			),
			array( 'title' => 'Archive' )
		);
		$output = ob_get_clean();

		$this->assertStringContainsString( '<aside>', $output );
		$this->assertStringContainsString( '<h2>Archive</h2>', $output );
		$this->assertStringContainsString( 'A Sample Publication', $output );
		$this->assertStringContainsString( '</aside>', $output );

		// 3.0.1 behaviour, preserved verbatim: `global $wppa_publications;
		// unset( $wppa_publications );` only unsets the local reference
		// inside widget(), not the actual $GLOBALS entry, so it remains set
		// after the widget renders.
		$this->assertArrayHasKey( 'wppa' . '_publications', $GLOBALS );
	}

	public function test_update_strips_tags_from_all_fields() {
		$widget = new Archive_Widget();

		$instance = $widget->update(
			array(
				'title'   => '<b>Archive</b>',
				'number'  => '<i>5</i>',
				'orderby' => '<i>date</i>',
			),
			array()
		);

		$this->assertSame( 'Archive', $instance['title'] );
		$this->assertSame( '5', $instance['number'] );
		$this->assertSame( 'date', $instance['orderby'] );
	}
}
