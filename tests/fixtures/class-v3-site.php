<?php
/**
 * Implements SPEC.md §8 Phase 0 item 1: a 3.0.1-shaped fixture every later
 * test uses. Writes meta the way a 3.0.1 site would have, bypassing any
 * registered sanitize callback (those arrive in P2-04); see P16.
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA\Tests\Fixtures;

use WPPA\Keys;

final class V3_Site {

	/**
	 * Writes a post meta row directly through $wpdb, bypassing
	 * update_post_meta() and any registered sanitize_callback.
	 *
	 * @param mixed $value
	 */
	public static function raw_meta( int $post_id, string $key, $value ): void {
		global $wpdb;

		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- reason: test fixture writes 3.0.1-shaped rows directly, bypassing update_post_meta() on purpose (P16).
			$wpdb->postmeta,
			array(
				'post_id'    => $post_id,
				'meta_key'   => $key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- reason: test fixture writes 3.0.1-shaped rows directly, bypassing update_post_meta() on purpose (P16).
				'meta_value' => maybe_serialize( $value ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- reason: see above.
			)
		);

		wp_cache_delete( $post_id, 'post_meta' );
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function create( \WP_UnitTest_Factory $factory ): array {
		$data = array();

		if ( ! term_exists( 'reports', 'category' ) ) {
			wp_insert_category(
				array(
					'cat_name' => 'Reports',
					'category_nicename' => 'reports',
				)
			);
		}
		$category = get_term_by( 'slug', 'reports', 'category' );
		$data['category_id'] = $category ? (int) $category->term_id : 0;

		if ( ! term_exists( 'jane-doe', Keys::TAX_AUTHOR ) ) {
			wp_insert_term( 'Jane Doe', Keys::TAX_AUTHOR, array( 'slug' => 'jane-doe' ) );
		}
		$author_term = get_term_by( 'slug', 'jane-doe', Keys::TAX_AUTHOR );
		$data['author_term_id'] = $author_term ? (int) $author_term->term_id : 0;

		$pdf_path = DIR_TESTDATA . '/images/wordpress-gsoc-flyer.pdf';
		if ( ! file_exists( $pdf_path ) ) {
			$pdf_path = get_temp_dir() . 'wppa-fixture.pdf';
			// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_file_put_contents -- reason: sole writer of a throwaway test fixture file when WP core test data is unavailable.
			file_put_contents( $pdf_path, "%PDF-1.4\n%%EOF" );
		}

		$attachment_id = $factory->attachment->create_upload_object( $pdf_path );
		$data['attachment_id']  = $attachment_id;
		$data['attachment_url'] = wp_get_attachment_url( $attachment_id );

		$pipe_attachment_id = $factory->attachment->create_upload_object( $pdf_path );
		$data['pipe_attachment_id'] = $pipe_attachment_id;
		$pipe_url                   = (string) wp_get_attachment_url( $pipe_attachment_id );
		$pipe_no_scheme              = (string) preg_replace( '#^[a-z]+://#', '', $pipe_url );

		$image_path = DIR_TESTDATA . '/images/canola.jpg';
		$image_attachment_id = $factory->attachment->create_upload_object( $image_path );
		$data['image_attachment_id'] = $image_attachment_id;
		$image_url                   = wp_get_attachment_url( $image_attachment_id );

		$data['attached'] = $factory->post->create(
			array(
				'post_type'    => Keys::POST_TYPE,
				'post_title'   => 'Attached Report',
				'post_content' => 'Attached report summary.',
				'post_status'  => 'publish',
				'post_date'    => '2013-01-07 12:00:00',
				'post_date_gmt' => '2013-01-07 12:00:00',
			)
		);
		self::raw_meta( $data['attached'], Keys::META_DOC, $data['attachment_url'] );

		$data['pipe'] = $factory->post->create(
			array(
				'post_type'    => Keys::POST_TYPE,
				'post_title'   => 'Pipe Report',
				'post_content' => 'Pipe report summary.',
				'post_status'  => 'publish',
				'post_date'    => '2013-01-06 00:00:00',
				'post_date_gmt' => '2013-01-06 00:00:00',
			)
		);
		self::raw_meta( $data['pipe'], Keys::META_DOC, 'https|' . $pipe_no_scheme );

		$data['alternates'] = $factory->post->create(
			array(
				'post_type'    => Keys::POST_TYPE,
				'post_title'   => 'Alternates Report',
				'post_content' => 'Alternates report summary.',
				'post_status'  => 'publish',
				'post_date'    => '2013-01-05 00:00:00',
				'post_date_gmt' => '2013-01-05 00:00:00',
			)
		);
		self::raw_meta( $data['alternates'], Keys::META_DOC, $data['attachment_url'] );
		self::raw_meta(
			$data['alternates'],
			Keys::META_ALTERNATES,
			array( 'description' => 'English', 'url' => $data['attachment_url'] )
		);
		self::raw_meta(
			$data['alternates'],
			Keys::META_ALTERNATES,
			array( 'description' => '<script>alert(1)</script>', 'url' => $data['attachment_url'] )
		);

		$data['thumbnail'] = $factory->post->create(
			array(
				'post_type'    => Keys::POST_TYPE,
				'post_title'   => 'Thumbnail Report',
				'post_content' => 'Thumbnail report summary.',
				'post_status'  => 'publish',
				'post_date'    => '2013-01-04 00:00:00',
				'post_date_gmt' => '2013-01-04 00:00:00',
			)
		);
		self::raw_meta( $data['thumbnail'], Keys::META_DOC, $data['attachment_url'] );
		self::raw_meta( $data['thumbnail'], Keys::META_IMAGE, $image_url );

		$data['categorised'] = $factory->post->create(
			array(
				'post_type'    => Keys::POST_TYPE,
				'post_title'   => 'Categorised Report',
				'post_content' => 'Categorised report summary.',
				'post_status'  => 'publish',
				'post_date'    => '2013-01-03 00:00:00',
				'post_date_gmt' => '2013-01-03 00:00:00',
			)
		);
		self::raw_meta( $data['categorised'], Keys::META_DOC, $data['attachment_url'] );
		wp_set_post_terms( $data['categorised'], array( $data['category_id'] ), 'category' );
		wp_set_post_terms( $data['categorised'], array( $data['author_term_id'] ), Keys::TAX_AUTHOR );

		$data['slug_view'] = $factory->post->create(
			array(
				'post_type'    => Keys::POST_TYPE,
				'post_title'   => 'View',
				'post_name'    => 'view',
				'post_content' => 'View slug report.',
				'post_status'  => 'publish',
				'post_date'    => '2013-01-02 00:00:00',
				'post_date_gmt' => '2013-01-02 00:00:00',
			)
		);
		self::raw_meta( $data['slug_view'], Keys::META_DOC, $data['attachment_url'] );

		$data['slug_download'] = $factory->post->create(
			array(
				'post_type'    => Keys::POST_TYPE,
				'post_title'   => 'Download',
				'post_name'    => 'download',
				'post_content' => 'Download slug report.',
				'post_status'  => 'publish',
				'post_date'    => '2013-01-01 00:00:00',
				'post_date_gmt' => '2013-01-01 00:00:00',
			)
		);
		self::raw_meta( $data['slug_download'], Keys::META_DOC, $data['attachment_url'] );

		return $data;
	}

	/**
	 * Used by characterisation tests in set_up(). A no-op unless
	 * Plugin::instance() exposes a rewrites() accessor whose object has a
	 * disarm() method (P0-08, P2-01).
	 */
	public static function reset_link_state(): void {
		if ( ! class_exists( \WPPA\Plugin::class ) || ! method_exists( \WPPA\Plugin::class, 'instance' ) ) {
			return;
		}

		try {
			$plugin = \WPPA\Plugin::instance();
		} catch ( \Throwable $e ) {
			return;
		}

		if ( ! method_exists( $plugin, 'rewrites' ) ) {
			return;
		}

		$rewrites = $plugin->rewrites();

		if ( is_object( $rewrites ) && method_exists( $rewrites, 'disarm' ) ) {
			$rewrites->disarm();
		}
	}
}
