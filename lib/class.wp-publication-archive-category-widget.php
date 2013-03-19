<?php
/**
 * Class WP_Publication_Archive_Category_Widget
 */

/**
 * This widget pulls the category loaded by the main query and displays
 * any publications in that category, optionally listing an excerpt for
 * each.  Titles will link to publication landing pages.
 *
 * @see WP_Widget
 * @author Eric Mann
 * @since 3.0
 */
class WP_Publication_Archive_Category_Widget extends WP_Widget {
	/** @var WP_Publication_Archive_Utilities */
	protected $utilities;

	/**
	 * Default constructor
	 */
	public function __construct() {
		$widget_ops = array(
			'classname'   => 'widget_pub_related',
			'description' => __( 'A list of related publications (based on category).', 'wp_pubarch_translate' )
		);
		parent::__construct( false, __( 'Related Publications', 'wp_pubarch_translate' ), $widget_ops );

		$this->utilities = WP_Publication_Archive_Utilities::get_instance();
	}

	/**
	 * Output the settings update form.
	 *
	 * @param array $instance
	 *
	 * @return void
	 */
	public function form( $instance ) {
		//Defaults
		$instance = wp_parse_args(
			(array) $instance,
			array(
			     'title' => __( 'Related Publications' ),
			     'count' => 5
			)
		);
		$title    = esc_attr( $instance['title'] );
		$count    = esc_attr( $instance['count'] );

		?>
		<p>
			<label
				for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'wp_pubarch_translate' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>"
			       name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>"/>
		</p>

		<p>
			<label
				for="<?php echo $this->get_field_id( 'count' ); ?>"><?php _e( 'Show publication counts', 'wp_pubarch_translate' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'count' ); ?>"
			       name="<?php echo $this->get_field_name( 'count' ); ?>" type="text" value="<?php echo $count; ?>" />
		</p>
	<?php
	}

	/**
	 * Update a particular widget instance.
	 *
	 * @param array $new_instance
	 * @param array $old_instance
	 *
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {
		$instance             = $old_instance;
		$instance['title']    = strip_tags( $new_instance['title'] );
		$instance['count']    = (int) $new_instance['count'];

		return $instance;
	}

	/**
	 * Echo the content of the widget to the front-end user interface.
	 *
	 * @param array $args
	 * @param array $instance
	 */
	public function widget( $args, $instance ) {
		$title    = apply_filters( 'widget_title', empty( $instance['title'] ) ? __( 'Related Publications', 'wp_pubarch_translate' ) : $instance['title'], $instance, $this->id_base );
		$count    = (int) $instance['count'];

		echo $args['before_widget'];
		if ( $title ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}

		$query_args = array(
			'numberposts' => $count,
			'post_type' => 'publication',
			'orderby' => 'post_date',
			'order' => 'DESC'
		);

		// Grab the current cagtegory
		$queried = get_queried_object();
		if ( null !== $queried ) {
			// Use the current post's categories
			$cats = wp_get_post_categories( $queried->ID );

			$query_args['category__in'] = $cats;
		}

		echo '<ul>';

		$publications = get_posts( $query_args );

		add_filter( 'excerpt_length', array( $this, 'limit_summary_length' ) );
		foreach( $publications as $post ) {
			$publication = new WP_Publication_Archive_Item( $post );

			echo '<li>';
			$publication->the_title();
			echo '<p>' . $publication->summary . '</p>';

			echo '</li>';
		}
		remove_filter( 'excerpt_length', array( $this, 'limit_summary_length' ) );

		echo '</ul>';

		echo $args['after_widget'];
	}

	public function limit_summary_length( $length ) {
		return apply_filters( 'wpa-widget-summary-length', 20 );
	}
}