<?php
class WP_Publication_Archive {

	public static $mimetypes = array(
		'application/pdf' => 'pdf',
		'application/postscript' => 'pdf',
		'application/zip' => 'zip',

		'audio/basic' => 'audio',
		'audio/mp4' => 'audio',
		'audio/mpeg' => 'audio',
		'audio/ogg' => 'audio',
		'audio/vorbis' => 'audio',
		'audio/x-ms-wma' => 'audio',
		'audio/x-ms-wax' => 'audio',
		'audio/vnd.rn-realaudio' => 'audio',
		'audio/vnd.wave' => 'audio',

		'image/gif' => 'image',
		'image/jpeg' => 'image',
		'image/png' => 'image',
		'image/svg+xml' => 'image',
		'image/tiff' => 'image',
		'image/vnd.microsoft.icon' => 'image',

		'text/cmd' => 'doc',
		'text/css' => 'doc',
		'text/csv' => 'data',
		'text/plain' => 'doc',

		'video/mpeg' => 'video',
		'video/mp4' => 'video',
		'video/ogg' => 'video',
		'video/quicktime' => 'video',
		'video/webm' => 'video',
		'video/x-ms-wmv' => 'video',

		'application/vnd.oasis.opendocument.text' => 'doc',
		'application/vnd.oasis.opendocument.spreadsheet' => 'data',
		'application/vnd.oasis.opendocument.presentation' => 'doc',
		'application/vnd.oasis.opendocument.graphics' => 'image',
		'application/vnd.ms-excel' => 'image',
		'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'data',
		'application/vnd.ms-powerpoint' => 'doc',
		'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'doc',
		'application/msword' => 'doc',
		'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'doc',

		'application/x-stuffit' => 'zip',
		'application/x-rar-compressed' => 'zip',
		'application/x-tar' => 'zip'
	);
	
	public static function get_image( $doctype ) {
		if( ! isset( WP_Publication_Archive::$mimetypes[$doctype] ) ) {
			return WP_PUB_ARCH_IMG_URL . '/icons/unknown.png';
		}
		
		return WP_PUB_ARCH_IMG_URL . '/icons/' . WP_Publication_Archive::$mimetypes[$doctype] . '.png';
	}

	public static function enqueue_scripts_and_styles() {
		if( is_admin() ) {
			wp_enqueue_script( 'media-upload' );
			wp_enqueue_script( 'thickbox' );
			wp_enqueue_style( 'thickbox' );
		} else {
			wp_enqueue_style( 'wp-publication-archive-frontend', WP_PUB_ARCH_INC_URL . '/front-end.css', '', '2.0', 'all' );
		}
	}

	public static function register_publication() {
		$labels = array(
			'name'                  => __( 'Publications' ),
			'singular_name'         => __( 'Publication' ),
			'add_new_item'          => __( 'Add New Publication' ),
			'edit_item'             => __( 'Edit Publication' ),
			'new_item'              => __( 'New Publication' ),
			'view_item'             => __( 'View Publication' ),
			'search_items'          => __( 'Search Publications' ),
			'not_found'             => __( 'No publications found' ),
			'not_found_in_trash'    => __( 'No publications found in trash' )
		);

		register_post_type( 'publication',
			array(
				'labels' => $labels,
				'capability_type' => 'post',
				'public' => true,
				'menu_position' => 20,
				'supports' => array(
					'title'
				),
				'taxonomies' => array(
					'category',
					'post_tag'
				),
				'register_meta_box_cb' => array( 'WP_Publication_Archive', 'pub_meta_boxes' ),
				'can_export' => true,
				'menu_icon' => WP_PUB_ARCH_IMG_URL . '/cabinet.png'
			)
		);
	}

	public static function register_author() {
		$labels = array(
			'name' => __( 'Authors' ),
			'singular_name' => __( 'Author' ),
			'search_items' => __( 'Search Authors' ),
			'popular_items' => __( 'Popular Authors' ),
			'all_items' => __( 'All Authors' ),
			'edit_item' => __( 'Edit Author' ),
			'update_item' => __( 'Update Author' ),
			'add_new_item' => __( 'Add New Author' ),
			'new_item_name' => __( 'New Author Name' ),
		    'menu_name' => __( 'Authors' ),
		);

		register_taxonomy(
			'publication-author',
			array( 'publication' ),
			array(
				'hierarchical' => false,
				'labels' => $labels,
				'label' => 'Authors',
				'query_var' => false,
				'rewrite' => false
			)
		);
	}

	public static function pub_meta_boxes() {
		add_meta_box( 'publication_desc', 'Summary', array( 'WP_Publication_Archive', 'doc_desc_box' ), 'publication', 'normal', 'high', '' );
		add_meta_box( 'publication_uri', 'Publication', array( 'WP_Publication_Archive', 'doc_uri_box' ), 'publication', 'normal', 'high', '' );
		add_meta_box( 'publication_thumb', 'Thumbnail', array( 'WP_Publication_Archive', 'doc_thumb_box'), 'publication', 'normal', 'high', '' );
		
		remove_meta_box( 'slugdiv', 'publication', 'core' );
	}

	public static function doc_desc_box() {
		global $post;
		
		$desc = get_post_meta( $post->ID, 'wpa_doc_desc', true );
		
		wp_nonce_field( plugin_basename(__FILE__), 'wpa_nonce' );
		echo '<p>Provide a short description of the publication:</p>';
		echo '<textarea id="wpa_doc_desc" name="wpa_doc_desc" rows="5" style="width:100%">' . $desc . '</textarea>';
	}
	
	public static function doc_uri_box() {
		global $post;
		
		$uri = get_post_meta( $post->ID, 'wpa_upload_doc', true );
		echo "<style type=\"text/css\">
#edit-slug-box {
	display: none;
	}
</style>";
		echo '<p>Please provide the absolute url of the file (including the <code>http://</code>):</p>';
		echo '<input type="text" id="wpa_upload_doc" name="wpa_upload_doc" value="' . $uri . '" size="25" style="width:85%" />';
		echo '<input class="button" id="upload_doc_button" type="button" value="Upload Publication" alt="Upload Publication" />';
		echo "<script type=\"text/javascript\">
jQuery(document).ready(function() {
	jQuery('#upload_doc_button').on('click', function() {
		window.send_to_editor = function(html) {
			var docurl = jQuery(html).attr('href');
			jQuery('#wpa_upload_doc').val(docurl);
			tb_remove();
		};

		formfield = jQuery('#wpa_upload_doc').attr('name');		
		tb_show('Upload Publication', 'media-upload.php?TB_iframe=1&width=640&height=263');
		return false;
	});
});
</script>\r\n";
	}

	public static function doc_thumb_box() {
		global $post;

		$thumb = get_post_meta( $post->ID, 'wpa-upload_image', true );

		echo 'Enter an URL or upload an image for the thumb.';
		echo '<br />';
		echo '<br />';
		echo '<label for="wpa-upload_image">';
		echo '<input id="wpa-upload_image" type="text" size="36" name="wpa-upload_image" value=" ' . $thumb . '" />';
		echo '<input id="wpa-upload_image_button" type="button" value="Upload Thumb" />';

		echo "<script type=\"text/javascript\">
	jQuery(document).ready(function() {
		jQuery('#wpa-upload_image_button').on('click', function() {
			window.send_to_editor = function(html) {
				var imgurl = jQuery('img',html).attr('src');
				jQuery('#wpa-upload_image').val(imgurl);
				tb_remove();
			}

			formfield = jQuery('#upload_image').attr('name');
			tb_show('Upload Thumbnail Image', 'media-upload.php?type=image&amp;TB_iframe=true');
			return false;
		});
	});
</script>\r\n";
	}

	public static function save_meta( $post_id ) {
		$post = get_post( $post_id );
		if( $post->post_type != 'publication' ) {
			return $post_id;
		}
	
		if( !isset($_POST['wpa_nonce']) || !wp_verify_nonce( $_POST['wpa_nonce'], plugin_basename(__FILE__) )) {
			return $post_id;
		}
		
		if( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) {
			return $post_id;
		}
		
		$description = $_POST['wpa_doc_desc'];
		$uri = $_POST['wpa_upload_doc'];
		$thumbnail = $_POST['wpa-upload_image'];
			
		update_post_meta( $post_id, 'wpa_doc_desc', $description );
		update_post_meta( $post_id, 'wpa_upload_doc', $uri );
		update_post_meta( $post_id, 'wpa-upload_image', $thumbnail );
		
		return $post_id;
	}

	public static function shortcode_handler( $atts ) {
		extract( shortcode_atts( array(
				'categories' => '',
				'author' => ''
				), $atts ) );
		
		global $post;

		$pubs_per_page = apply_filters( 'wpa-pubs_per_page', 10 );

		if(isset($_GET['wpa-paged'])) {
			$paged = (int)$_GET['wpa-paged'];
			$offset = $pubs_per_page * ($paged - 1);
		} else {
			$paged = 1;
			$offset = 0;
		}

		// Get publications
		$args = array(
			'offset' => $offset,
			'numberposts' => $pubs_per_page,
			'post_type' => 'publication',
			'orderby' => 'post_date',
			'order' => 'DESC',
			'post_status' => 'publish'
		);

		if ( '' != $categories ) {
			// Create an array of category IDs based on the categories fed in.
			$catFilter = array();
			$catList = explode(',', $categories);
			foreach( $catList as $catName ){
				$id = get_cat_id( trim( $catName ) );
				if( 0 !== $id )
					$catFilter[] = $id;
			}
			// if no categories matched categories in the database, report failure
			if ( empty( $catFilter ) ) {
				$error_msg = "<div class='publication-archive'><p>". __(' Sorry, but the categories you passed to the wp-publication-archive shortcode do not match any publication categories.' ) . "</p><p>" . __( 'You passed: ', 'wp-publication-archive' ) . "<code>$categories</code></p></div>";
				return $error_msg;
			}
			$args['category'] = implode( ',', $catFilter );
		}

		if('' != $author) {
			$args['tax_query'] = array(array(
				'taxonomy' => 'publication-author',
				'field' => 'slug',
				'terms' => $author
			));
		}

		$publications = get_posts( $args );

		$args['numberposts'] = -1;
		$total_pubs = count( get_posts( $args ) );

		// Report if there are no publications matching filters
		if ( 0 == $total_pubs ) {
			$error_msg = "<p>" . __( 'There are no publications to display' );
			if ( '' != $author ) 
				$error_msg .= __( ' by ' ) . "$author";
			if ( '' != $categories ) {
				// There is probably a better way to do this…
				$error_msg .= __( ' categorized ' );
				$catList = explode ( ',', $categories );
				$catNum = count( $catList );
				$x = 3; // number of terms necessary for grammar to require commas after each term
				if ( $catNum > 2 ) $x = 1;
				 for ( $i = 0; $i < $catNum; $i++ ) {
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
		
		// Create publication list
		$list = '<div class="publication-archive">';
		foreach( $publications as $publication ) {
			$pub = new WP_Publication_Archive_Item( $publication->ID, $publication->post_title, $publication->post_date );

			$list .= '<div class="single-publication">';
				$list .= $pub->get_the_thumbnail();
				$list .= $pub->get_the_title();
				$list .= $pub->get_the_authors();
				$list .= $pub->get_the_uri();
				$list .= $pub->get_the_summary();
				$list .= $pub->get_the_keywords();
				$list .= $pub->get_the_categories();
			$list .= "</div>";
		}
		
		$list .= '</div>';

		if( $total_pubs > $pubs_per_page ) {
			$list .= '<div id="navigation">';

			$next = add_query_arg( 'wpa-paged', $paged + 1, get_permalink($post->ID) );
			$prev = add_query_arg( 'wpa-paged', $paged - 1, get_permalink($post->ID) );

			if($offset > 0) {
				$list .= '<div class="nav-previous">';
				$list .= '<a href="' . $prev . '">';
				$list .= '&laquo; Previous';
				$list .= '</a>';
				$list .= '</div>';
			}
			
			if($offset + $pubs_per_page < $total_pubs ) {
				$list .= '<div class="nav-next">';
				$list .= '<a href="' . $next . '">';
				$list .= 'Next &raquo;';
				$list .= '</a>';
				$list .= '</div>';
			}
			
			$list .= '</div>';
		}

		return $list;
	}

	public static function query_vars( $public_vars ) {
		$public_vars[] = 'wpa-paged';
		return $public_vars;
	}

	public static function publication_link( $permalink, $post ) {
		if( 'publication' != $post->post_type )
			return $permalink;

		$pub = new WP_Publication_Archive_Item( $post->ID, $post->post_title, $post->post_date );
		return $pub->get_the_link();
	}

	public static function the_content( $content ) {
		global $post;
		if( 'publication' != $post->post_type )
			return $content;

		$pub = new WP_Publication_Archive_Item( $post->ID, $post->post_title, $post->post_date );
		return $pub->summary;
	}

	public static function the_title( $title, $id ) {
		$post = &get_post( $id );
		if( 'publication' != $post->post_type )
			return $title;

		return $title . " (Download Publication)";
	}
	
	/**
	 * Utility function to return a WP_Query object with Publication posts
	 */
	 
	public static function query_publications( $args ) {
		$defaults = array(
			'posts_per_page' => -1,
			'order' => 'ASC',
			'orderby' => 'menu_order'
		);
		$query_args = wp_parse_args( $args, $defaults );
		$query_args['post_type'] = 'publication';
	
		$results = new WP_Query( $query_args );
	
		return $results;		
	}

	// Register widget
	public static function register_widget() {
		register_widget( 'publication_archive_widget' );
	}


}
?>
