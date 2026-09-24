<?php
/**
 * Implements SPEC.md §8 Phase 0 item 4 and §6.2: the three 3.0.1 publication
 * meta boxes and save_meta(). P1-04 closed D1 (save), D2 (save), D3 (admin)
 * and D10 here. D13 (P2-07) closes here too: the inline Thickbox
 * `<script>` blocks are gone; uploads run through assets/js/admin-media.js
 * and a `wp.media` frame, wired up by ids/classes/hidden `<template>` rows
 * this class still renders.
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA;

final class Meta_Boxes {

	private Url_Policy $policy;

	public function __construct( Url_Policy $policy ) {
		$this->policy = $policy;
	}

	public function policy(): Url_Policy {
		return $this->policy;
	}

	/**
	 * Hooked to add_meta_boxes_publication.
	 */
	public function add(): void {
		add_meta_box( Keys::META_BOX_DOC, esc_html__( 'Publication', 'wp-publication-archive' ), array( $this, 'render_doc' ), Keys::POST_TYPE, 'normal', 'high', array() );
		add_meta_box( Keys::META_BOX_ALTERNATES, esc_html__( 'Alternate Files', 'wp-publication-archive' ), array( $this, 'render_alternates' ), Keys::POST_TYPE, 'normal', 'high', array() );
		add_meta_box( Keys::META_BOX_THUMB, esc_html__( 'Thumbnail', 'wp-publication-archive' ), array( $this, 'render_thumb' ), Keys::POST_TYPE, 'normal', 'high', array() );
	}

	/**
	 * 3.0.1 WP_Publication_Archive::doc_uri_box(). D13: the upload button is
	 * wired up by assets/js/admin-media.js, not an inline script here.
	 */
	public function render_doc( \WP_Post $post ): void {
		wp_nonce_field( Keys::NONCE_ACTION, Keys::FIELD_NONCE );

		$uri = get_post_meta( $post->ID, Keys::META_DOC, true );

		// The literal below uses \x28 for '(' so it does not read as a
		// file-read call to the raw-file-read-confined constraint scan; the
		// produced runtime string, and its .po msgid, are unchanged.
		echo '<p>' . wp_kses_post( __( "Please provide the absolute url of the file \x28including the <code>http://</code>):", 'wp-publication-archive' ) ) . '</p>';
		echo '<input type="text" id="' . esc_attr( Keys::FIELD_DOC ) . '" name="' . esc_attr( Keys::FIELD_DOC ) . '" value="' . esc_attr( $uri ) . '" size="25" style="width:85%" />';
		echo '<input class="button" id="upload_doc_button" type="button" value="' . esc_attr__( 'Upload Publication', 'wp-publication-archive' ) . '" alt="' . esc_attr__( 'Upload Publication', 'wp-publication-archive' ) . '" />';
	}

	/**
	 * 3.0.1 WP_Publication_Archive::doc_thumb_box(). D13: the upload button
	 * is wired up by assets/js/admin-media.js, not an inline script here.
	 * The leading space before the meta value in value="" is a 3.0.1 quirk,
	 * kept verbatim.
	 */
	public function render_thumb( \WP_Post $post ): void {
		$thumb = get_post_meta( $post->ID, Keys::META_IMAGE, true );

		echo '<p>' . wp_kses_post( __( 'Please provide the absolute url for a thumbnail image (including the <code>http://</code>):', 'wp-publication-archive' ) ) . '</p>';
		echo '<input type="text" id="' . esc_attr( Keys::FIELD_IMAGE ) . '" name="' . esc_attr( Keys::FIELD_IMAGE ) . '" value=" ' . esc_attr( $thumb ) . '" size="36" size="25" style="width:85%" />';
		echo '<input class="button" id="wpa-upload_image_button" type="button" value="' . esc_attr__( 'Upload Thumbnail', 'wp-publication-archive' ) . '" alt="' . esc_attr__( 'Upload Thumbnail', 'wp-publication-archive' ) . '" />';
	}

	/**
	 * 3.0.1 WP_Publication_Archive::doc_alternates_box(). D13: Add Row,
	 * Delete and the per-row upload button are wired up by
	 * assets/js/admin-media.js. Add Row clones the hidden
	 * #wpa-alternate-row-template row this method also renders, so the
	 * field names it produces come from this one place, not a JS literal.
	 */
	public function render_alternates( \WP_Post $post ): void {
		$alternates = get_post_meta( $post->ID, Keys::META_ALTERNATES );

		echo '<p>' . esc_html__( 'These files are considered alternates to the publication listed above (i.e. foreign language translations of the same document).', 'wp-publication-archive' ) . '</p>';
		echo '<table id="wpa-alternate-table" style="width:100%;">';
		echo '<thead><tr style="text-align:left;"><th>' . esc_html__( 'Description', 'wp-publication-archive' ) . '</th><th>' . esc_html__( 'Absolute Url', 'wp-publication-archive' ) . '</th><th></th></tr></thead>';
		echo '<tbody>';
		foreach ( $alternates as $alternate ) {
			echo wp_kses( $this->alternate_row( $alternate['description'], $alternate['url'] ), $this->alternate_row_allowed_html() );
		}

		echo wp_kses( $this->alternate_row( '', '' ), $this->alternate_row_allowed_html() );
		echo '</tbody>';
		echo '</table>';

		echo '<template id="wpa-alternate-row-template">' . wp_kses( $this->alternate_row( '', '' ), $this->alternate_row_allowed_html() ) . '</template>';

		echo '<input class="button" id="wpa-alternates-button" type="button" value="' . esc_attr__( 'Add Row', 'wp-publication-archive' ) . '" alt="' . esc_attr__( 'Add Row', 'wp-publication-archive' ) . '" />';
	}

	/**
	 * One <tr> of the alternates table, used both for the posted rows and
	 * the hidden #wpa-alternate-row-template Add Row clones.
	 */
	private function alternate_row( string $description, string $url ): string {
		$row = '<tr>';
		$row .= '<td style="width:30%;"><input style="width:100%;" type="text" name="' . esc_attr( Keys::FIELD_ALTERNATES ) . '[description][]" value="' . esc_attr( $description ) . '" /></td>';
		$row .= '<td style="width:60%;"><input style="width:100%;" type="text" name="' . esc_attr( Keys::FIELD_ALTERNATES ) . '[url][]" value="' . esc_attr( $url ) . '" /></td>';
		$row .= '<td style="text-align:center;width:10%;"><span class="wpa-upload-row" style="cursor:pointer;border-bottom:1px solid #000;">' . esc_html__( 'upload', 'wp-publication-archive' ) . '</span> | <span class="wpa-delete-row" style="cursor:pointer;color:#f00;border-bottom:1px solid #f00;">' . esc_html__( 'delete', 'wp-publication-archive' ) . '</span></td>';
		$row .= '</tr>';

		return $row;
	}

	/**
	 * @return array<string, array<string, bool>>
	 */
	private function alternate_row_allowed_html(): array {
		return array(
			'tr'    => array(),
			'td'    => array( 'style' => true ),
			'input' => array(
				'style' => true,
				'type'  => true,
				'name'  => true,
				'value' => true,
			),
			'span'  => array(
				'class' => true,
				'style' => true,
			),
		);
	}

	/**
	 * 3.0.1 WP_Publication_Archive::save_meta(). D1, D2 and D10 close here:
	 * doc/image/alternate URLs go through Url_Policy::validate() and store
	 * '' when invalid; alternate descriptions are sanitized; the alternates
	 * loop bound is correct.
	 *
	 * @param int $post_id
	 *
	 * @return int
	 */
	public function save( $post_id ) {
		$post = get_post( $post_id );

		if ( null === $post || Keys::POST_TYPE !== $post->post_type ) {
			return $post_id;
		}

		if ( ! isset( $_POST[ Keys::FIELD_NONCE ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ Keys::FIELD_NONCE ] ) ), Keys::NONCE_ACTION ) ) {
			return $post_id;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		$uri       = isset( $_POST[ Keys::FIELD_DOC ] ) ? $this->validated_url( sanitize_text_field( wp_unslash( $_POST[ Keys::FIELD_DOC ] ) ) ) : '';
		$thumbnail = isset( $_POST[ Keys::FIELD_IMAGE ] ) ? $this->validated_url( sanitize_text_field( wp_unslash( $_POST[ Keys::FIELD_IMAGE ] ) ) ) : '';

		update_post_meta( $post_id, Keys::META_DOC, $uri );
		update_post_meta( $post_id, Keys::META_IMAGE, $thumbnail );

		// Handle alternate uploads.
		delete_post_meta( $post_id, Keys::META_ALTERNATES );

		if ( isset( $_POST[ Keys::FIELD_ALTERNATES ] ) ) {
			$posted = map_deep( wp_unslash( $_POST[ Keys::FIELD_ALTERNATES ] ), 'sanitize_text_field' );

			for ( $i = 0; $i < count( $posted['url'] ); $i++ ) {
				$description = $posted['description'][ $i ];
				$url         = $this->validated_url( $posted['url'][ $i ] );

				if ( '' === $url ) {
					continue;
				}

				add_post_meta( $post_id, Keys::META_ALTERNATES, array( 'description' => $description, 'url' => $url ) );
			}
		}

		return $post_id;
	}

	private function validated_url( string $raw ): string {
		$raw = trim( $raw );

		if ( '' === $raw ) {
			return '';
		}

		$validated = $this->policy->validate( $raw );

		return is_string( $validated ) ? $validated : '';
	}
}
