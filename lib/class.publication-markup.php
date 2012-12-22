<?php
/**
 * Wrapper object for publication archive items.
 *
 * @module WP_Publication_Archive
 *
 * @since 2.3
 */

/**
 * This object is used to wrap useful helper functions that relate specifically to publication items.
 *
 * Since a publication item is a traditional WP_Post object, you instantiate this class only when you need its specific functionality.
 * Pass in the ID of the publication, its title, and its date to create a new object.  Other useful information (i.e. summary, authors, thumbnail) will be populated automatically.
 *
 * @since 2.3
 */
class WP_Publication_Archive_Item {
	var $ID;
	var $title;
	var $date;
	var $summary;
	var $uri;
	var $keywords;
	var $categories;
	var $authors;
	var $upload_image;

	/**
	 * Default object constructor
	 *
	 * @var int      ID
	 * @var string   title
	 * @var datetime date
	 */
	public function __construct() {
		@list($this->ID, $this->title, $this->date) = func_get_args();

		$this->summary = get_post_meta( $this->ID, 'wpa_doc_desc', true );
		$this->uri = get_post_meta( $this->ID, 'wpa_upload_doc', true );
		$this->upload_image = get_post_meta( $this->ID, 'wpa-upload_image', true );

		$this->uri = str_replace('http|', 'http://', $this->uri);
		$this->uri = str_replace('https|', 'https://', $this->uri);

		$tags = wp_get_post_tags( $this->ID );
		if ( count( $tags ) > 0 ) {
			$this->keywords = '';
			foreach( $tags as $tag ) {
				if( $this->keywords != '' ) $this->keywords .= ', ';
				$this->keywords .= $tag->name;
			}
		} else {
			$this->keywords = false;
		}

		$cats = get_the_category ( $this->ID );
		if ( count( $cats ) > 0 ) {
			$this->categories = '';
			foreach( $cats as $cat ) {
				if ( $this->categories != '' ) $this->categories .= ', ';
				$this->categories .= $cat->name;
			}
		} else {
			$this->categories = false;
		}

		$auths = wp_get_post_terms( $this->ID, 'publication-author' );
		if( count( $auths ) > 0 ) {
			$this->authors = '';
			foreach( $auths as $author ) {
				if($this->authors != '') $this->authors .= ', ';
				$this->authors .= $author->name;
			}
		} else {
			$this->authors = false;
		}
	}

	/**
	 * Get markup for the publication title.
	 *
	 * @uses apply_filters() Calls 'wpa-title' to modify the publication title.
	 *
	 * @return string
	 */
	public function get_the_title() {
		$before = '<div class="publication_title">';
		$after = '</div>';

		$title = apply_filters( 'wpa-title', $this->title, $this->ID );

		return $before . $title . $after;
	}

	/**
	 * Echo the markup for the publication title.
	 *
	 * @see WP_Publication_Archive_Item::get_the_title()
	 */
	public function the_title() {
		echo $this->get_the_title();
	}

	/**
	 * Get markup for the publication thumbnail image.
	 *
	 * @uses apply_filters() Calls 'wpa-upload_image' to modify the thumbnail URL.
	 *
	 * @return string
	 */
	public function get_the_thumbnail() {
		$before = '<div class="publication_thumbnail">';
		$after = '</div>';

		$thumb = apply_filters( 'wpa-upload_image', $this->upload_image, $this->ID );

		if ( '' == trim( $thumb ) )
			return '';

		return $before . '<img src="' . $thumb . '" />' . $after;
	}

	/**
	 * Echo the markup for the publication thumbnail.
	 *
	 * @see WP_Publication_Archive_Item::get_the_thumbnail()
	 */
	public function the_thumbnail() {
		echo $this->get_the_thumbnail();
	}

	/**
	 * Get a list of authors for the publication.  Also gets the date bound to the publication object.
	 *
	 * @uses apply_filters() Calls 'wpa-authors' to modify the author's list.
	 *
	 * @return string
	 */
	public function get_the_authors() {
		$before = '<div class="publication_authors">';
		$after = '</div>';

		$authors = apply_filters( 'wpa-authors', $this->authors, $this->ID );

		$list = '';

		if( $authors ) {
			$list = '<span class="author-list">' . $authors . '</span>';
		}

		$date = '<span class="date">(' . date( 'F j, Y', strtotime( $this->date ) ) . ')</span>';

		return $before . $list . $date . $after;
	}

	/**
	 * Echos the markup for the authors of the publication.
	 *
	 * @see WP_Publication_Archive_Item::get_the_authors()
	 */
	public function the_authors() {
		echo $this->get_the_authors();
	}

	/**
	 * Get the file open link for the current publication.
	 *
	 * @return string Download link.
	 *
	 * @see WP_Publication_Archive::get_open_link()
	 */
	public function get_the_link() {
		return WP_Publication_Archive::get_open_link( $this->ID );
	}

	/**
	 * Get the markup for the publication download links.
	 *
	 * @see mimetype
	 *
	 * @uses WP_Publication_Archive::get_image()
	 * @uses WP_Publication_Archive::get_open_link()
	 * @uses WP_Publication_Archive::get_download_link()
	 *
	 * @return string
	 */
	public function get_the_uri() {
		$mime = new mimetype();

		$uri = $this->get_the_link();
		if ( '' == trim( $uri ) )
			return '';

		$output = '<div class="publication_download">';
		$output .= '<span class="title">Download: </span>';
		$output .= '<span class="description">';
		$output .= '<img height="16" width="16" alt="download" src="' . WP_Publication_Archive::get_image( $mime->getType( $this->uri ) ) . '" /> ';
		$output .= '<a href="' . WP_Publication_Archive::get_open_link( $this->ID ) . '">';
		$output .= __( 'Open', 'wppa_translate' ) . '</a> | ';
		$output .= '<a href="' . WP_Publication_Archive::get_download_link( $this->ID ) . '">';
		$output .= __( 'Download', 'wppa_translate' ) . '</a>';
		$output .= '</span>';
		$output .= '</div>';

		return $output;
	}

	/**
	 * Echos the markup for publication download links.
	 *
	 * @see WP_Publication_Archive_Item::get_the_uri()
	 */
	public function the_uri() {
		echo $this->get_the_uri();
	}

	/**
	 * Gets the markup for the publication summary.
	 *
	 * @uses apply_filters() Calls 'wpa-summary' to modify the publication summary.
	 *
	 * @return string
	 */
	public function get_the_summary() {
		$before = '<div class="publication_summary">';
		$before .= '<span class="title">Summary: </span>';
		$before .= '<span class="description">';

		$after = '</span></div>';

		$summary = apply_filters( 'wpa-summary', $this->summary, $this->ID );

		if ( $summary != '' ) {
			return $before . $summary . $after;
		}
	}

	/**
	 * Echo the markup for the publication summary.
	 *
	 * @see WP_Publication_Archive_Item::get_the_summary()
	 */
	public function the_summary() {
		echo $this->get_the_summary();
	}

	/**
	 * Get the markup for the publication keyword list.
	 *
	 * @uses apply_filters() Calls 'wpa-keywords' to modify the publication keyword list.
	 *
	 * @return string
	 */
	public function get_the_keywords() {
		$before = '<div class="publication_keywords">';
		$before .= '<span class="title">Keywords: </span>';
		$before .= '<span class="description">';

		$after = '</span></div>';

		$keywords = apply_filters( 'wpa-keywords', $this->keywords, $this->ID );

		if ( $keywords != '' ) {
			return $before . $keywords . $after;
		}
	}

	/**
	 * Echo the publication keyword list.
	 *
	 * @see WP_Publication_Archive_Item::get_the_keywords()
	 */
	public function the_keywords() {
		echo $this->get_the_keywords();
	}

	/**
	 * Get the markup for the publication category list.
	 *
	 * @uses apply_filters() Calls 'wpa-categories' to modify the publication category list.
	 *
	 * @return string
	 */
	public function get_the_categories() {
		$before = '<div class="publication_categories">';
		$before .= '<span class="title">Categories: </span>';
		$before .= '<span class="description">';

		$after = '</span></div>';

		$categories = apply_filters( 'wpa-categories', $this->categories, $this->ID );

		if ( $categories != '' ) {
			return $before . $categories . $after;
		}
	}

	/**
	 * Echo the publication category list.
	 *
	 * @see WP_Publication_Archive_Item::get_the_categories()
	 */
	public function the_categories() {
		echo $this->get_the_categories();
	}
}
?>