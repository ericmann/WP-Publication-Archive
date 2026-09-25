<?php
/**
 * Implements SPEC.md §8 Phase 0 item 4 and §6.1: the 3.0.1 `publication`
 * post type and `publication-author` taxonomy, registered verbatim except
 * the menu icon (D16 part: the 3.0.1 PNG icon asset is deleted, so
 * Keys::MENU_ICON is used) and D12 (P2-03): both are now exposed to REST
 * and the block editor, with publication capabilities. P2-04 registers the
 * §5.1 meta keys for REST, edit-context only, with Url_Policy sanitising on
 * write (D12 meta, D1 via REST).
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
		$this->register_meta();
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
				'capability_type'      => Keys::CAPABILITY_TYPE,
				'map_meta_cap'         => true,
				'public'               => true,
				'publicly_queryable'   => true,
				'has_archive'          => true,
				'menu_position'        => 20,
				'supports'             => array(
					'title',
					'editor',
					'custom-fields',
				),
				'taxonomies'           => array(
					'category',
					'post_tag',
				),
				'can_export'           => true,
				'menu_icon'            => Keys::MENU_ICON,
				'show_in_rest'         => true,
				'rest_base'            => Keys::REST_BASE,
			)
		);
	}

	/**
	 * SPEC.md §6.1: the three §5.1 meta keys, exposed to REST in the edit
	 * context only (D12 meta), with Url_Policy sanitising on write (D1 via
	 * REST).
	 */
	private function register_meta(): void {
		$url_args = array(
			'single'            => true,
			'type'              => 'string',
			'show_in_rest'      => array(
				'schema' => array(
					'type'    => 'string',
					'format'  => 'uri',
					'context' => array( 'edit' ),
				),
			),
			'auth_callback'     => array( $this, 'can_edit' ),
			'sanitize_callback' => array( $this, 'sanitize_url_meta' ),
		);

		register_post_meta( Keys::POST_TYPE, Keys::META_DOC, $url_args );
		register_post_meta( Keys::POST_TYPE, Keys::META_IMAGE, $url_args );

		register_post_meta(
			Keys::POST_TYPE,
			Keys::META_ALTERNATES,
			array(
				'single'            => false,
				'type'              => 'object',
				'show_in_rest'      => array(
					'schema' => array(
						'type'       => 'object',
						'properties' => array(
							'description' => array( 'type' => 'string' ),
							'url'         => array( 'type' => 'string' ),
						),
						'context'    => array( 'edit' ),
					),
				),
				'auth_callback'     => array( $this, 'can_edit' ),
				'sanitize_callback' => array( $this, 'sanitize_alternate_meta' ),
			)
		);
	}

	public function can_edit( bool $allowed, string $meta_key, int $object_id ): bool {
		unset( $allowed, $meta_key );

		return current_user_can( 'edit_post', $object_id );
	}

	public function sanitize_url_meta( string $meta_value ): string {
		$validated = $this->policy->validate( $meta_value );

		return $validated instanceof \WP_Error ? '' : $validated;
	}

	/**
	 * @param mixed $meta_value
	 * @return array{description: string, url: string}
	 */
	public function sanitize_alternate_meta( $meta_value ): array {
		$description = is_array( $meta_value ) && isset( $meta_value['description'] )
			? sanitize_text_field( (string) $meta_value['description'] )
			: '';

		$url = is_array( $meta_value ) && isset( $meta_value['url'] )
			? (string) $meta_value['url']
			: '';

		$validated = $this->policy->validate( $url );

		return array(
			'description' => $description,
			'url'         => $validated instanceof \WP_Error ? '' : $validated,
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
				'public'       => true,
				'query_var'    => Keys::TAX_AUTHOR_QUERY_VAR,
				'rewrite'      => array( 'slug' => Keys::TAX_AUTHOR_REWRITE_SLUG ),
				'show_in_rest' => true,
			)
		);
	}
}
