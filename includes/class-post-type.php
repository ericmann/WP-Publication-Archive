<?php
/**
 * Implements SPEC.md §8 Phase 0 item 4: the 3.0.1 `publication` post type and
 * `publication-author` taxonomy, registered verbatim except the menu icon
 * (D16 part: the 3.0.1 PNG icon asset is deleted, so Keys::MENU_ICON is used).
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA;

final class Post_Type {

	private Url_Policy $policy;

	public function __construct( Url_Policy $policy ) {
		$this->policy = $policy;
	}

	public function policy(): Url_Policy {
		return $this->policy;
	}

	/**
	 * Hooked to init.
	 */
	public function register(): void {
		$this->register_author();
		$this->register_publication();
	}

	/**
	 * 3.0.1 query_publications(). Utility function returning a WP_Query of
	 * Publication posts.
	 *
	 * @param array<string, mixed> $args
	 */
	public function query( array $args ): \WP_Query {
		$defaults = array(
			'posts_per_page' => -1, // phpcs:ignore WordPressVIPMinimum.Performance.NoPaging.posts_per_page_posts_per_page -- reason: 3.0.1 behaviour, query_publications() default is unpaginated.
			'order'          => 'ASC',
			'orderby'        => 'menu_order',
		);

		$query_args              = wp_parse_args( $args, $defaults );
		$query_args['post_type'] = Keys::POST_TYPE;

		return new \WP_Query( $query_args );
	}

	private function register_publication(): void {
		$labels = array(
			'name'               => __( 'Publications', 'wp-publication-archive' ),
			'singular_name'      => __( 'Publication', 'wp-publication-archive' ),
			'add_new_item'       => __( 'Add New Publication', 'wp-publication-archive' ),
			'edit_item'          => __( 'Edit Publication', 'wp-publication-archive' ),
			'new_item'           => __( 'New Publication', 'wp-publication-archive' ),
			'view_item'          => __( 'View Publication', 'wp-publication-archive' ),
			'search_items'       => __( 'Search Publications', 'wp-publication-archive' ),
			'not_found'          => __( 'No publications found', 'wp-publication-archive' ),
			'not_found_in_trash' => __( 'No publications found in trash', 'wp-publication-archive' ),
		);

		register_post_type(
			Keys::POST_TYPE,
			array(
				'labels'               => $labels,
				'capability_type'      => 'post',
				'public'               => true,
				'publicly_queryable'   => true,
				'has_archive'          => true,
				'menu_position'        => 20,
				'supports'             => array(
					'title',
					'editor',
				),
				'taxonomies'           => array(
					'category',
					'post_tag',
				),
				'can_export'           => true,
				'menu_icon'            => Keys::MENU_ICON,
			)
		);
	}

	private function register_author(): void {
		$labels = array(
			'name'          => __( 'Authors', 'wp-publication-archive' ),
			'singular_name' => __( 'Author', 'wp-publication-archive' ),
			'search_items'  => __( 'Search Authors', 'wp-publication-archive' ),
			'popular_items' => __( 'Popular Authors', 'wp-publication-archive' ),
			'all_items'     => __( 'All Authors', 'wp-publication-archive' ),
			'edit_item'     => __( 'Edit Author', 'wp-publication-archive' ),
			'update_item'   => __( 'Update Author', 'wp-publication-archive' ),
			'add_new_item'  => __( 'Add New Author', 'wp-publication-archive' ),
			'new_item_name' => __( 'New Author Name', 'wp-publication-archive' ),
			'menu_name'     => __( 'Authors', 'wp-publication-archive' ),
		);

		register_taxonomy(
			Keys::TAX_AUTHOR,
			array( Keys::POST_TYPE ),
			array(
				'hierarchical' => false,
				'labels'       => $labels,
				'label'        => __( 'Authors', 'wp-publication-archive' ),
				'query_var'    => false,
				'rewrite'      => false,
			)
		);
	}
}
