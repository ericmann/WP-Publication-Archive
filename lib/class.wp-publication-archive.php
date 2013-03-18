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
	 *
	 * @return string Download/Open link.
	 * @since 2.5
	 */
	protected static function get_link( $publication_id = 0, $endpoint = 'wppa_open', $permalink = false ) {
		if ( ! $permalink ) {
			remove_filter( 'post_type_link', array( 'WP_Publication_Archive', 'publication_link' ) );
			$permalink = get_permalink( $publication_id );
			add_filter( 'post_type_link', array( 'WP_Publication_Archive', 'publication_link' ), 10, 2 );
		}

		$structure = get_option( 'permalink_structure' );

		if ( empty( $structure ) ) {
			$new = add_query_arg( $endpoint, 1, $permalink );
		} else {
			$new = trailingslashit( $permalink ) . $endpoint;
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
		return WP_Publication_Archive::get_link( $publication_id, 'wppa_open' );
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
		return WP_Publication_Archive::get_link( $publication_id, 'wppa_download' );
	}

	/**
	 * Filter WordPress' request so that we can send a redirect to the file if it's requested.
	 *
	 * @uses  apply_filters() Calls 'wppa_download_url' to get the download URL.
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

		$uri = get_post_meta( $wp_query->post->ID, 'wpa_upload_doc', true );

		// Strip the old http| and https| if they're there
		$uri = str_replace( 'http|', 'http://', $uri );
		$uri = str_replace( 'https|', 'https://', $uri );

		$uri = apply_filters( 'wppa_download_url', $uri );

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

		$uri = get_post_meta( $wp_query->post->ID, 'wpa_upload_doc', true );

		// Strip the old http| and https| if they're there
		$uri = str_replace( 'http|', 'http://', $uri );
		$uri = str_replace( 'https|', 'https://', $uri );

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
				$image_url = WP_PUB_ARCH_IMG_URL . '/icons/pdf.png';
				break;
			case 'application/zip':
			case 'application/x-stuffit':
			case 'application/x-rar-compressed':
			case 'application/x-tar':
				$image_url = WP_PUB_ARCH_IMG_URL . '/icons/zip.png';
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
				$image_url = WP_PUB_ARCH_IMG_URL . '/icons/audio.png';
				break;
			case 'image/gif':
			case 'image/jpeg':
			case 'image/png':
			case 'image/svg+xml':
			case 'image/tiff':
			case 'image/vnd.microsoft.icon':
			case 'application/vnd.oasis.opendocument.graphics':
			case 'application/vnd.ms-excel':
				$image_url = WP_PUB_ARCH_IMG_URL . '/icons/image.png';
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
				$image_url = WP_PUB_ARCH_IMG_URL . '/icons/doc.png';
				break;
			case 'text/csv':
			case 'application/vnd.oasis.opendocument.spreadsheet':
			case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':
				$image_url = WP_PUB_ARCH_IMG_URL . '/icons/data.png';
				break;
			case 'video/mpeg':
			case 'video/mp4':
			case 'video/ogg':
			case 'video/quicktime':
			case 'video/webm':
			case 'video/x-ms-wmv':
				$image_url = WP_PUB_ARCH_IMG_URL . '/icons/video.png';
				break;
			default:
				$image_url = WP_PUB_ARCH_IMG_URL . '/icons/unknown.png';
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
	}

	/**
	 * Build the Publication link box
	 */
	public static function doc_uri_box() {
		global $post;

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

                formfield = document.getElementById( 'wpa_upload_doc' ).getAttribute( 'name' );
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
	 */
	public static function doc_thumb_box() {
		global $post;

		$thumb = get_post_meta( $post->ID, 'wpa-upload_image', true );

		_e( 'Enter an URL or upload an image for the thumb.', 'wp_pubarch_translate' );
		echo '<br />';
		echo '<br />';
		echo '<label for="wpa-upload_image">';
		echo '<input id="wpa-upload_image" type="text" size="36" name="wpa-upload_image" value=" ' . $thumb . '" />';
		echo '<input id="wpa-upload_image_button" type="button" value="' . __( 'Upload Thumb', 'wp_pubarch_translate' ) . '" />';
		?>
		<script type="text/javascript">
			( function( window, $, undefined ) {
				var handle_thumb_upload = function() {
					var document = window.document;

					window.orig_send_to_editor = window.send_to_editor;
					window.send_to_editor = function( html ) {
						var imgurl = jQuery('img',html).attr('src');
						jQuery('#wpa-upload_image').val(imgurl);
						tb_remove();

						// Restore original handler
						window.send_to_editor = window.orig_send_to_editor;
					};

					$( '#wpa-upload_image_button' ).on( 'click', handle_thumb_upload );
				}
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
		add_rewrite_rule( '^publication/download/([^/]+)(/[0-9]+)?/?$', 'index.php?publication=$matches[1]&wppa_download=yes', 'top' );
		add_rewrite_rule( '^publication/view/([^/]+)(/[0-9]+)?/?$', 'index.php?publication=$matches[1]&wppa_open=yes', 'top' );
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