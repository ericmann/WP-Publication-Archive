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
			'name'                  => __( 'Free Projects' ),
			'singular_name'         => __( 'Free Project' ),
			'add_new_item'          => __( 'Add Free Project' ),
			'edit_item'             => __( 'Edit Projects' ),
			'new_item'              => __( 'New Project' ),
			'view_item'             => __( 'View Project' ),
			'search_items'          => __( 'Search Free Projects' ),
			'not_found'             => __( 'No free projects found' ),
			'not_found_in_trash'    => __( 'No free projects found in trash' )
		);

		register_post_type( 'publication',
			array(
				'labels' => $labels,
				'capability_type' => 'post',
				'public' => true,
				'menu_position' => 150,
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
		add_meta_box( 'publication_thumb', 'Thumb', array( 'WP_Publication_Archive', 'doc_thumb_box' ), 'publication', 'normal', 'high', '');
		
		remove_meta_box( 'slugdiv', 'publication', 'core' );
	}

	public static function doc_desc_box() {
		global $post;
		
		$desc = get_post_meta( $post->ID, 'wpa_doc_desc', true );
		
		wp_nonce_field( plugin_basename(__FILE__), 'wpa_nonce' );
		echo '<p>Provide a short description of the publication:</p>';
		echo '<textarea id="wpa_doc_desc" name="wpa_doc_desc" rows="5" style="width:100%">' . $desc . '</textarea>';
	}
	
	/* A Trip Function */
	public static function doc_thumb_box() {
    global $post;
    
    $thumb = get_post_meta( $post->ID, 'upload_image', true );

    function my_admin_scripts() {
    wp_enqueue_script('media-upload');
    wp_enqueue_script('thickbox');
    wp_enqueue_script('my-upload');
    }

    function my_admin_styles() {
    wp_enqueue_style('thickbox');
    }

    if (isset($_GET['page']) && $_GET['page'] == 'my_plugin_page') {
    add_action('admin_print_scripts', 'my_admin_scripts');
    add_action('admin_print_styles', 'my_admin_styles');
    }

    echo 'Enter an URL or upload an image for the thumb.';
    echo '<br />';
    echo '<br />';
    echo '<label for="upload_image">';
    echo '<input id="upload_image" type="text" size="36" name="upload_image" value=" ' . $thumb . '" />';
    echo '<input id="upload_image_button" type="button" value="Upload Thumb" />';

    
    echo "<script type=\"text/javascript\">
    jQuery(document).ready(function() {

    jQuery('#upload_image_button').click(function() {
     formfield = jQuery('#upload_image').attr('name');
     tb_show('', 'media-upload.php?type=image&amp;TB_iframe=true');
     return false;
    });

    window.send_to_editor = function(html) {
     imgurl = jQuery('img',html).attr('src');
     jQuery('#upload_image').val(imgurl);
     tb_remove();
    }

    });
    </script>\r\n";
    
	}
	
	public static function doc_uri_box() {
		global $post;
		
		$uri = get_post_meta( $post->ID, 'wpa_upload_doc', true );
		echo "<style type=\"text/css\">
#edit-slug-box {
	display: none;
	}
</style>";
		echo '<p>Please provide the abosulte url of the file (including the <code>http://</code>):</p>';
		echo '<input type="text" id="wpa_upload_doc" name="wpa_upload_doc" value="' . $uri . '" size="25" style="width:85%" />';
		echo '<input class="button" id="upload_doc_button" type="button" value="Upload Publication" alt="Upload Publication" />';
		echo "<script type=\"text/javascript\">
jQuery(document).ready(function() {
	jQuery('#upload_doc_button').click(function() {
		formfield = jQuery('#wpa_upload_doc').attr('name');		
		tb_show('Upload Publication', 'media-upload.php?TB_iframe=1&width=640&height=263');
		return false;
	});
	
	window.send_to_editor = function(html) {
		var docurl = jQuery(html).attr('href');
		jQuery('#wpa_upload_doc').val(docurl);
		tb_remove();
	}
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
		$thumb = $_POST['upload_image'];
			
		update_post_meta( $post_id, 'wpa_doc_desc', $description );
		update_post_meta( $post_id, 'wpa_upload_doc', $uri );
		update_post_meta( $post_id, 'upload_image', $thumb);
		
		return $post_id;
	}

	public static function shortcode_handler( $atts ) {
		extract( shortcode_atts( array(
				'categories' => '',
				'author' => ''
				), $atts ) );
		
		global $post;

		// Create an array of category IDs based on the categories fed in.
		$catFilter = array();
		$catList = explode(',', $categories);
		foreach( $catList as $catName ){
			$id = get_cat_id( trim( $catName ) );
			if( 0 !== $id )
				$catFilter[] = $id;
		}

		$pubs_per_page = apply_filters( 'wpa-pubs_per_page', 10 );

		if(isset($_GET['wpa-paged'])) {
			$paged = (int)$_GET['wpa-paged'];
			$offset = $pubs_per_page * ($paged - 1);
		} else {
			$paged = 1;
			$offset = 0;
		}

		$list = '<div class="publication-archive">';

		// Get publications
		$args = array(
			'offset' => $offset,
			'numberposts' => $pubs_per_page,
			'post_type' => 'publication',
			'orderby' => 'post_date',
			'order' => 'DESC',
			'post_status' => 'publish',
			'category__in' => $catFilter
		);

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
		
		// Create publication list
		foreach( $publications as $publication ) {
			$pub = new WP_Publication_Archive_Item( $publication->ID, $publication->post_title, $publication->post_date );

			$list .= '<div class="single-publication">';
			  $list .= $pub->get_the_thumb();
				$list .= $pub->get_the_title();
				$list .= $pub->get_the_uri();
				$list .= $pub->get_the_summary();
				$list .= $pub->get_the_keywords();
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
}
?>