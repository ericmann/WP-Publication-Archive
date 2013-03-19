<?php
/**
 * Class WP_Publication_Archive_Cat_Count_Widget
 */

/**
 * This widget displays a list of publication categories with a count
 * of the publications in each category.  This is similar to the built-in
 * category count widget for WordPress posts.
 *
 * @see    WP_Widget
 * @author Eric Mann
 * @since  3.0
 */
class WP_Publication_Archive_Cat_Count_Widget extends WP_Widget {
	/**
	 * @var WP_Publication_Archive_Utilities
	 */
	protected $utilities;

	/**
	 * Default constructor
	 */
	public function __construct() {
		$widget_ops = array(
			'classname'   => 'widget_pub_categories',
			'description' => __( 'A list or dropdown of publication categories.', 'wp_pubarch_translate' )
		);
		parent::__construct( false, __( 'Publication Categories', 'wp_pubarch_translate' ), $widget_ops );

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
		$instance = wp_parse_args( (array) $instance, array( 'title' => '' ) );
		$title    = esc_attr( $instance['title'] );
		$count    = isset( $instance['count'] ) ? (bool) $instance['count'] : false;
		$dropdown = isset( $instance['dropdown'] ) ? (bool) $instance['dropdown'] : false;

		?>
		<p>
			<label
				for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'wp_pubarch_translate' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>"
			       name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>"/>
		</p>

		<p>
			<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id( 'dropdown' ); ?>"
			       name="<?php echo $this->get_field_name( 'dropdown' ); ?>"<?php checked( $dropdown ); ?> />
			<label
				for="<?php echo $this->get_field_id( 'dropdown' ); ?>"><?php _e( 'Display as dropdown', 'wp_pubarch_translate' ); ?></label><br/>

			<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id( 'count' ); ?>"
			       name="<?php echo $this->get_field_name( 'count' ); ?>"<?php checked( $count ); ?> />
			<label
				for="<?php echo $this->get_field_id( 'count' ); ?>"><?php _e( 'Show publication counts', 'wp_pubarch_translate' ); ?></label><br/>
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
		$instance['count']    = ! empty( $new_instance['count'] ) ? 1 : 0;
		$instance['dropdown'] = ! empty( $new_instance['dropdown'] ) ? 1 : 0;

		return $instance;
	}

	/**
	 * Echo the content of the widget to the front-end user interface.
	 *
	 * @param array $args
	 * @param array $instance
	 */
	public function widget( $args, $instance ) {
		$title    = apply_filters( 'widget_title', empty( $instance['title'] ) ? __( 'Publication Categories', 'wp_pubarch_translate' ) : $instance['title'], $instance, $this->id_base );
		$count    = ! empty( $instance['count'] ) ? '1' : '0';
		$dropdown = ! empty( $instance['dropdown'] ) ? '1' : '0';

		echo $args['before_widget'];
		if ( $title )
			echo $args['before_title'] . $title . $args['after_title'];

		$cat_args = array( 'orderby' => 'name', 'show_count' => $count );

		if ( $dropdown ) {
			$cat_args['show_option_none'] = __( 'Select Category', 'wp_pubarch_translate' );

			$this->utilities->dropdown_categories( apply_filters( 'widget_categories_dropdown_args', $cat_args ) );
			?>

			<script type='text/javascript'>
				/* <![CDATA[ */
				var dropdown = document.getElementById( "wp_pubarch_cat" );
				function onCatChange () {
					if ( dropdown.options[dropdown.selectedIndex].value > 0 ) {
						location.href = "<?php echo home_url(); ?>/?post_type=publication&cat=" + dropdown.options[dropdown.selectedIndex].value;
					}
				}
				dropdown.onchange = onCatChange;
				/* ]]> */
			</script>

		<?php
		} else {
			?>
			<ul>
				<?php
				$cat_args['title_li'] = '';

				$this->utilities->list_categories( apply_filters( 'widget_categories_args', $cat_args ) );
				?>
			</ul>
		<?php
		}

		echo $args['after_widget'];
	}
}