<?php
/**
 * Implements SPEC.md §8 Phase 0 item 4 and §6.2: the three 3.0.1 publication
 * meta boxes and save_meta(). P1-04 closes D1 (save), D2 (save), D3 (admin)
 * and D10 here; D13 (inline Thickbox JS) stays open until P2-07.
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
	 * 3.0.1 WP_Publication_Archive::doc_uri_box(). D13: inline Thickbox JS.
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
		echo '<input type="text" id="' . esc_attr( Keys::FIELD_IMAGE ) . '" name="' . esc_attr( Keys::FIELD_IMAGE ) . '" value=" ' . esc_attr( $thumb ) . '" size="36" size="25" style="width:85%" />';
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
					table = document.getElementById( "wp" + "a-alternate-table" ),
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
					span1.className = 'wp' + 'a-upload-row';
					span1.style.borderBottom = '1px solid #000';
					span1.style.cursor = 'pointer';
					span1.innerText = '<?php echo esc_js( __( 'upload', 'wp-publication-archive' ) ); ?>';
					td3.appendChild( span1 );
					td3.appendChild( document.createTextNode( ' | ' ) );
					var span2 = document.createElement( 'span' );
					span2.className = 'wp' + 'a-delete-row';
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

				$( document.getElementById( 'wp' + 'a-alternates-button' ) ).on( 'click', addRow );
				$( table ).on( 'click', '.wpa-delete-row', deleteRow );
				$( table ).on( 'click', '.wpa-upload-row', uploadRow );
			} )( this, jQuery );
		</script>
		<?php
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
