<?php
/**
 * Implements SPEC.md §8 Phase 0 item 4 and §6.6: 3.0.1's
 * WP_Publication_Archive_Cat_Count_Widget, aliased back by that name.
 * Not final; keeps the 3.0.1 method names, parameters, defaults and public
 * properties, with phpdoc types only.
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA\Widgets;

use WPPA\Keys;
use WPPA\Hooks;

class Category_Count_Widget extends \WP_Widget {

	/**
	 * @var \WP_Publication_Archive_Utilities|false
	 */
	protected $utilities;

	/**
	 * Default constructor
	 */
	public function __construct() {
		$widget_ops = array(
			'classname'   => 'widget_pub_categories',
			'description' => __( 'A list or dropdown of publication categories.', 'wp-publication-archive' ),
		);
		parent::__construct( Keys::WIDGET_CAT_COUNT_ID_BASE, __( 'Publication Categories', 'wp-publication-archive' ), $widget_ops );

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
		$instance = wp_parse_args( (array) $instance, array( 'title' => '' ) );
		$count    = isset( $instance['count'] ) ? (bool) $instance['count'] : false;
		$dropdown = isset( $instance['dropdown'] ) ? (bool) $instance['dropdown'] : false;

		?>
		<p>
			<label
				for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'wp-publication-archive' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
			       name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $instance['title'] ); ?>"/>
		</p>

		<p>
			<input type="checkbox" class="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'dropdown' ) ); ?>"
			       name="<?php echo esc_attr( $this->get_field_name( 'dropdown' ) ); ?>"<?php checked( $dropdown ); ?> />
			<label
				for="<?php echo esc_attr( $this->get_field_id( 'dropdown' ) ); ?>"><?php esc_html_e( 'Display as dropdown', 'wp-publication-archive' ); ?></label><br/>

			<input type="checkbox" class="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'count' ) ); ?>"
			       name="<?php echo esc_attr( $this->get_field_name( 'count' ) ); ?>"<?php checked( $count ); ?> />
			<label
				for="<?php echo esc_attr( $this->get_field_id( 'count' ) ); ?>"><?php esc_html_e( 'Show publication counts', 'wp-publication-archive' ); ?></label><br/>
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
		$instance              = $old_instance;
		$instance['title']     = strip_tags( $new_instance['title'] ); // phpcs:ignore WordPressVIPMinimum.Functions.StripTags.StripTagsOneParameter -- reason: 3.0.1 behaviour, widget title text only.
		$instance['count']     = ! empty( $new_instance['count'] ) ? 1 : 0;
		$instance['dropdown']  = ! empty( $new_instance['dropdown'] ) ? 1 : 0;

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
		$title    = Hooks::widget_title( empty( $instance['title'] ) ? __( 'Publication Categories', 'wp-publication-archive' ) : $instance['title'], $instance, $this->id_base );
		$count    = ! empty( $instance['count'] ) ? '1' : '0';
		$dropdown = ! empty( $instance['dropdown'] ) ? '1' : '0';

		echo wp_kses_post( $args['before_widget'] );
		if ( $title ) {
			echo wp_kses_post( $args['before_title'] . $title . $args['after_title'] );
		}

		$cat_args = array(
			'orderby'    => 'name',
			'show_count' => $count,
		);

		if ( $dropdown ) {
			$cat_args['show_option_none'] = __( 'Select Category', 'wp-publication-archive' );

			if ( $this->utilities ) {
				$this->utilities->dropdown_categories( Hooks::widget_categories_dropdown_args( $cat_args ) );
			}
			?>

			<script type='text/javascript'>
				/* <![CDATA[ */
				var dropdown = document.getElementById( "<?php echo esc_js( Keys::FIELD_CAT_DROPDOWN ); ?>" );
				function onCatChange () {
					if ( dropdown.options[dropdown.selectedIndex].value > 0 ) {
						location.href = "<?php echo esc_js( home_url() ); ?>/?post_type=<?php echo esc_js( Keys::POST_TYPE ); ?>&cat=" + dropdown.options[dropdown.selectedIndex].value;
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

				if ( $this->utilities ) {
					$this->utilities->list_categories( Hooks::widget_categories_args( $cat_args ) );
				}
				?>
			</ul>
		<?php
		}

		echo wp_kses_post( $args['after_widget'] );
	}
}
