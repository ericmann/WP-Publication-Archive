<?php
/**
 * Implements SPEC.md §8 Phase 0 item 4 and §6.6: 3.0.1's
 * WP_Publication_Archive_Category_Widget, aliased back by that name.
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA\Tests;

use WPPA\Keys;
use WPPA\Widgets\Related_Widget;

class Test_Related_Widget extends \WP_UnitTestCase {

	public function test_id_base_is_the_301_value() {
		$widget = new Related_Widget();

		$this->assertSame( Keys::WIDGET_RELATED_ID_BASE, $widget->id_base );
	}

	public function test_widget_is_not_final() {
		$reflection = new \ReflectionClass( Related_Widget::class );

		$this->assertFalse( $reflection->isFinal() );
	}

	public function test_summary_length_scope_applies_only_inside_the_widget() {
		$id = self::factory()->post->create(
			array(
				'post_type'    => Keys::POST_TYPE,
				'post_content' => 'One two three four five six seven eight nine ten.',
				'post_excerpt' => '',
			)
		);
		$this->go_to( get_permalink( $id ) );

		$called = false;
		add_filter(
			Keys::FILTER_WIDGET_SUMMARY_LENGTH,
			function ( $length ) use ( &$called ) {
				$called = true;

				return $length;
			}
		);

		// Outside the widget, the widget-scoped filter must not fire.
		get_the_excerpt( $id );
		$this->assertFalse( $called );

		$widget = new Related_Widget();

		ob_start();
		$widget->widget(
			array(
				'before_widget' => '<aside>',
				'after_widget'  => '</aside>',
				'before_title'  => '<h2>',
				'after_title'   => '</h2>',
			),
			array(
				'title' => 'Related',
				'count' => 5,
			)
		);
		ob_get_clean();

		// Inside the widget's loop, it must have fired.
		$this->assertTrue( $called );

		// And the scope is cleared again once the widget returns.
		$called = false;
		get_the_excerpt( $id );
		$this->assertFalse( $called );

		remove_all_filters( Keys::FILTER_WIDGET_SUMMARY_LENGTH );
	}

	public function test_widget_guards_against_a_non_post_queried_object() {
		self::factory()->post->create(
			array(
				'post_type'    => Keys::POST_TYPE,
				'post_content' => 'Content.',
			)
		);

		if ( ! term_exists( 'reports', 'category' ) ) {
			wp_insert_category(
				array(
					'cat_name'           => 'Reports',
					'category_nicename' => 'reports',
				)
			);
		}
		$category = get_term_by( 'slug', 'reports', 'category' );

		$this->go_to( get_term_link( (int) $category->term_id, 'category' ) );

		$widget = new Related_Widget();

		ob_start();
		$widget->widget(
			array(
				'before_widget' => '<aside>',
				'after_widget'  => '</aside>',
				'before_title'  => '<h2>',
				'after_title'   => '</h2>',
			),
			array(
				'title' => 'Related',
				'count' => 5,
			)
		);
		$output = ob_get_clean();

		$this->assertStringContainsString( '<aside>', $output );
	}

	public function test_update_casts_count_to_int() {
		$widget = new Related_Widget();

		$instance = $widget->update(
			array(
				'title' => '<b>Related</b>',
				'count' => '7',
			),
			array()
		);

		$this->assertSame( 'Related', $instance['title'] );
		$this->assertSame( 7, $instance['count'] );
	}

	public function test_limit_summary_length_is_never_hooked() {
		$widget = new Related_Widget();

		$this->assertFalse( has_filter( 'excerpt_length', array( $widget, 'limit_summary_length' ) ) );
		$this->assertSame( Keys::DEFAULT_WIDGET_SUMMARY_LENGTH, $widget->limit_summary_length( 55 ) );
	}
}
