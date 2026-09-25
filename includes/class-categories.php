<?php
/**
 * Implements SPEC.md §8 Phase 0 item 4: 3.0.1's category dropdown/list
 * helpers, scoped to publications. The term_link/terms_clauses hijacks are a
 * P3 port: Plugin registers each filter once, and it acts only while a
 * private flag is set in try/finally around the call that needs it.
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA;

final class Categories {

	private Flags $flags;

	/**
	 * True while list_categories() is inside walk_category_tree().
	 */
	private bool $term_link_scope = false;

	/**
	 * True while get_terms() is inside core get_terms().
	 */
	private bool $terms_clauses_scope = false;

	/**
	 * @var list<string>
	 */
	private array $scoped_post_types = array();

	public function __construct( Flags $flags ) {
		$this->flags = $flags;
	}

	public function flags(): Flags {
		return $this->flags;
	}

	/**
	 * D3: the allowed markup for dropdown_categories()'s <select>/<option>
	 * output.
	 */
	private function kses_dropdown( string $output ): string {
		return wp_kses(
			$output,
			array(
				'select' => array(
					'name'     => true,
					'id'       => true,
					'class'    => true,
					'tabindex' => true,
				),
				'option' => array(
					'value'    => true,
					'selected' => true,
				),
			)
		);
	}

	/**
	 * 3.0.1 dropdown_categories().
	 *
	 * @param string|array<string, mixed> $args
	 *
	 * @return string
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
			'exclude'          => '', // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- reason: 3.0.1 behaviour, get_terms() exclude argument, not a post query.
			'echo'             => 1,
			'selected'         => 0,
			'name'             => Keys::FIELD_CAT_DROPDOWN,
			'id'               => '',
			'class'            => 'postform',
			'depth'            => 0,
			'tab_index'        => 0,
			'taxonomy'         => 'category',
			'hide_if_empty'    => false,
			'post_types'       => array( Keys::POST_TYPE ),
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

		$name  = esc_attr( $args['name'] );
		$class = esc_attr( $args['class'] );
		$id    = $args['id'] ? esc_attr( $args['id'] ) : $name;

		if ( ! $args['hide_if_empty'] || ! empty( $categories ) ) {
			$output = "<select name='$name' id='$id' class='$class' $tab_index_attribute>\n";
		} else {
			$output = '';
		}

		if ( empty( $categories ) && ! $args['hide_if_empty'] && ! empty( $args['show_option_none'] ) ) {
			$args['show_option_none'] = Hooks::list_cats( $args['show_option_none'] );
			$output                  .= "\t<option value='-1' selected='selected'>" . $args['show_option_none'] . "</option>\n";
		}

		if ( ! empty( $categories ) ) {

			if ( $args['show_option_all'] ) {
				$show_option_all = Hooks::list_cats( $args['show_option_all'] );
				$selected        = ( '0' === strval( $args['selected'] ) ) ? " selected='selected'" : '';
				$output          .= "\t<option value='0'$selected>$show_option_all</option>\n";
			}

			if ( $args['show_option_none'] ) {
				$show_option_none = Hooks::list_cats( $args['show_option_none'] );
				$selected         = ( '-1' === strval( $args['selected'] ) ) ? " selected='selected'" : '';
				$output          .= "\t<option value='-1'$selected>$show_option_none</option>\n";
			}

			$depth = -1; // Flat.

			$output .= walk_category_dropdown_tree( $categories, $depth, $args );
		}

		if ( ! $args['hide_if_empty'] || ! empty( $categories ) ) {
			$output .= "</select>\n";
		}

		$output = Hooks::dropdown_cats( $output );
		$output = $this->kses_dropdown( $output );

		if ( $args['echo'] ) {
			echo wp_kses(
				$output,
				array(
					'select' => array(
						'name'     => true,
						'id'       => true,
						'class'    => true,
						'tabindex' => true,
					),
					'option' => array(
						'value'    => true,
						'selected' => true,
					),
				)
			);
		}

		return $output;
	}

	/**
	 * 3.0.1 list_categories().
	 *
	 * @param string|array<string, mixed> $args
	 *
	 * @return string|false|void
	 */
	public function list_categories( $args = '' ) {
		$defaults = array(
			'show_option_all'    => '',
			'show_option_none'   => __( 'No categories', 'wp-publication-archive' ),
			'orderby'            => 'name',
			'order'              => 'ASC',
			'style'              => 'list',
			'show_count'         => 0,
			'hide_empty'         => 1,
			'use_desc_for_title' => 1,
			'child_of'           => 0,
			'feed'               => '',
			'feed_type'          => '',
			'feed_image'         => '',
			'exclude'            => '', // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- reason: 3.0.1 behaviour, get_terms() exclude argument, not a post query.
			'exclude_tree'       => '',
			'current_category'   => 0,
			'hierarchical'       => true,
			'title_li'           => __( 'Categories', 'wp-publication-archive' ),
			'echo'               => 1,
			'depth'              => 0,
			'taxonomy'           => 'category',
			'post_types'         => array( Keys::POST_TYPE ),
		);

		$args = wp_parse_args( $args, $defaults );

		if ( ! isset( $args['pad_counts'] ) && $args['show_count'] && $args['hierarchical'] ) {
			$args['pad_counts'] = true;
		}

		if ( ! isset( $args['class'] ) ) {
			$args['class'] = ( 'category' === $args['taxonomy'] ) ? 'categories' : $args['taxonomy'];
		}

		if ( ! taxonomy_exists( $args['taxonomy'] ) ) {
			return false;
		}

		$categories = $this->get_terms( 'category', $args );

		$output = '';
		if ( $args['title_li'] && 'list' === $args['style'] ) {
			$output = '<li class="' . esc_attr( $args['class'] ) . '">' . $args['title_li'] . '<ul>';
		}

		if ( empty( $categories ) ) {
			if ( ! empty( $args['show_option_none'] ) ) {
				if ( 'list' === $args['style'] ) {
					$output .= '<li>' . $args['show_option_none'] . '</li>';
				} else {
					$output .= $args['show_option_none'];
				}
			}
		} else {
			if ( ! empty( $args['show_option_all'] ) ) {
				$posts_page = ( 'page' === $this->flags->show_on_front() && $this->flags->page_for_posts() ) ? get_permalink( $this->flags->page_for_posts() ) : home_url( '/' );
				$posts_page = esc_url( $posts_page );
				if ( 'list' === $args['style'] ) {
					$output .= "<li><a href='$posts_page'>" . $args['show_option_all'] . '</a></li>';
				} else {
					$output .= "<a href='$posts_page'>" . $args['show_option_all'] . '</a>';
				}
			}

			if ( empty( $args['current_category'] ) && ( is_category() || is_tax() || is_tag() ) ) {
				$current_term_object = get_queried_object();
				if ( $args['taxonomy'] === $current_term_object->taxonomy ) {
					$args['current_category'] = get_queried_object_id();
				}
			}

			$depth = -1; // Flat.

			$this->term_link_scope = true;

			try {
				$output .= walk_category_tree( $categories, $depth, $args );
			} finally {
				$this->term_link_scope = false;
			}
		}

		if ( $args['title_li'] && 'list' === $args['style'] ) {
			$output .= '</ul></li>';
		}

		$output = Hooks::list_categories( $output, $args );

		if ( $args['echo'] ) {
			echo wp_kses_post( $output );
		} else {
			return wp_kses_post( $output );
		}
	}

	/**
	 * 3.0.1 get_terms() wrapper, scoping terms_clauses to publications. Calls
	 * core get_terms() with the modern single-array signature (the legacy
	 * two-argument form is deprecated on the WordPress versions this plugin
	 * supports); 'taxonomy' is merged into $args exactly as 3.0.1 passed it
	 * as the first argument.
	 *
	 * @param string|array<int, string> $taxonomies
	 * @param array<string, mixed>      $args
	 *
	 * @return array<int, mixed>|\WP_Error
	 */
	public function get_terms( $taxonomies, $args = array() ) {
		$args['taxonomy'] = $taxonomies;

		if ( empty( $args['post_types'] ) ) {
			return get_terms( $args );
		}

		$this->scoped_post_types   = (array) $args['post_types'];
		$this->terms_clauses_scope = true;

		try {
			$terms = get_terms( $args );
		} finally {
			$this->terms_clauses_scope = false;
			$this->scoped_post_types   = array();
		}

		return $terms;
	}

	/**
	 * Hooked to terms_clauses by Plugin. Acts only while get_terms() is
	 * running (P3).
	 *
	 * @param array<string, string>    $pieces
	 * @param string|array<int,string> $tax
	 * @param array<string, mixed>     $args
	 *
	 * @return array<string, string>
	 */
	public function filter_terms_by_cpt( $pieces, $tax, $args ) {
		unset( $tax, $args );

		if ( ! $this->terms_clauses_scope ) {
			return $pieces;
		}

		global $wpdb;

		// Don't use db count
		$pieces['fields'] .= ', COUNT(*) ';

		// Join extra tables to restrict by post type.
		$pieces['join'] .= " INNER JOIN $wpdb->term_relationships AS r ON r.term_taxonomy_id = tt.term_taxonomy_id
							 INNER JOIN $wpdb->posts AS p ON p.ID = r.object_id ";

		// Restrict by post type and Group by term_id for COUNTing.
		$post_types_str = implode( ',', $this->scoped_post_types );
		$pieces['where'] .= $wpdb->prepare( ' AND p.post_type IN(%s) GROUP BY t.term_id', $post_types_str );

		return $pieces;
	}

	/**
	 * Hooked to term_link by Plugin. Acts only while list_categories() is
	 * running (P3).
	 *
	 * @param string $termlink
	 * @param mixed  $term
	 * @param mixed  $taxonomy
	 *
	 * @return string
	 */
	public function filter_category_link( $termlink, $term, $taxonomy ) {
		unset( $term, $taxonomy );

		if ( ! $this->term_link_scope ) {
			return $termlink;
		}

		return preg_replace( '/\/category\//', '/' . Keys::REWRITE_BASE . '/' . Keys::REWRITE_CATEGORY . '/', $termlink );
	}
}
