<?php
class publication_archive_widget extends WP_Widget {

	// register the widget
	public function publication_archive_widget() {
		$widget_options = array(
			'classname' => 'publication_archive',
			'description' => 'Display a list of publications.'
		);
		$this->WP_Widget( 'publication_archive_widget', 'Publication Archive Widget' );
	}

	// display the widget
	public function widget( $args, $instance ) { 
		global $post;

		// Get publications to display
		$query_args = array();
		
		/* @TODO SUPPORT THESE LATER	
		if ( isset( $instance['publication_cat'] ) )
			$query_args['cat'] = $instance['publication_cat'];
		if ( isset( $instance['publication_id'] ) )
			$query_args['id'] = $instance['publication_id'];
		*/
		
		if ( isset( $instance['number'] ) )
			$query_args['posts_per_page'] = $instance['number'];
		if ( isset( $instance['order_by'] ) )
			$query_args['order_by'] = $instance['order_by'];
		
		$publications = WP_Publication_Archive::query_publications( $query_args );
		
		if ( $publications->have_posts() ) {

			// begin html
			$output = '';
			if ( ! empty( $args['before_widget'] ) )
				$output .= $args['before_widget'];
			if ( ! empty( $instance['title'] ) )
				$output .= "<h3 class='widgettitle'>" . $instance['title'] . "</h3>";
			
			$output .= "<ul>";
			
			while ( $publications->have_posts() ) {
				$publications->the_post();
				
				// We're doing this instad of `get_the_title()` to avoid include '(Download Publication)'
				$pub_title = $post->post_title; 

				$output .= "<li><a href='" . get_permalink() . "' title='$pub_title'>$pub_title</a></li>";
			
			}
			
			$output .= "</ul>";
			
			if ( ! empty( $args['after_widget'] ) )
				$output .= $args['after_widget'];
			
			echo $output;
		
		}
		
		wp_reset_query();

	}
	
	// build the widget's admin form
	public function form( $instance ) {
		$defaults = array(
			'title' => 'Publication Archive',
			'number' => 5,
			'orderby' => 'menu_order'
		);
		$instance = wp_parse_args( (array) $instance, $defaults );
		
		if ( isset( $instance['title'] ) ) {
			$title = $instance['title'];
		} else {
			$title = '';
		}
		$number = $instance['number'];
		$orderby = $instance['orderby'];

		$output = "<p>" . __( 'Title', 'rli_testimonials' ) . ": <input class='widefat' name='" . $this->get_field_name( 'title' ) . "' type='text' value='" . esc_attr( $title ) . "' /></p>";

		$output .= "<p>" . __( 'Number of publications to display', 'rli_testimonials' ) . ": <input class='widefat' name='" . $this->get_field_name( 'number' ) . "' type='text' value='" . esc_attr( $number ) . "' /> <em class='help'>Leave blank for no limit.</em></p>";

		$output .= "<p>" . __( 'Order by', 'rli_testimonials' ) . ": <select name='" . $this->get_field_name( 'orderby' ) . "'>";
			$output .= "<option value='menu_order' " . selected( $orderby, 'menu_order', false ) . ">" . __( 'Manual (drag and drop)', 'rli_testimonials' ) . "</option>";
			$output .= "<option value='date' " . selected( $orderby, 'date', false ) . ">" . __( 'Latest (publish date)', 'rli_testimonials' ) . "</option>";
		$output .= "</select></p>";

		echo $output;
	}

	// save widget settings
	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['number'] = strip_tags( $new_instance['number'] );
		$instance['orderby'] = strip_tags( $new_instance['orderby'] );

		return $instance;
	}


}