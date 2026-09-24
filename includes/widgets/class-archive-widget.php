<?php
/**
 * Implements SPEC.md §8 Phase 0 item 4: 3.0.1's WP_Publication_Archive_Widget,
 * aliased back by that name. Not final; keeps the 3.0.1 method names,
 * parameters, defaults and public properties, with phpdoc types only.
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA\Widgets;

use WPPA\Hooks;
use WPPA\Keys;

class Archive_Widget extends \WP_Widget {

	/**
	 * Default constructor
	 */
	public function __construct() {
		parent::__construct(
			Keys::WIDGET_ARCHIVE_ID_BASE,
			__( 'Publication Archive Widget', 'wp-publication-archive' ),
			array(
				'classname'   => 'publication_archive',
				'description' => __( 'Display a list of publications.', 'wp-publication-archive' ),
			)
		);
	}

	/**
	 * Output the settings update form.
	 *
	 * @param array<string, mixed> $instance Current settings.
	 *
	 * @return void
	 */
	public function form( $instance ) {
		if ( $instance && isset( $instance['title'] ) ) {
			$title = esc_attr( $instance['title'] );
		} else {
			$title = esc_attr__( 'Publication Archive', 'wp-publication-archive' );
		}

		if ( $instance && isset( $instance['number'] ) ) {
			$number = esc_attr( $instance['number'] );
		} else {
			$number = Keys::DEFAULT_ARCHIVE_WIDGET_NUMBER;
		}

		if ( $instance && isset( $instance['orderby'] ) ) {
			$orderby = esc_attr( $instance['orderby'] );
		} else {
			$orderby = 'menu_order';
		}

		$output = '<p>' . __( 'Title', 'wp-publication-archive' ) . ": <input class='widefat' name='" . $this->get_field_name( 'title' ) . "' type='text' value='" . $title . "' /></p>";

		$output .= '<p>' . __( 'Number of publications to display', 'wp-publication-archive' ) . ": <input class='widefat' name='" . $this->get_field_name( 'number' ) . "' type='text' value='" . esc_attr( $number ) . "' /> <em class='help'>" . __( 'Leave blank for no limit.', 'wp-publication-archive' ) . '</em></p>';

		$output .= '<p>' . __( 'Order by', 'wp-publication-archive' ) . ": <select name='" . $this->get_field_name( 'orderby' ) . "'>";
		$output .= "<option value='menu_order' " . selected( $orderby, 'menu_order', false ) . '>' . __( 'Manual (drag and drop)', 'wp-publication-archive' ) . '</option>';
		$output .= "<option value='date' " . selected( $orderby, 'date', false ) . '>' . __( 'Latest (publish date)', 'wp-publication-archive' ) . '</option>';
		$output .= '</select></p>';

		echo wp_kses_post( $output );
	}

	/**
	 * Update a particular widget instance.
	 *
	 * This function builds out an instance array based on data passed through
	 * the $new_instance variable. Data passed in is never saved directly.
	 *
	 * @param array<string, mixed> $new_instance New settings for the instance as input by the user.
	 * @param array<string, mixed> $old_instance Old settings for the instance.
	 *
	 * @return array<string, mixed> Settings to save.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance            = $old_instance;
		$instance['title']   = strip_tags( $new_instance['title'] ); // phpcs:ignore WordPressVIPMinimum.Functions.StripTags.StripTagsOneParameter -- reason: 3.0.1 behaviour, widget title text only.
		$instance['number']  = strip_tags( $new_instance['number'] ); // phpcs:ignore WordPressVIPMinimum.Functions.StripTags.StripTagsOneParameter -- reason: 3.0.1 behaviour, numeric field text only.
		$instance['orderby'] = strip_tags( $new_instance['orderby'] ); // phpcs:ignore WordPressVIPMinimum.Functions.StripTags.StripTagsOneParameter -- reason: 3.0.1 behaviour, select field text only.

		return $instance;
	}

	/**
	 * Echo the content of the widget to the front-end user interface.
	 *
	 * Dynamically loads a template for the widget display. Default template
	 * is `templates/classic/template.wppa_widget.php`. If a similarly-named
	 * file exists in the current theme, the theme's version is used instead.
	 *
	 * @param array<string, string> $args     Display arguments including before_title, after_title, before_widget, and after_widget.
	 * @param array<string, mixed>  $instance Settings for this particular instance.
	 *
	 * @return void
	 */
	public function widget( $args, $instance ) {
		$before_widget = $args['before_widget'];
		$after_widget  = $args['after_widget'];
		$before_title  = $args['before_title'];
		$after_title   = $args['after_title'];

		$title = Hooks::widget_title( $instance['title'] );

		// Get publications to display.
		$query_args = array();

		if ( isset( $instance['number'] ) ) {
			$query_args['posts_per_page'] = $instance['number'];
		}
		if ( isset( $instance['order_by'] ) ) {
			// 3.0.1 behaviour: the widget instance stores 'orderby' (see
			// form()/update()), but widget() reads 'order_by', which is
			// never set. This key mismatch is preserved verbatim.
			$query_args['order_by'] = $instance['order_by'];
		}

		// Globalize our publications wrapper so the template can use it.
		global $wppa_publications;

		$wppa_publications = \WPPA\Plugin::instance()->post_type()->query( $query_args );

		echo wp_kses_post( $before_widget );

		if ( $title ) {
			echo wp_kses_post( $before_title . $title . $after_title );
		}

		// Include widget template. Can be overridden by a theme.
		$template_name = Hooks::widget_template( Keys::TEMPLATE_WIDGET );
		$path          = \WPPA\Plugin::instance()->templates()->locate( $template_name );

		include $path;

		echo wp_kses_post( $after_widget );

		// Clean up our globals.
		unset( $wppa_publications );
	}
}
