<?php
/**
 * Core functionality for the WP Publication Archive plugin.
 *
 * All functions are static members of this class to allow for easy namespacing.
 *
 * @module WP_Publication_Archive
 * @author Eric Mann
 */

/**
 * This class contains all of the functionality for the WP Publication Archive Plugin.
 *
 * All methods are static, so this class should not be instantiated.
 */
class WP_Publication_Archive {

	/**
	 * Automatically upgrade the plugin data store from one version to another.
	 *
	 * @param int $from
	 */
	public static function upgrade( $from ) {
		switch ( (int) $from ) {
			case 2:
				// Get all publications, since we're converting thumbnails to featured images
				$publications = get_posts(
					array(
					     'numberposts' => - 1,
					     'post_type'   => 'publication'
					)
				);

				foreach ( $publications as $publication ) {
					$content = get_post_meta( $publication->ID, 'wpa_doc_desc', true );

					// Upgrade content storage
					if ( ! empty( $content ) && empty( $publication->post_content ) ) {
						$publication->post_content = apply_filters( 'content_save_pre', $content );

						wp_update_post( $publication );
					}
				}
				break;
		}
	}

	/**
	 * Generate a link with a given endpoint.
	 *
	 * If no permalink is provided, it will be pulled back from WordPress.  In this case, the filter that auto-converts permalinks into open links will be removed and re-added.
	 *
	 * @param int         $publication_id Optional ID of the publication for which to generate a link.
	 * @param string      $endpoint       Optional endpoint name.
	 * @param bool|string $permalink      Optional existing permalink
	 * @param bool|string $key            Optional alternate download key
	 *
	 * @return string Download/Open link.
	 * @since 2.5
	 */
	protected static function get_link( $publication_id = 0, $endpoint = 'view', $permalink = false, $key = false ) {
		if ( ! $permalink ) {
			remove_filter( 'post_type_link', array( 'WP_Publication_Archive', 'publication_link' ) );
			$permalink = get_permalink( $publication_id );
			add_filter( 'post_type_link', array( 'WP_Publication_Archive', 'publication_link' ), 10, 2 );
		}

		$structure = get_option( 'permalink_structure' );

		if ( empty( $structure ) ) {
			$new = add_query_arg( $endpoint, 'yes', $permalink );

			if ( false !== $key ) {
				$new = add_query_arg( 'alt', $key, $new );
			}
		} else {
			$new = site_url() . '/publication/' . $endpoint . '/' . basename( $permalink );

			if ( false !== $key ) {
				$new .= '/' . $key;
			}
		}

		return $new;
	}

	/**
	 * Generate a link for a particular file download.
	 *
	 * @param int $publication_id Optional ID of the publication for which to retrieve a download link.
	 *
	 * @return string Open link.
	 * @since 2.5
	 */
	public static function get_open_link( $publication_id = 0 ) {
		return WP_Publication_Archive::get_link( $publication_id, 'view' );
	}

	/**
	 * Generate a link for a particular file download.
	 *
	 * @param int $publication_id Optional ID of the publication for which to retrieve a download link.
	 *
	 * @return string Download link.
	 * @since 2.5
	 */
	public static function get_download_link( $publication_id = 0 ) {
		return WP_Publication_Archive::get_link( $publication_id, 'download' );
	}

	/**
	 * Generate a link for a particular alternate file download.
	 *
	 * @param int         $publication_id Optional ID of the publication for which to retrieve a download link.
	 * @param string|bool $key            Optional key of the file to download
	 *
	 * @return string Download link.
	 * @since 3.0
	 */
	public static function get_alternate_open_link( $publication_id = 0, $key = false ) {
		return WP_Publication_Archive::get_link( $publication_id, 'altview', false, $key );
	}

	/**
	 * Generate a link for a particular alternate file download.
	 *
	 * @param int         $publication_id Optional ID of the publication for which to retrieve a download link.
	 * @param string|bool $key            Optional key of the file to download
	 *
	 * @return string Download link.
	 * @since 3.0
	 */
	public static function get_alternate_download_link( $publication_id = 0, $key = false ) {
		return WP_Publication_Archive::get_link( $publication_id, 'altdown', false, $key );
	}

	/**
	 * Filter WordPress' request so that we can send a redirect to the file if it's requested.
	 *
	 * @uses  apply_filters() Calls 'wppa_open_url' to get the download URL.
	 * @uses  apply_filters() Calls 'wppa_mask_url' to check whether the file source URL should be masked.
	 *
	 * @since 2.5
	 */
	public static function open_file() {
		global $wp_query;

		// If this isn't the right kind of request, bail.
		if ( ! isset( $wp_query->query_vars['wppa_open'] ) ) {
			return;
		}

		$publication = new WP_Publication_Archive_Item( $wp_query->post );

		// Set an empty URI so we don't get an error later.
		$uri = '';

		if ( isset( $wp_query->query_vars['wppa_alt'] ) ) {
			foreach( $publication->alternates as $alt ) {
				if ( urldecode( $wp_query->query_vars['wppa_alt'] ) === $alt['description'] ) {
					$uri = $alt['url'];
					break;
				}
			}
		} else {
			// Strip the old http| and https| if they're there
			$uri = str_replace( 'http|', 'http://', $publication->uri );
			$uri = str_replace( 'https|', 'https://', $uri );
		}

		$uri = apply_filters( 'wppa_open_url', $uri );

		if ( empty( $uri ) ) {
			return;
		}

		if ( apply_filters( 'wppa_mask_url', true ) ) {
			$content_length = false;
			$last_modified = false;

			// Attempt to grab the content length and last modified date for caching.
			$request = wp_remote_head( $uri, array( 'sslverify' => false ) );
			if ( ! is_wp_error( $request ) ) {
				$headers = wp_remote_retrieve_headers( $request );

				if ( isset( $headers['content-length'] ) ) {
					$content_length = $headers['content-length'];
				}

				if ( isset( $headers['last-modified'] ) ) {
					$last_modified = $headers['last-modified'];
				}
			}

			$mime = new mimetype();

			$content_type  = $mime->getType( basename( $uri ) );

			header( 'HTTP/1.1 200 OK' );
			header( 'Expires: Wed, 9 Nov 1983 05:00:00 GMT' );
			header( 'Content-type: ' . $content_type );
			header( 'Content-Transfer-Encoding: binary' );

			if ( false !== $content_length ) {
				header( 'Content-Length: ' . $content_length );
			}

			if ( false !== $last_modified ) {
				header( 'Last-Modified: ' . $last_modified );
			}

			// Return the remote file
			ob_clean();
			flush();
			readfile( $uri );
		} else {
			header( 'HTTP/1.1 303 See Other' );
			header( 'Location: ' . $uri );
		}

		exit();
	}

	/**
	 * Filter WordPress' request so that we can send a redirect to the file if it's requested.
	 *
	 * @uses  apply_filters() Calls 'wppa_download_url' to get the download URL.
	 * @since 2.5
	 */
	public static function download_file() {
		global $wp_query;

		// If this isn't the right kind of request, bail.
		if ( ! isset( $wp_query->query_vars['wppa_download'] ) ) {
			return;
		}

		$publication = new WP_Publication_Archive_Item( $wp_query->post );

		// Set an empty URI so we don't get an error later.
		$uri = '';

		if ( isset( $wp_query->query_vars['wppa_alt'] ) ) {
			foreach( $publication->alternates as $alt ) {
				if ( urldecode( $wp_query->query_vars['wppa_alt'] ) === $alt['description'] ) {
					$uri = $alt['url'];
					break;
				}
			}
		} else {
			// Strip the old http| and https| if they're there
			$uri = str_replace( 'http|', 'http://', $publication->uri );
			$uri = str_replace( 'https|', 'https://', $uri );
		}

		$uri = apply_filters( 'wppa_download_url', $uri );

		if ( empty( $uri ) ) {
			return;
		}

		if ( apply_filters( 'wppa_mask_url', true ) ) {
			$content_length = false;
			$last_modified = false;

			// Fetch the file from the remote server.
			$request = wp_remote_head( $uri, array( 'sslverify' => false ) );

			if ( ! is_wp_error( $request ) ) {
				$headers = wp_remote_retrieve_headers( $request );

				if ( isset( $headers['content-length'] ) ) {
					$content_length = $headers['content-length'];
				}

				if ( isset( $headers['last-modified'] ) ) {
					$last_modified = $headers['last-modified'];
				}
			}

			$mime = new mimetype();

			$content_type = $mime->getType( basename( $uri ) );

				header( 'HTTP/1.1 200 OK' );
				header( 'Expires: Wed, 9 Nov 1983 05:00:00 GMT' );
				header( 'Content-Disposition: attachment; filename=' . basename( $uri ) );
				header( 'Content-type: ' . $content_type );
				header( 'Content-Transfer-Encoding: binary' );

			if ( false !== $content_length ) {
				header( 'Content-Length: ' . $content_length );
			}

			if ( false !== $last_modified ) {
				header( 'Last-Modified: ' . $last_modified );
			}

			// Return the remote file
			ob_clean();
			flush();
			readfile( $uri );
		} else {
			header( 'HTTP/1.1 303 See Other' );
			header( 'Location: ' . $uri );
		}

		exit();
	}

	/**
	 * Get an image for the publication based on its MIME type.
	 *
	 * @uses apply_filters Calls 'wppa_publication_icon' to allow adding icons for unregistered MIME types.
	 *
	 * @param string $doctype MIME type of the file.
	 *
	 * @return string
	 */
	public static function get_image( $doctype ) {
		switch ( $doctype ) {
			case 'application/pdf':
			case 'application/postscript':
				$image_url = WP_PUB_ARCH_URL . 'images' . '/icons/pdf.png';
				break;
			case 'application/zip':
			case 'application/x-stuffit':
			case 'application/x-rar-compressed':
			case 'application/x-tar':
				$image_url = WP_PUB_ARCH_URL . 'images' . '/icons/zip.png';
				break;
			case 'audio/basic':
			case 'audio/mp4':
			case 'audio/mpeg':
			case 'audio/ogg':
			case 'audio/vorbis':
			case 'audio/x-ms-wma':
			case 'audio/x-ms-wax':
			case 'audio/vnd.rn-realaudio':
			case 'audio/vnd.wave':
				$image_url = WP_PUB_ARCH_URL . 'images' . '/icons/audio.png';
				break;
			case 'image/gif':
			case 'image/jpeg':
			case 'image/png':
			case 'image/svg+xml':
			case 'image/tiff':
			case 'image/vnd.microsoft.icon':
			case 'application/vnd.oasis.opendocument.graphics':
			case 'application/vnd.ms-excel':
				$image_url = WP_PUB_ARCH_URL . 'images' . '/icons/image.png';
				break;
			case 'text/cmd':
			case 'text/css':
			case 'text/plain':
			case 'application/vnd.oasis.opendocument.text':
			case 'application/vnd.oasis.opendocument.presentation':
			case 'application/vnd.ms-powerpoint':
			case 'application/vnd.openxmlformats-officedocument.presentationml.presentation':
			case 'application/msword':
			case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
				$image_url = WP_PUB_ARCH_URL . 'images' . '/icons/doc.png';
				break;
			case 'text/csv':
			case 'application/vnd.oasis.opendocument.spreadsheet':
			case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':
				$image_url = WP_PUB_ARCH_URL . 'images' . '/icons/data.png';
				break;
			case 'video/mpeg':
			case 'video/mp4':
			case 'video/ogg':
			case 'video/quicktime':
			case 'video/webm':
			case 'video/x-ms-wmv':
				$image_url = WP_PUB_ARCH_URL . 'images' . '/icons/video.png';
				break;
			default:
				$image_url = WP_PUB_ARCH_URL . 'images' . '/icons/unknown.png';
		}

		return apply_filters( 'wppa_publication_icon', $image_url, $doctype );
	}

	/**
	 * Queue up scripts and styles, based on whether the user is on the admin or the front-end.
	 *
	 * @uses wp_enqueue_script()
	 * @uses wp_enqueue_style()
	 */
	public static function enqueue_scripts_and_styles() {
		if ( is_admin() ) {
			wp_enqueue_script( 'media-upload' );
			wp_enqueue_script( 'thickbox' );
			wp_enqueue_style( 'thickbox' );
		} else {
			wp_enqueue_style( 'wp-publication-archive-frontend', WP_PUB_ARCH_URL . 'includes/front-end.css', array(), WP_PUB_ARCH_VERSION, 'all' );
		}
	}

	/**
	 * Register the Publication custom post type.
	 *
	 * @uses register_post_type()
	 */
	public static function register_publication() {
		$labels = array(
			'name'               => __( 'Publications', 'wp_pubarch_translate' ),
			'singular_name'      => __( 'Publication', 'wp_pubarch_translate' ),
			'add_new_item'       => __( 'Add New Publication', 'wp_pubarch_translate' ),
			'edit_item'          => __( 'Edit Publication', 'wp_pubarch_translate' ),
			'new_item'           => __( 'New Publication', 'wp_pubarch_translate' ),
			'view_item'          => __( 'View Publication', 'wp_pubarch_translate' ),
			'search_items'       => __( 'Search Publications', 'wp_pubarch_translate' ),
			'not_found'          => __( 'No publications found', 'wp_pubarch_translate' ),
			'not_found_in_trash' => __( 'No publications found in trash', 'wp_pubarch_translate' )
		);

		register_post_type( 'publication',
			array(
			     'labels'               => $labels,
			     'capability_type'      => 'post',
			     'public'               => true,
			     'publicly_queryable'   => true,
			     'has_archive'          => true,
			     'menu_position'        => 20,
			     'supports'             => array(
				     'title',
				     'editor'
			     ),
			     'taxonomies'           => array(
				     'category',
				     'post_tag'
			     ),
			     'register_meta_box_cb' => array( 'WP_Publication_Archive', 'pub_meta_boxes' ),
			     'can_export'           => true,
			     'menu_icon'            => WP_PUB_ARCH_URL . 'images/cabinet.png'
			)
		);
	}

	/**
	 * Register the publication author taxonomy.
	 *
	 * @uses register_taxonomy
	 * @todo Create a custom meta box to allow listing previously used authors rather than the freeform Tag box.
	 */
	public static function register_author() {
		$labels = array(
			'name'          => __( 'Authors', 'wp_pubarch_translate' ),
			'singular_name' => __( 'Author', 'wp_pubarch_translate' ),
			'search_items'  => __( 'Search Authors', 'wp_pubarch_translate' ),
			'popular_items' => __( 'Popular Authors', 'wp_pubarch_translate' ),
			'all_items'     => __( 'All Authors', 'wp_pubarch_translate' ),
			'edit_item'     => __( 'Edit Author', 'wp_pubarch_translate' ),
			'update_item'   => __( 'Update Author', 'wp_pubarch_translate' ),
			'add_new_item'  => __( 'Add New Author', 'wp_pubarch_translate' ),
			'new_item_name' => __( 'New Author Name', 'wp_pubarch_translate' ),
			'menu_name'     => __( 'Authors', 'wp_pubarch_translate' ),
		);

		register_taxonomy(
			'publication-author',
			array( 'publication' ),
			array(
			     'hierarchical' => false,
			     'labels'       => $labels,
			     'label'        => __( 'Authors', 'wp_pubarch_translate' ),
			     'query_var'    => false,
			     'rewrite'      => false
			)
		);
	}

	/**
	 * Register custom meta boxes for the Publication oage.
	 */
	public static function pub_meta_boxes() {
		add_meta_box( 'publication_uri', __( 'Publication', 'wp_pubarch_translate' ), array( 'WP_Publication_Archive', 'doc_uri_box' ), 'publication', 'normal', 'high', '' );
		add_meta_box( 'publication_alternates', __( 'Alternate Files', 'wp_pubarch_translate' ), array( 'WP_Publication_Archive', 'doc_alternates_box' ), 'publication', 'normal', 'high', '' );
		add_meta_box( 'publication_thumb', __( 'Thumbnail', 'wp_pubarch_translate' ),   array( 'WP_Publication_Archive', 'doc_thumb_box'), 'publication', 'normal', 'high', '' );
	}

	/**
	 * Build the Publication link box
	 *
	 * @param WP_Post $post
	 */
	public static function doc_uri_box( $post ) {
		wp_nonce_field( plugin_basename( __FILE__ ), 'wpa_nonce' );

		$uri = get_post_meta( $post->ID, 'wpa_upload_doc', true );
		echo '<p>' . __( 'Please provide the absolute url of the file (including the <code>http://</code>):', 'wp_pubarch_translate' ) . '</p>';
		echo '<input type="text" id="wpa_upload_doc" name="wpa_upload_doc" value="' . $uri . '" size="25" style="width:85%" />';
		echo '<input class="button" id="upload_doc_button" type="button" value="' . __( 'Upload Publication', 'wp_pubarch_translate' ) . '" alt="' . __( 'Upload Publication', 'wp_pubarch_translate' ) . '" />';
		?>
    <script type="text/javascript">
        ( function ( window, $, undefined ) {
            var handle_doc_upload = function () {
                var document = window.document;

                window.orig_send_to_editor = window.send_to_editor;
                window.send_to_editor = function ( html ) {
                    document.getElementById( 'wpa_upload_doc' ).value = $( html ).attr( 'href' );

                    window.tb_remove();

                    // Restore original handler
                    window.send_to_editor = window.orig_send_to_editor;
                };

                window.tb_show( '<?php _e( 'Upload Publication', 'wp_pubarch_translate' ); ?>', 'media-upload.php?TB_iframe=1&width=640&height=263' );
                return false;
            };

            $( '#upload_doc_button' ).on( 'click', handle_doc_upload );
        } )( this, jQuery );
    </script>
	<?php
	}

	/**
	 * Build the Publication thumbnail image box.
	 *
	 * @param WP_Post $post
	 */
	public static function doc_thumb_box( $post ) {
		$thumb = get_post_meta( $post->ID, 'wpa-upload_image', true );

		echo '<p>' . __( 'Please provide the absolute url for a thumbnail image (including the <code>http://</code>):', 'wp_pubarch_translate' ) . '</p>';
		echo '<input type="text" id="wpa-upload_image" name="wpa-upload_image" value=" ' . $thumb . '" size="36" size="25" style="width:85%" />';
		echo '<input class="button" id="wpa-upload_image_button" type="button" value="' . __( 'Upload Thumbnail', 'wp_pubarch_translate' ) . '" alt="' . __( 'Upload Thumbnail', 'wp_pubarch_translate' ) . '" />';
		?>
		<script type="text/javascript">
			( function( window, $, undefined ) {
				var handle_thumb_upload = function() {
					var document = window.document;

					window.orig_send_to_editor = window.send_to_editor;
					window.send_to_editor = function( html ) {
						document.getElementById( 'wpa-upload_image' ).value = $( html ).attr( 'href' );

						window.tb_remove();

						// Restore original handler
						window.send_to_editor = window.orig_send_to_editor;
					};

					window.tb_show( '<?php _e( 'Upload Thumbnail', 'wp_pubarch_translate' ); ?>', 'media-upload.php?TB_iframe=1&width=640&height=263' );
					return false;
				}

				$( '#wpa-upload_image_button' ).on( 'click', handle_thumb_upload );
			} )( this, jQuery );
		</script>
	<?php
	}

	/**
	 * Output a meta box with repeatable alternate upload fields
	 *
	 * @param WP_Post $post
	 */
	public static function doc_alternates_box( $post ) {
		$alternates = get_post_meta( $post->ID, 'wpa-upload_alternates' );

		echo '<p>' . __( 'These files are considered alternates to the publication listed above (i.e. foreign language translations of the same document).', 'wp_pubarch_translate' ) . '</p>';
		echo '<table id="wpa-alternate-table" style="width:100%;">';
		echo '<thead><tr style="text-align:left;"><th>Description</th><th>Absolute Url</th><th></th></tr></thead>';
		echo '<tbody>';
		foreach( $alternates as $alternate ) {
			echo '<tr>';
			echo '<td style="width:30%;"><input style="width:100%;" type="text" name="wpa-alternates[description][]" value="' . esc_attr( $alternate['description'] ) . '" /></td>';
			echo '<td style="width:60%;"><input style="width:100%;" type="text" name="wpa-alternates[url][]" value="' . esc_attr( $alternate['url'] ) . '" /></td>';
			echo '<td style="text-align:center;width:10%;"><span class="wpa-upload-row" style="cursor:pointer;border-bottom:1px solid #000;">' . __( 'upload', 'wp_pubarch_translate' ) . '</span> | <span class="wpa-delete-row" style="cursor:pointer;color:#f00;border-bottom:1px solid #f00;">' . __( 'delete', 'wp_pubarch_translate' ) . '</span></td>';
			echo '</tr>';
		}

		echo '<tr>';
		echo '<td style="width:30%;"><input style="width:100%;" type="text" name="wpa-alternates[description][]" value="" /></td>';
		echo '<td style="width:60%;"><input style="width:100%;" type="text" name="wpa-alternates[url][]" value="" /></td>';
		echo '<td style="text-align:center;width:10%;"><span class="wpa-upload-row" style="cursor:pointer;border-bottom:1px solid #000;">' . __( 'upload', 'wp_pubarch_translate' ) . '</span> | <span class="wpa-delete-row" style="cursor:pointer;color:#f00;border-bottom:1px solid #f00;">' . __( 'delete', 'wp_pubarch_translate' ) . '</span></td>';
		echo '</tr>';
		echo '</tbody>';
		echo '</table>';

		echo '<input class="button" id="wpa-alternates-button" type="button" value="' . __( 'Add Row', 'wp_pubarch_translate' ) . '" alt="' . __( 'Add Row', 'wp_pubarch_translate' ) . '" />';
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
					input1.setAttribute( 'name', 'wpa-alternates[description][]' );
					td1.appendChild( input1 );

					var td2 = document.createElement( 'td' );
					td2.style.width = '60%';
					row.appendChild( td2 );
					var input2 = document.createElement( 'input' );
					input2.style.width = '100%';
					input2.setAttribute( 'type', 'text' );
					input2.setAttribute( 'name', 'wpa-alternates[url][]' );
					td2.appendChild( input2 );

					var td3 = document.createElement( 'td' );
					td3.style.width = '10%';
					td3.style.textAlign = 'center';
					row.appendChild( td3 );
					var span1 = document.createElement( 'span' );
					span1.className = 'wpa-upload-row';
					span1.style.borderBottom = '1px solid #000';
					span1.style.cursor = 'pointer';
					span1.innerText = '<?php _e( 'upload', 'wp_pubarch_translate' ); ?>';
					td3.appendChild( span1 );
					td3.appendChild( document.createTextNode( ' | ' ) );
					var span2 = document.createElement( 'span' );
					span2.className = 'wpa-delete-row';
					span2.style.color = '#f00';
					span2.style.borderBottom = '1px solid #f00';
					span2.style.cursor = 'pointer';
					span2.innerText = '<?php _e( 'delete', 'wp_pubarch_translate' ); ?>';
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
						target = $this.parents( 'tr' ).find( 'input[name="wpa-alternates[url][]"]' );

					var send_handler = function( html ) {
						target.val( $( html ).attr( 'href' ) );

						window.tb_remove();

						window.send_to_editor = editor_store;
					};

					editor_store = window.send_to_editor;
					window.send_to_editor = send_handler;
					window.tb_show( '<?php _e( 'Upload Alternate', 'wp_pubarch_translate' ); ?>', 'media-upload.php?TB_iframe=1&width=640&height=263' );
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
	 * Save our changes to Publication meta information.
	 *
	 * @param int $post_id ID of the Publication we're updating
	 *
	 * @return int
	 */
	public static function save_meta( $post_id ) {
		$post = get_post( $post_id );
		if ( $post->post_type != 'publication' ) {
			return $post_id;
		}

		if ( ! isset( $_POST['wpa_nonce'] ) || ! wp_verify_nonce( $_POST['wpa_nonce'], plugin_basename( __FILE__ ) ) ) {
			return $post_id;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		$uri = isset( $_POST['wpa_upload_doc'] ) && '' != trim( $_POST['wpa_upload_doc'] ) ? esc_url_raw( $_POST['wpa_upload_doc'] ) : '';
		$thumbnail = isset( $_POST['wpa-upload_image'] ) && '' != trim( $_POST['wpa-upload_image'] ) ? esc_url_raw( $_POST['wpa-upload_image'] ) : '';

		update_post_meta( $post_id, 'wpa_upload_doc', $uri );
		update_post_meta( $post_id, 'wpa-upload_image', $thumbnail );

		// Handle alternate uploads
		delete_post_meta( $post_id, 'wpa-upload_alternates' );
		if ( isset( $_POST['wpa-alternates'] ) ) {
			for ( $i = 0; $i <= count( $_POST['wpa-alternates'] ); $i++ ) {
				$description = $_POST['wpa-alternates']['description'][ $i ];
				$url = $_POST['wpa-alternates']['url'][ $i ];

				if ( '' === trim( $url ) ) {
					continue;
				}

				add_post_meta( $post_id, 'wpa-upload_alternates', array( 'description' => $description, 'url' => $url ) );
			}
		}

		return $post_id;
	}

	/**
	 * Handle the 'wp-publication-archive' shortcode and provided filters.
	 *
	 * @param array $atts Shortcode arguments.
	 *
	 * @return string Shortcode output.
	 * @uses apply_filters() Calls 'wwpa_list_limit' to get the number of publications listed on each page.
	 * @uses apply_filters() Calls 'wppa_list_template' to get the shortcode template file.
	 * @uses apply_filters() Calls 'wppa_dropdown_template' to get the shortcode template file.
	 */
	public static function shortcode_handler( $atts ) {
		global $post;

		/**
		 * @var string $categories List of category slugs to filter.
		 * @var string $author     Author slug to filter.
		 * @var number $limit      Number of publications per page.
		 * @var string $showas     Format to use when displaying publications.
		 */
		extract( shortcode_atts( array(
		                              'categories' => '',
		                              'author'     => '',
		                              'limit'      => 10,
		                              'showas'     => 'list'
		                         ), $atts ) );

		$limit = apply_filters( 'wpa-pubs_per_page', $limit ); // Ugly, deprecated filter.
		$limit = apply_filters( 'wppa_list_limit', $limit );

		if ( isset( $_GET['wpa-paged'] ) ) {
			$paged  = (int) $_GET['wpa-paged'];
			$offset = $limit * ( $paged - 1 );
		} else {
			$paged  = 1;
			$offset = 0;
		}

		// Get publications
		$args = array(
			'offset'      => $offset,
			'numberposts' => $limit,
			'post_type'   => 'publication',
			'orderby'     => 'post_date',
			'order'       => 'DESC',
			'post_status' => 'publish'
		);

		if ( '' != $categories ) {
			// Create an array of category IDs based on the categories fed in.
			$catFilter = array();
			$catList   = explode( ',', $categories );
			foreach ( $catList as $catName ) {
				$id = get_cat_id( trim( $catName ) );
				if ( 0 !== $id )
					$catFilter[] = $id;
			}
			// if no categories matched categories in the database, report failure
			if ( empty( $catFilter ) ) {
				$error_msg = "<div class='publication-archive'><p>" . __( ' Sorry, but the categories you passed to the wp-publication-archive shortcode do not match any publication categories.', 'wp_pubarch_translate' ) . "</p><p>" . __( 'You passed: ', 'wp_pubarch_translate' ) . "<code>$categories</code></p></div>";

				return $error_msg;
			}
			$args['category'] = implode( ',', $catFilter );
		}

		if ( '' != $author ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'publication-author',
					'field'    => 'slug',
					'terms'    => $author
				)
			);
		}

		$publications = get_posts( $args );

		$args['numberposts'] = - 1;
		$total_pubs          = count( get_posts( $args ) );

		// Report if there are no publications matching filters
		if ( 0 == $total_pubs ) {
			$error_msg = "<p>" . __( 'There are no publications to display', 'wp_pubarch_translate' );
			if ( '' != $author )
				$error_msg .= __( ' by ', 'wp_pubarch_translate' ) . $author;
			if ( '' != $categories ) {
				// There is probably a better way to do this
				$error_msg .= __( ' categorized ', 'wp_pubarch_translate' );
				$catList = explode( ',', $categories );
				$catNum  = count( $catList );
				$x       = 3; // number of terms necessary for grammar to require commas after each term
				if ( $catNum > 2 ) $x = 1;
				for ( $i = 0; $i < $catNum; $i ++ ) {
					if ( $catNum > 1 && $i == ( $catNum - 1 ) ) $error_msg .= 'or ';
					$error_msg .= $catList[$i];
					if ( $i < ( $catNum - $x ) ) {
						$error_msg .= ', ';
					} else if ( $i < ( $catNum - 1 ) ) {
						$error_msg .= ' ';
					}
				}
			}
			$error_msg .= ".</p>";

			return $error_msg;
		}

		switch ( $showas ) {
			case 'dropdown':
				// Get the publication list template
				$template_name = apply_filters( 'wppa_dropdown_template', 'template.wppa_publication_dropdown.php' );
				break;
			case 'list':
			default:
				// Get the publication list template
				$template_name = apply_filters( 'wppa_list_template', 'template.wppa_publication_list.php' );
		}

		$path = locate_template( $template_name );
		if ( empty( $path ) ) {
			$path = WP_PUB_ARCH_DIR . 'includes/' . $template_name;
		}

		// Get a global container variable and populate it with our data
		global $wppa_container;
		$wppa_container = array(
			'publications' => $publications,
			'total_pubs'   => $total_pubs,
			'limit'        => $limit,
			'offset'       => $offset,
			'paged'        => $paged,
			'post'         => $post
		);
		$wppa_container = apply_filters( 'wppa_publication_list_container', $wppa_container );

		// Start a buffer to capture the HTML output of the shortcode.
		ob_start();

		include( $path );

		$output = ob_get_contents();

		ob_end_clean();

		// Because globals are evil, clean up afterwards.
		unset( $wppa_container );

		return $output;
	}

	/**
	 * Register new query variables.
	 *
	 * @param array $public_vars Query variables.
	 *
	 * @return array Query variables.
	 */
	public static function query_vars( $public_vars ) {
		$public_vars[] = 'wpa-paged';

		return $public_vars;
	}

	/**
	 * Register our custom rewrite slugs and URLs.
	 */
	public static function custom_rewrites() {
		add_rewrite_tag( '%wppa_download%', '(.+)' );
		add_rewrite_tag( '%wppa_open%', '(.+)' );
		add_rewrite_tag( '%wppa_alt%', '(.+)' );
		add_rewrite_rule( '^publication/download/([^/]+)(/[0-9]+)?/?$', 'index.php?publication=$matches[1]&wppa_download=yes', 'top' );
		add_rewrite_rule( '^publication/view/([^/]+)(/[0-9]+)?/?$', 'index.php?publication=$matches[1]&wppa_open=yes', 'top' );
		add_rewrite_rule( '^publication/altdown/([^/]+)/([^/]+)/?$', 'index.php?publication=$matches[1]&wppa_download=yes&wppa_alt=$matches[2]', 'top' );
		add_rewrite_rule( '^publication/altview/([^/]+)/([^/]+)/?$', 'index.php?publication=$matches[1]&wppa_open=yes&wppa_alt=$matches[2]', 'top' );

		add_rewrite_rule( '^publication/category/(.+?)/feed/(feed|rdf|rss|rss2|atom)/?$', 'index.php?post_type=publication&category_name=$matches[1]&feed=$matches[2]', 'top' );
		add_rewrite_rule( '^publication/category/(.+?)/(feed|rdf|rss|rss2|atom)/?$', 'index.php?post_type=publication&category_name=$matches[1]&feed=$matches[2]', 'top' );
		add_rewrite_rule( '^publication/category/(.+?)/page/?([0-9]{1,})/?$', 'index.php?post_type=publication&category_name=$matches[1]&paged=$matches[2]', 'top' );
		add_rewrite_rule( '^publication/category/(.+?)/?$', 'index.php?post_type=publication&category_name=$matches[1]', 'top' );
	}

	/**
	 * The post link for Publications is actually link to *open* the file, rather than to open the post page.  Filter
	 * out requests so we generate the correct link.
	 *
	 * @param string $permalink
	 * @param object $post
	 *
	 * @return string
	 */
	public static function publication_link( $permalink, $post ) {
		if ( 'publication' != $post->post_type )
			return $permalink;

		$pub = new WP_Publication_Archive_Item( $post );

		return self::get_link( $pub->ID, 'wppa_open', $permalink );
	}

	/**
	 * Filter the content of a Publication.
	 *
	 * Since Publications aren't using the regular post editor for their description, we need to hook in to calls
	 * to `the_content()` to filter out what's stored in the database and replace it with what's stored in the description
	 * meta field.
	 *
	 * We won't use the actual post content for Publications because, eventually, this will contain full-text references
	 * from the Publication itself to aid in full-text searching within WordPress.
	 *
	 * @param string $content Regular post content from the `wp_posts` table.
	 *
	 * @return string Actual summary description of the Publication, or unfiltered text if this isn't a Publication.
	 */
	public static function the_content( $content ) {
		global $post;
		if ( 'publication' != $post->post_type ) {
			return $content;
		}

		$pub = new WP_Publication_Archive_Item( $post );

		return $pub->summary;
	}

	/**
	 * Filter the title to append "(Download Publication)" where necessary.
	 *
	 * @param string $title Original title
	 * @param int    $id    Post ID
	 *
	 * @return string
	 */
	public static function the_title( $title, $id = 0 ) {
		// If the filter is called without passing in an ID, it's being called incorrectly.  Rather than spewing a PHP warning,
		// we will just exit out.  This code was added specifically to handle bad plugins like All-in-One Event Calendar.
		if ( 0 == $id ) {
			return $title;
		}

		$post = get_post( $id );
		if ( 'publication' != $post->post_type || is_admin() )
			return $title;

		return sprintf( __( '%s (Publication)', 'wp_pubarch_translate' ), $title );
	}

	/**
	 * Also check if the search term is contained in the publication's description.
	 *
	 * @param string $where Existing search query string.
	 *
	 * @uses  add_filter()
	 *
	 * @return string
	 *
	 * @since 2.5
	 */
	public static function search( $where ) {
		if ( ! is_search() ) {
			return $where;
		}

		global $wpdb, $wp;

		$where = preg_replace(
			"/($wpdb->posts.post_title (LIKE '%{$wp->query_vars['s']}%'))/i",
			"$0 OR ($wpdb->postmeta.meta_key = 'wpa_doc_desc' AND $wpdb->postmeta.meta_value $1)",
			$where
		);

		$where = preg_replace(
			"/$wpdb->postmeta.meta_value $wpdb->posts.post_title LIKE/",
			"$wpdb->postmeta.meta_value LIKE",
			$where
		);

		add_filter( 'posts_join_request', array( 'WP_Publication_Archive', 'search_join' ) );
		add_filter( 'posts_distinct_request', array( 'WP_Publication_Archive', 'search_distinct' ) );

		return $where;
	}

	/**
	 * Add post meta to the search Query.
	 *
	 * @param string $join Existing search query string.
	 *
	 * @return string
	 *
	 * @since 2.5
	 */
	public static function search_join( $join ) {
		global $wpdb;

		return $join .= " LEFT JOIN $wpdb->postmeta ON ($wpdb->posts.ID = $wpdb->postmeta.post_id) ";
	}

	/**
	 * Force the search to only return distinct values.
	 *
	 * @param string $distinct
	 *
	 * @return string
	 */
	public static function search_distinct( $distinct ) {
		return 'DISTINCT';
	}

	/**
	 * Utility function to return a WP_Query object with Publication posts
	 *
	 * @author Matthew Eppelsheimer
	 * @since  2.5
	 */
	public static function query_publications( $args ) {
		$defaults = array(
			'posts_per_page' => - 1,
			'order'          => 'ASC',
			'orderby'        => 'menu_order'
		);

		$query_args              = wp_parse_args( $args, $defaults );
		$query_args['post_type'] = 'publication';

		$results = new WP_Query( $query_args );

		return $results;
	}

	/**
	 * Allow users to filter the length of only publication summaries.
	 *
	 * @param int $length
	 *
	 * @return int
	 */
	public static function custom_excerpt_length( $length ) {
		global $post;

		if ( 'publication' !== $post->post_type ) {
			return $length;
		}

		return apply_filters( 'wpa-summary-length', $length );
	}
}