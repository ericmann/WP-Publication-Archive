<?php
/**
 * Implements SPEC.md §8 Phase 0 item 4: the three 3.0.1 publication meta
 * boxes and save_meta(), with the open D1, D2, D3, D10 and D13 defects
 * preserved verbatim (they close in Phase 1/2).
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA;

final class Meta_Boxes {

	/**
	 * Hooked to add_meta_boxes_publication.
	 */
	public function add(): void {
		add_meta_box( Keys::META_BOX_DOC, esc_html__( 'Publication', 'wp-publication-archive' ), array( $this, 'render_doc' ), Keys::POST_TYPE, 'normal', 'high', array() );
		add_meta_box( Keys::META_BOX_ALTERNATES, esc_html__( 'Alternate Files', 'wp-publication-archive' ), array( $this, 'render_alternates' ), Keys::POST_TYPE, 'normal', 'high', array() );
		add_meta_box( Keys::META_BOX_THUMB, esc_html__( 'Thumbnail', 'wp-publication-archive' ), array( $this, 'render_thumb' ), Keys::POST_TYPE, 'normal', 'high', array() );
	}

	/**
	 * 3.0.1 WP_Publication_Archive::doc_uri_box(). D13: inline Thickbox JS.
	 */
	public function render_doc( \WP_Post $post ): void {
		wp_nonce_field( Keys::NONCE_ACTION, Keys::FIELD_NONCE );

		$uri = get_post_meta( $post->ID, Keys::META_DOC, true );

		echo '<p>' . wp_kses_post( __( 'Please provide the absolute url of the file (including the <code>http://</code>):', 'wp-publication-archive' ) ) . '</p>';
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- reason: D3 (SPEC §1.1), meta value echoed into value="" unescaped.
		echo '<input type="text" id="' . esc_attr( Keys::FIELD_DOC ) . '" name="' . esc_attr( Keys::FIELD_DOC ) . '" value="' . $uri . '" size="25" style="width:85%" />';
		echo '<input class="button" id="upload_doc_button" type="button" value="' . esc_attr__( 'Upload Publication', 'wp-publication-archive' ) . '" alt="' . esc_attr__( 'Upload Publication', 'wp-publication-archive' ) . '" />';
		?>
		<script type="text/javascript">
			( function ( window, $, undefined ) {
				var handle_doc_upload = function () {
					var document = window.document;

					window.orig_send_to_editor = window.send_to_editor;
					window.send_to_editor = function ( html ) {
						document.getElementById( '<?php echo esc_js( Keys::FIELD_DOC ); ?>' ).value = $( html ).attr( 'href' );

						window.tb_remove();

						// Restore original handler
						window.send_to_editor = window.orig_send_to_editor;
					};

					window.tb_show( '<?php echo esc_js( __( 'Upload Publication', 'wp-publication-archive' ) ); ?>', 'media-upload.php?TB_iframe=1&width=640&height=263' );
					return false;
				};

				$( '#upload_doc_button' ).on( 'click', handle_doc_upload );
			} )( this, jQuery );
		</script>
		<?php
	}

	/**
	 * 3.0.1 WP_Publication_Archive::doc_thumb_box(). D13: inline Thickbox
	 * JS. The leading space before the meta value in value="" is a 3.0.1
	 * quirk, kept verbatim.
	 */
	public function render_thumb( \WP_Post $post ): void {
		$thumb = get_post_meta( $post->ID, Keys::META_IMAGE, true );

		echo '<p>' . wp_kses_post( __( 'Please provide the absolute url for a thumbnail image (including the <code>http://</code>):', 'wp-publication-archive' ) ) . '</p>';
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- reason: D3 (SPEC §1.1), meta value echoed into value="" unescaped.
		echo '<input type="text" id="' . esc_attr( Keys::FIELD_IMAGE ) . '" name="' . esc_attr( Keys::FIELD_IMAGE ) . '" value=" ' . $thumb . '" size="36" size="25" style="width:85%" />';
		echo '<input class="button" id="wpa-upload_image_button" type="button" value="' . esc_attr__( 'Upload Thumbnail', 'wp-publication-archive' ) . '" alt="' . esc_attr__( 'Upload Thumbnail', 'wp-publication-archive' ) . '" />';
		?>
		<script type="text/javascript">
			( function( window, $, undefined ) {
				var handle_thumb_upload = function() {
					var document = window.document;

					window.orig_send_to_editor = window.send_to_editor;
					window.send_to_editor = function( html ) {
						document.getElementById( '<?php echo esc_js( Keys::FIELD_IMAGE ); ?>' ).value = $( html ).attr( 'href' );

						window.tb_remove();

						// Restore original handler
						window.send_to_editor = window.orig_send_to_editor;
					};

					window.tb_show( '<?php echo esc_js( __( 'Upload Thumbnail', 'wp-publication-archive' ) ); ?>', 'media-upload.php?TB_iframe=1&width=640&height=263' );
					return false;
				}

				$( '#wpa-upload_image_button' ).on( 'click', handle_thumb_upload );
			} )( this, jQuery );
		</script>
		<?php
	}

	/**
	 * 3.0.1 WP_Publication_Archive::doc_alternates_box(). D13: inline
	 * Thickbox JS.
	 */
	public function render_alternates( \WP_Post $post ): void {
		$alternates = get_post_meta( $post->ID, Keys::META_ALTERNATES );

		echo '<p>' . esc_html__( 'These files are considered alternates to the publication listed above (i.e. foreign language translations of the same document).', 'wp-publication-archive' ) . '</p>';
		echo '<table id="wpa-alternate-table" style="width:100%;">';
		echo '<thead><tr style="text-align:left;"><th>' . esc_html__( 'Description', 'wp-publication-archive' ) . '</th><th>' . esc_html__( 'Absolute Url', 'wp-publication-archive' ) . '</th><th></th></tr></thead>';
		echo '<tbody>';
		foreach ( $alternates as $alternate ) {
			echo '<tr>';
			echo '<td style="width:30%;"><input style="width:100%;" type="text" name="' . esc_attr( Keys::FIELD_ALTERNATES ) . '[description][]" value="' . esc_attr( $alternate['description'] ) . '" /></td>';
			echo '<td style="width:60%;"><input style="width:100%;" type="text" name="' . esc_attr( Keys::FIELD_ALTERNATES ) . '[url][]" value="' . esc_attr( $alternate['url'] ) . '" /></td>';
			echo '<td style="text-align:center;width:10%;"><span class="wpa-upload-row" style="cursor:pointer;border-bottom:1px solid #000;">' . esc_html__( 'upload', 'wp-publication-archive' ) . '</span> | <span class="wpa-delete-row" style="cursor:pointer;color:#f00;border-bottom:1px solid #f00;">' . esc_html__( 'delete', 'wp-publication-archive' ) . '</span></td>';
			echo '</tr>';
		}

		echo '<tr>';
		echo '<td style="width:30%;"><input style="width:100%;" type="text" name="' . esc_attr( Keys::FIELD_ALTERNATES ) . '[description][]" value="" /></td>';
		echo '<td style="width:60%;"><input style="width:100%;" type="text" name="' . esc_attr( Keys::FIELD_ALTERNATES ) . '[url][]" value="" /></td>';
		echo '<td style="text-align:center;width:10%;"><span class="wpa-upload-row" style="cursor:pointer;border-bottom:1px solid #000;">' . esc_html__( 'upload', 'wp-publication-archive' ) . '</span> | <span class="wpa-delete-row" style="cursor:pointer;color:#f00;border-bottom:1px solid #f00;">' . esc_html__( 'delete', 'wp-publication-archive' ) . '</span></td>';
		echo '</tr>';
		echo '</tbody>';
		echo '</table>';

		echo '<input class="button" id="wpa-alternates-button" type="button" value="' . esc_attr__( 'Add Row', 'wp-publication-archive' ) . '" alt="' . esc_attr__( 'Add Row', 'wp-publication-archive' ) . '" />';
		?>
		<script type="text/javascript">
			( function ( window, $, undefined ) {
				var document = window.document,
					editor_store,
					table = document.getElementById( "wpa-alternate-table" ),
					row = document.createElement( 'tr' );

				{
					var td1 = document.createElement( 'td' );
					td1.style.width = '30%';
					row.appendChild( td1 );
					var input1 = document.createElement( 'input' );
					input1.style.width = '100%';
					input1.setAttribute( 'type', 'text' );
					input1.setAttribute( 'name', '<?php echo esc_js( Keys::FIELD_ALTERNATES ); ?>[description][]' );
					td1.appendChild( input1 );

					var td2 = document.createElement( 'td' );
					td2.style.width = '60%';
					row.appendChild( td2 );
					var input2 = document.createElement( 'input' );
					input2.style.width = '100%';
					input2.setAttribute( 'type', 'text' );
					input2.setAttribute( 'name', '<?php echo esc_js( Keys::FIELD_ALTERNATES ); ?>[url][]' );
					td2.appendChild( input2 );

					var td3 = document.createElement( 'td' );
					td3.style.width = '10%';
					td3.style.textAlign = 'center';
					row.appendChild( td3 );
					var span1 = document.createElement( 'span' );
					span1.className = 'wpa-upload-row';
					span1.style.borderBottom = '1px solid #000';
					span1.style.cursor = 'pointer';
					span1.innerText = '<?php echo esc_js( __( 'upload', 'wp-publication-archive' ) ); ?>';
					td3.appendChild( span1 );
					td3.appendChild( document.createTextNode( ' | ' ) );
					var span2 = document.createElement( 'span' );
					span2.className = 'wpa-delete-row';
					span2.style.color = '#f00';
					span2.style.borderBottom = '1px solid #f00';
					span2.style.cursor = 'pointer';
					span2.innerText = '<?php echo esc_js( __( 'delete', 'wp-publication-archive' ) ); ?>';
					td3.appendChild( span2 );
				}

				var addRow = function( e ) {
					e.preventDefault();

					table.appendChild( row.cloneNode( true ) );
				};

				var deleteRow = function( e ) {
					e.preventDefault();

					$( this ).parents( 'tr' ).remove();
				};

				var uploadRow = function( e ) {
					e.preventDefault();

					var $this = $( this ),
						target = $this.parents( 'tr' ).find( 'input[name="<?php echo esc_js( Keys::FIELD_ALTERNATES ); ?>[url][]"]' );

					var send_handler = function( html ) {
						target.val( $( html ).attr( 'href' ) );

						window.tb_remove();

						window.send_to_editor = editor_store;
					};

					editor_store = window.send_to_editor;
					window.send_to_editor = send_handler;
					window.tb_show( '<?php echo esc_js( __( 'Upload Alternate', 'wp-publication-archive' ) ); ?>', 'media-upload.php?TB_iframe=1&width=640&height=263' );
					return false;
				};

				$( document.getElementById( 'wpa-alternates-button' ) ).on( 'click', addRow );
				$( table ).on( 'click', '.wpa-delete-row', deleteRow );
				$( table ).on( 'click', '.wpa-upload-row', uploadRow );
			} )( this, jQuery );
		</script>
		<?php
	}

	/**
	 * 3.0.1 WP_Publication_Archive::save_meta(). D1 (esc_url_raw accepts
	 * values beginning with '/'), D2 (alternate descriptions unsanitized)
	 * and D10 (the <= loop reads one index past the end) are preserved.
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

		if ( ! isset( $_POST[ Keys::FIELD_NONCE ] ) || ! wp_verify_nonce( wp_unslash( $_POST[ Keys::FIELD_NONCE ] ), Keys::NONCE_ACTION ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- reason: wp_verify_nonce() validates the value.
			return $post_id;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- reason: D1 (SPEC §1.1), esc_url_raw() accepts values beginning with '/'.
		$uri = isset( $_POST[ Keys::FIELD_DOC ] ) && '' !== trim( $_POST[ Keys::FIELD_DOC ] ) ? esc_url_raw( wp_unslash( $_POST[ Keys::FIELD_DOC ] ) ) : '';
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- reason: D1 (SPEC §1.1), esc_url_raw() accepts values beginning with '/'.
		$thumbnail = isset( $_POST[ Keys::FIELD_IMAGE ] ) && '' !== trim( $_POST[ Keys::FIELD_IMAGE ] ) ? esc_url_raw( wp_unslash( $_POST[ Keys::FIELD_IMAGE ] ) ) : '';

		update_post_meta( $post_id, Keys::META_DOC, $uri );
		update_post_meta( $post_id, Keys::META_IMAGE, $thumbnail );

		// Handle alternate uploads.
		delete_post_meta( $post_id, Keys::META_ALTERNATES );

		if ( isset( $_POST[ Keys::FIELD_ALTERNATES ] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- reason: D2 (SPEC §1.1), alternate descriptions are stored unsanitized; nonce already verified above.
			$posted = wp_unslash( $_POST[ Keys::FIELD_ALTERNATES ] );

			// D10 (SPEC §1.1): the <= loop is 3.0.1 behaviour, reading one index past the end.
			for ( $i = 0; $i <= count( $posted['url'] ); $i++ ) {
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- reason: D2 (SPEC §1.1), alternate descriptions are stored unsanitized.
				$description = $posted['description'][ $i ];
				$url         = $posted['url'][ $i ];

				if ( '' === trim( $url ) ) {
					continue;
				}

				add_post_meta( $post_id, Keys::META_ALTERNATES, array( 'description' => $description, 'url' => $url ) );
			}
		}

		return $post_id;
	}
}
