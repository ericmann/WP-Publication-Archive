<?php
/**
 * Class WP_Publication_Archive_Utilities
 */

/**
 * Static class containing specific helper functions that make life easier
 * for the plugin as a whole.
 *
 * @author Eric Mann
 * @since  3.0
 */
class WP_Publication_Archive_Utilities {
	/**
	 * @var WP_Publication_Archive_Utilities
	 */
	protected static $instance = false;

	/**
	 * Hidden constructor
	 */
	protected function __construct() {
		// Wireup actions
		add_action( 'widgets_init', array( $this, 'register_widget' ) );

		// Wireup filters
	}

	/**
	 * Get the initialized object.
	 *
	 * @return WP_Publication_Archive_Utilities
	 */
	public static function get_instance() {
		return self::$instance;
	}

	/**
	 * Bootstrapper to create an instance (eager instantiation) of the object.
	 *
	 * If the object is already initialized, throw an error.
	 *
	 * @throws Exception
	 */
	public static function create_instance() {
		if ( is_a( self::$instance, 'WP_Publication_Archive_Utilities' ) ) {
			throw new Exception( __( 'Utilities object already initialized', 'wp_pubarch_translate' ) );
		}

		self::$instance = new self();
	}

	/***********************************************************/
	/*                  Utility Functionality                  */
	/***********************************************************/

	/**
	 * Register all bundled widgets
	 */
	public function register_widget() {
		register_widget( 'WP_Publication_Archive_Widget' );
		register_widget( 'WP_Publication_Archive_Cat_Count_Widget' );
		//register_widget( 'WP_Publication_Archive_Category_Widget' );
	}

	/**
	 * Display or retrieve the HTML dropdown list of categories.
	 *
	 * The list of arguments is below:
	 *     'show_option_all' (string) - Text to display for showing all categories.
	 *     'show_option_none' (string) - Text to display for showing no categories.
	 *     'orderby' (string) default is 'ID' - What column to use for ordering the categories.
	 *     'order' (string) default is 'ASC' - What direction to order categories.
	 *     'show_count' (bool|int) default is 0 - Whether to show how many posts are in the category.
	 *     'hide_empty' (bool|int) default is 1 - Whether to hide categories that don't have any posts attached to them.
	 *     'child_of' (int) default is 0 - See {@link get_categories()}.
	 *     'exclude' (string) - See {@link get_categories()}.
	 *     'echo' (bool|int) default is 1 - Whether to display or retrieve content.
	 *     'depth' (int) - The max depth.
	 *     'tab_index' (int) - Tab index for select element.
	 *     'name' (string) - The name attribute value for select element.
	 *     'id' (string) - The ID attribute value for select element. Defaults to name if omitted.
	 *     'class' (string) - The class attribute value for select element.
	 *     'selected' (int) - Which category ID is selected.
	 *     'taxonomy' (string) - The name of the taxonomy to retrieve. Defaults to category.
	 *
	 * The 'hierarchical' argument, which is disabled by default, will override the
	 * depth argument, unless it is true. When the argument is false, it will
	 * display all of the categories. When it is enabled it will use the value in
	 * the 'depth' argument.
	 *
	 * @since 3.0
	 *
	 * @param string|array $args Optional. Override default arguments.
	 *
	 * @return string HTML content only if 'echo' argument is 0.
	 */
	public function dropdown_categories( $args = '' ) {
		$defaults = array(
			'show_option_all'  => '',
			'show_option_none' => '',
			'orderby'          => 'id',
			'order'            => 'ASC',
			'show_count'       => 0,
			'hide_empty'       => 1,
			'child_of'         => 0,
			'exclude'          => '',
			'echo'             => 1,
			'selected'         => 0,
			'name'             => 'wp_pubarch_cat',
			'id'               => '',
			'class'            => 'postform',
			'depth'            => 0,
			'tab_index'        => 0,
			'taxonomy'         => 'category',
			'hide_if_empty'    => false,
			'post_types'       => array( 'publication' )
		);

		$defaults['selected'] = ( is_category() ) ? get_query_var( 'cat' ) : 0;

		$args = wp_parse_args( $args, $defaults );

		if ( ! isset( $args['pad_counts'] ) && $args['show_count'] ) {
			$args['pad_counts'] = true;
		}

		$tab_index_attribute = '';
		if ( (int) $args['tab_index'] > 0 ) {
			$tab_index_attribute = ' tabindex="' . $args['tab_index'] . '"';
		}

		$categories = $this->get_terms( $args['taxonomy'], $args );
		var_dump( $categories );
		$name       = esc_attr( $args['name'] );
		$class      = esc_attr( $args['class'] );
		$id         = $args['id'] ? esc_attr( $args['id'] ) : $name;

		if ( ! $args['hide_if_empty'] || ! empty( $categories ) ) {
			$output = "<select name='$name' id='$id' class='$class' $tab_index_attribute>\n";
		} else {
			$output = '';
		}

		if ( empty( $categories ) && ! $r['hide_if_empty'] && ! empty( $args['show_option_none'] ) ) {
			$args['show_option_none'] = apply_filters( 'list_cats', $args['show_option_none'] );
			$output .= "\t<option value='-1' selected='selected'>" . $args['show_option_none'] . "</option>\n";
		}

		if ( ! empty( $categories ) ) {

			if ( $args['show_option_all'] ) {
				$show_option_all = apply_filters( 'list_cats', $args['show_option_all'] );
				$selected        = ( '0' === strval( $args['selected'] ) ) ? " selected='selected'" : '';
				$output .= "\t<option value='0'$selected>$show_option_all</option>\n";
			}

			if ( $args['show_option_none'] ) {
				$show_option_none = apply_filters( 'list_cats', $args['show_option_none'] );
				$selected         = ( '-1' === strval( $args['selected'] ) ) ? " selected='selected'" : '';
				$output .= "\t<option value='-1'$selected>$show_option_none</option>\n";
			}

			$depth = - 1; // Flat.

			$output .= walk_category_dropdown_tree( $categories, $depth, $args );
		}

		if ( ! $args['hide_if_empty'] || ! empty( $categories ) )
			$output .= "</select>\n";

		$output = apply_filters( 'wp_dropdown_cats', $output );

		if ( $args['echo'] ) {
			echo $output;
		}

		return $output;
	}

	public function list_categories() {

	}

	/**
	 * Wrapper for standard get_terms() to allow filtering by custom post type.
	 *
	 * @see http://core.trac.wordpress.org/ticket/18106
	 *
	 * @param string|array $taxonomies
	 * @param array        $args
	 *
	 * @return array|WP_Error
	 */
	public function get_terms( $taxonomies, $args = array() ) {
		if ( ! empty( $args['post_types'] ) ) {
			$args['post_types'] = (array) $args['post_types'];
			add_filter( 'terms_clauses', array( $this, 'filter_terms_by_cpt' ), 10, 3 );
		}
		$terms = get_terms( $taxonomies, $args );
		remove_filter( 'terms_clauses', array( $this, 'filter_terms_by_cpt' ), 10 );

		return $terms;
	}

	/**
	 * Filter the terms query by custom post type
	 *
	 * @see http://core.trac.wordpress.org/ticket/18106
	 *
	 * @param array        $pieces
	 * @param string|array $tax
	 * @param array        $args
	 *
	 * @return array
	 */
	public function filter_terms_by_cpt( $pieces, $tax, $args ) {
		global $wpdb;

		//Don't use db count
		$pieces['fields'] .= ", COUNT(*) ";

		//Join extra tables to restrict by post type.
		$pieces['join'] .= " INNER JOIN $wpdb->term_relationships AS r ON r.term_taxonomy_id = tt.term_taxonomy_id
							 INNER JOIN $wpdb->posts AS p ON p.ID = r.object_id ";

		//Restrict by post type and Group by term_id for COUNTing.
		$post_types_str = implode( ',', $args['post_types'] );
		$pieces['where'] .= $wpdb->prepare( " AND p.post_type IN(%s) GROUP BY t.term_id", $post_types_str );

		return $pieces;
	}
}

// Initialize the singleton
WP_Publication_Archive_Utilities::create_instance();