<?php
/**
 * This class builds the default archive widget.
 *
 * @see WP_Widget
 * @author Matthew Eppelshiemer
 * @since 2.5
 */
class WP_Publication_Archive_Widget extends WP_Widget {
	/**
	 * Default constructor
	 */
	public function __construct() {
		parent::__construct(
			false,
			__( 'Publication Archive Widget', 'wp_pubarch_translate' ),
			array(
			     'classname' => 'publication_archive',
			     'description' => __( 'Display a list of publications.', 'wp_pubarch_translate' )
			)
		);
	}

	/**
	 * Output the settings update form.
	 *
	 * @param array $instance Current settings
	 *
	 * @return void
	 */
	public function form( $instance ) {
		if ( $instance && isset( $instance['title'] ) ) {
			$title = esc_attr( $instance['title'] );
		} else {
			$title = esc_attr__( 'Publication Archive', 'wp_pubarch_translate' );
		}

		if ( $instance && isset( $instance['number'] ) ) {
			$number = esc_attr( $instance['number'] );
		} else {
			$number = 5;
		}

		if ( $instance && isset( $instance['orderby'] ) ) {
			$orderby = esc_attr( $instance['orderby'] );
		} else {
			$orderby = 'menu_order';
		}

		$output = "<p>" . __( 'Title', 'wp_pubarch_translate' ) . ": <input class='widefat' name='" . $this->get_field_name( 'title' ) . "' type='text' value='" . $title . "' /></p>";

		$output .= "<p>" . __( 'Number of publications to display', 'wp_pubarch_translate' ) . ": <input class='widefat' name='" . $this->get_field_name( 'number' ) . "' type='text' value='" . esc_attr( $number ) . "' /> <em class='help'>" . __( 'Leave blank for no limit.', 'wp_pubarch_translate' ) . "</em></p>";

		$output .= "<p>" . __( 'Order by', 'wp_pubarch_translate' ) . ": <select name='" . $this->get_field_name( 'orderby' ) . "'>";
		$output .= "<option value='menu_order' " . selected( $orderby, 'menu_order', false ) . ">" . __( 'Manual (drag and drop)', 'wp_pubarch_translate' ) . "</option>";
		$output .= "<option value='date' " . selected( $orderby, 'date', false ) . ">" . __( 'Latest (publish date)', 'wp_pubarch_translate' ) . "</option>";
		$output .= "</select></p>";

		echo $output;
	}

	/**
	 * Update a particular widget instance.
	 *
	 * This function builds out an instance array based on data passed through the $new_instance variable.
	 * Data passed in is never saved directly.
	 *
	 * @param array $new_instance New settings for the instance as input by the user.
	 * @param array $old_instance Old settings for the instance.
	 * @return array Settings to save or Falst to cancel.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['number'] = strip_tags( $new_instance['number'] );
		$instance['orderby'] = strip_tags( $new_instance['orderby'] );

		return $instance;
	}

	/**
	 * Echo the content of the widget to the front-end user interface.
	 *
	 * Dynamically loads a template for the widget display. Default template is stored in
	 * includes/template.wppa_widget.php. If a similarly-named file exists in the current theme, the theme's
	 * version will be used instead.
	 *
	 * @param array $argsDisplay arguments including before_title, after_title, before_widget, and after_widget
	 * @param array $instance Settings for this particular instance.
	 * @uses apply_filters() Calls 'widget_title' for the widget title.
	 * @uses apply_filters() Calls 'wppa_widget_template' to get the name of the widget template file.
	 *
	 * @todo Support 'publication_cat' and 'publication_id' in the $query_args array.
	 */
	public function widget( $args, $instance ) {
		/**
		 * @var string $before_widget
		 * @var string $after_widget
		 * @var string $before_title
		 * @var string $after_title
		 */
		extract( $args );
		$title = apply_filters( 'widget_title', $instance['title'] );

		// Get publications to display
		$query_args = array();

		if ( isset( $instance['number'] ) ) {
			$query_args['posts_per_page'] = $instance['number'];
		}
		if ( isset( $instance['order_by'] ) ) {
			$query_args['order_by'] = $instance['order_by'];
		}

		// Globalize our publications wrapper so the template can use it.
		global $wppa_publications;

		$wppa_publications = WP_Publication_Archive::query_publications( $query_args );

		echo $before_widget;

		if ( $title ) {
			echo $before_title . $title . $after_title;
		}

		// Include widget template. Can be overridden by a theme.
		$template_name = apply_filters( 'wppa_widget_template', 'template.wppa_widget.php' );
		$path = locate_template( $template_name );
		if ( empty( $path ) ) {
			$path = WP_PUB_ARCH_DIR . 'includes/' . $template_name;
		}

		include( $path );

		echo $after_widget;

		// Clean up our globals
		unset( $wppa_publications );
	}
}