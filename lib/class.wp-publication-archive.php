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
	
	static function get_image( $doctype ) {
		$path = WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__));
		if( ! isset( self::$mimetypes[$doctype] ) ) {
			return $path . 'icons/' . 'unknown.png';
		}
		
		return $path . 'icons/' . self::$mimetypes[$doctype] . '.png';
	}

	function WP_Publication_Archive() {
		add_action( 'init', array( &$this, 'setup' ) );
		
		add_action( 'save_post', array( &$this, 'save_meta' ) );
		
		if( is_admin() ) {
			wp_enqueue_script( 'media-upload' );
			wp_enqueue_script( 'thickbox' );
			wp_enqueue_style( 'thickbox' );
		} else {
			wp_enqueue_style( 'wp-publication-archive-frontend', plugins_url('/front-end.css', __FILE__), '', '2.0', 'all' );
		}
		
		add_shortcode( 'wp-publication-archive', array( &$this, 'shortcode_handler' ) );
	}
	
	function setup() {
		register_post_type( 'publication',
			array(
				'labels' => array(
					'name' => __( 'Publications' ),
					'singular_name' => __( 'Publication' ),
					'add_new_item' => __( 'Add New Publication' ),
					'edit_item' => __( 'Edit Publication' ),
					'new_item' => __( 'New Publication' ),
					'view_item' => __( 'View Publication' ),
					'search_items' => __( 'Search Publications' ),
					'not_found' => __( 'No publications found' ),
					'not_found_in_trash' => __( 'No publications found in trash' )
				),
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
				'exclude_from_search' => true,
				'register_meta_box_cb' => array( &$this, 'pub_meta_boxes' ),
				'rewrite' => false,
				'can_export' => true,
				'menu_icon' => WP_PLUGIN_URL . '/' . str_replace( basename( __FILE__ ), '', plugin_basename( __FILE__ ) ) . 'cabinet.png'
			)
		);
	
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
	
	function pub_meta_boxes() {
		add_meta_box( 'publication_desc', 'Summary', array( &$this, 'doc_desc_box' ), 'publication', 'normal', 'high', '' );	
		add_meta_box( 'publication_uri', 'Publication', array( &$this, 'doc_uri_box' ), 'publication', 'normal', 'high', '' );
		
		remove_meta_box( 'slugdiv', 'publication', 'core' );
	}
	
	function doc_desc_box() {
		global $post;
		
		$desc = get_post_meta( $post->ID, 'wpa_doc_desc', true );
		
		wp_nonce_field( plugin_basename(__FILE__), 'wpa_nonce' );
		echo '<p>Provide a short description of the publication:</p>';
		echo '<textarea id="wpa_doc_desc" name="wpa_doc_desc" rows="5" style="width:100%">' . $desc . '</textarea>';
	}
	
	function doc_uri_box() {
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
	
	function save_meta( $post_id ) {
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
			
		update_post_meta( $post_id, 'wpa_doc_desc', $description );
		update_post_meta( $post_id, 'wpa_upload_doc', $uri );
		
		return $post_id;
	}
	
	public static function shortcode_handler() {
		global $post;
		
		require_once('class.mimetype.php');
		$mime = new mimetype();
		$downloadroot = WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__)) . 'openfile.php?file=';
		
		if(isset($_GET['paged'])) {
			$paged = (int)$_GET['paged'];
			$offset = 10 * ($paged - 1);
		} else {
			$paged = 1;
			$offset = 0;
		}
		
		$list = '<div class="publication-archive">';
	
		// Get publications
		$args = array(
			'offset' => $offset,
			'numberposts' => 10,
			'post_type' => 'publication',
			'orderby' => 'post_date',
			'order' => 'DESC',
			'post_status' => 'publish'
		);
		
		$publications = get_posts( $args );
		
		// Create publication list
		foreach( $publications as $publication ) {
			$summary = get_post_meta( $publication->ID, 'wpa_doc_desc', true );
			$uri = get_post_meta ( $publication->ID, 'wpa_upload_doc', true );
			
			$tags = wp_get_post_tags( $publication->ID );
			if( count( $tags ) > 0 ) {
				$keywords = '';
				foreach( $tags as $tag ) {
					if($keywords != '') $keywords .= ', ';
					$keywords .= $tag->name;
				}
			} else {
				$keywords = false;
			}
			
			$cats = get_the_category ( $publication->ID );
			if( count( $cats ) > 0 ) {
				$categories = '';
				foreach( $cats as $cat ) {
					if($categories != '') $categories .= ', ';
					$categories .= $cat->name;
				}
			} else {
				$categories = false;
			}
			
			$auths = wp_get_post_terms( $publication->ID, 'publication-author' );
			if( count( $auths ) > 0 ) {
				$authors = '';
				foreach( $auths as $author ) {
					if($authors != '') $authors .= ', ';
					$authors .= $author->name;
				}
			} else {
				$authors = false;
			}
			
			$list .= '<div class="single-publication">';
		
			$list .= '<div class="publication_title">';
			$list .= $publication->post_title;
			$list .= '</div>';
			
			$list .= '<div class="publication_authors">';
			if( $authors ) {
				$list .= '<span class="author-list">' . $authors . '</span>';
			}
			$list .= '<span class="date">(' . date( 'F j, Y', strtotime( $publication->post_date ) ) . ')</span>';
			$list .= '</div>';
			
			if( $uri != 'http://' && $uri != '' ) {
				$list .= '<div class="publication_download">';
				$list .= '<span class="title">Download: </span>';
				$list .= '<span class="description"><a href="' . $downloadroot . $uri . '">';
				$list .= '<img height="16" width="16" alt="download" src="' . WP_Publication_Archive::get_image( $mime->getType( $uri ) ) . '" />Download</a>';
				$list .= '</span>';
				$list .= '</div>';
			}
			
			if( $summary != '' ) {
				$list .= '<div class="publication_summary">';
				$list .= '<span class="title">Summary: </span>';
				$list .= '<span class="description">' . $summary . '</span>';
				$list .= '</div>';
			}
			
			if( $keywords ) {
				$list .= '<div class="publication_keywords">';
				$list .= '<span class="title">Keywords: </span>';
				$list .= '<span class="description">' . $keywords . '</span>';
				$list .= '</div>';
			}
			
			if( $categories ) {
				$list .= '<div class="publication_categories">';
				$list .= '<span class="title">Categories: </span>';
				$list .= '<span class="description">' . $categories . '</span>';
				$list .= '</div>';
			}
			
			$list .= "</div>";
		}
		
		$list .= '</div>';
		
		if( wp_count_posts( 'publication' )->publish > 10 ) {
			$list .= '<div id="navigation">';
			
			if($offset > 0) {
				$list .= '<div class="nav-previous">';
				$list .= '<a href="' . get_permalink($post->ID) . '&paged=' . ($paged - 1) . '">';
				$list .= '&laquo; Previous';
				$list .= '</a>';
				$list .= '</div>';
			}
			
			if($offset + 10 < wp_count_posts( 'publication' )->publish ) {
				$list .= '<div class="nav-next">';
				$list .= '<a href="' . get_permalink($post->ID) . '&paged=' . ($paged + 1) . '">';
				$list .= 'Next &raquo;';
				$list .= '</a>';
				$list .= '</div>';
			}
			
			$list .= '</div>';
		}
		
		return $list;
	}
}