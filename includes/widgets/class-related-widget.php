<?php
/**
 * Implements SPEC.md §8 Phase 0 item 4: 3.0.1's
 * WP_Publication_Archive_Category_Widget, aliased back by that name. Not
 * final; keeps the 3.0.1 method names, parameters, defaults and public
 * properties, with phpdoc types only.
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA\Widgets;

use WPPA\Hooks;
use WPPA\Keys;
use WPPA\Publication_Item;

class Related_Widget extends \WP_Widget {

	/**
	 * @var \WP_Publication_Archive_Utilities|false
	 */
	protected $utilities;

	/**
	 * Default constructor
	 */
	public function __construct() {
		$widget_ops = array(
			'classname'   => 'widget_pub_related',
			'description' => __( 'A list of related publications (based on category).', 'wp-publication-archive' ),
		);
		parent::__construct( Keys::WIDGET_RELATED_ID_BASE, __( 'Related Publications', 'wp-publication-archive' ), $widget_ops );

		$this->utilities = \WP_Publication_Archive_Utilities::get_instance();
	}

	/**
	 * Output the settings update form.
	 *
	 * @param array<string, mixed> $instance
	 *
	 * @return void
	 */
	public function form( $instance ) {
		// Defaults
		$instance = wp_parse_args(
			(array) $instance,
			array(
				'title' => __( 'Related Publications', 'wp-publication-archive' ),
				'count' => Keys::DEFAULT_RELATED_COUNT,
			)
		);
		$title    = esc_attr( $instance['title'] );
		$count    = esc_attr( $instance['count'] );

		?>
		<p>
			<label
				for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'wp-publication-archive' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
			       name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>"/>
		</p>

		<p>
			<label
				for="<?php echo esc_attr( $this->get_field_id( 'count' ) ); ?>"><?php esc_html_e( 'Show publication counts', 'wp-publication-archive' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'count' ) ); ?>"
			       name="<?php echo esc_attr( $this->get_field_name( 'count' ) ); ?>" type="text" value="<?php echo esc_attr( $count ); ?>" />
		</p>
	<?php
	}

	/**
	 * Update a particular widget instance.
	 *
	 * @param array<string, mixed> $new_instance
	 * @param array<string, mixed> $old_instance
	 *
	 * @return array<string, mixed>
	 */
	public function update( $new_instance, $old_instance ) {
		$instance           = $old_instance;
		$instance['title']  = strip_tags( $new_instance['title'] ); // phpcs:ignore WordPressVIPMinimum.Functions.StripTags.StripTagsOneParameter -- reason: 3.0.1 behaviour, widget title text only.
		$instance['count']  = (int) $new_instance['count'];

		return $instance;
	}

	/**
	 * Echo the content of the widget to the front-end user interface.
	 *
	 * @param array<string, string> $args
	 * @param array<string, mixed>  $instance
	 *
	 * @return void
	 */
	public function widget( $args, $instance ) {
		$title = Hooks::widget_title( empty( $instance['title'] ) ? __( 'Related Publications', 'wp-publication-archive' ) : $instance['title'], $instance, $this->id_base );
		$count = (int) $instance['count'];

		echo wp_kses_post( $args['before_widget'] );
		if ( $title ) {
			echo wp_kses_post( $args['before_title'] . $title . $args['after_title'] );
		}

		$query_args = array(
			'numberposts' => $count,
			'post_type'   => Keys::POST_TYPE,
			'orderby'     => 'post_date',
			'order'       => 'DESC',
		);

		// Grab the current category.
		$queried = get_queried_object();
		if ( null !== $queried && isset( $queried->ID ) ) {
			// Use the current post's categories.
			$cats = wp_get_post_categories( $queried->ID );

			$query_args['category__in'] = $cats;
		}

		echo '<ul>';

		$publications = get_posts( $query_args );

		\WPPA\Plugin::instance()->templates()->with_widget_summary_length(
			function () use ( $publications ) {
				foreach ( $publications as $post ) {
					$publication = new Publication_Item( $post );

					echo '<li>';
					$publication->the_title();
					echo '<p>' . wp_kses_post( $publication->summary ) . '</p>';

					echo '</li>';
				}
			}
		);

		echo '</ul>';

		echo wp_kses_post( $args['after_widget'] );
	}

	/**
	 * 3.0.1 method, never hooked.
	 *
	 * @param int $length
	 *
	 * @return int
	 */
	public function limit_summary_length( $length ) {
		unset( $length );

		return Hooks::widget_summary_length( Keys::DEFAULT_WIDGET_SUMMARY_LENGTH );
	}
}
