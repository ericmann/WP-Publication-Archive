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
	/**
	 * Underlying post object.
	 *
	 * @var object|WP_Post
	 */
	protected $post;

	/**
	 * @var int
	 */
	public $ID;

	/**
	 * @var string
	 */
	public $title;

	/**
	 * @var string
	 */
	public $date;

	/**
	 * @var string
	 */
	public $content;

	/**
	 * @var string
	 */
	public $summary;

	/**
	 * @var string|null
	 */
	public $upload_image;

	/**
	 * @var string
	 */
	public $uri;

	/**
	 * @var string
	 */
	public $filename;

	/**
	 * Alternate file downloads.
	 *
	 * @var array
	 */
	public $alternates;

	/**
	 * @var bool|string
	 */
	public $keywords;

	/**
	 * @var array
	 */
	public $keyword_array = array();

	/**
	 * @var bool|string
	 */
	public $categories;

	/**
	 * @var array
	 */
	public $category_array = array();

	/**
	 * @var bool|string
	 */
	public $authors;

	/**
	 * @var array
	 */
	public $author_array = array();

	/**
	 * Default object constructor
	 *
	 * @param int|object|WP_Post $post
	 */
	public function __construct( $post ) {
		if ( ! is_object( $post ) ) {
			$post = get_post( $post );
		}

		setup_postdata( $post );

		$this->post = $post;

		$this->ID           = $post->ID;
		$this->title        = $post->post_title;
		$this->date         = $post->post_date;
		$this->content      = get_the_content();
		$this->summary      = get_the_excerpt();

		$this->upload_image = get_post_meta( $this->ID, 'wpa-upload_image', true );
		$this->uri          = get_post_meta( $this->ID, 'wpa_upload_doc', true );
		$this->filename     = basename( $this->uri );

		// Filter legacy URLs to strip out bad pipes
		$this->uri = str_replace( 'http|', 'http://', $this->uri );
		$this->uri = str_replace( 'https|', 'https://', $this->uri );

		// Build the keywords string
		$tags = wp_get_post_tags( $this->ID );
		if ( count( $tags ) > 0 ) {
			$this->keyword_array = wp_list_pluck( $tags, 'name' );
			$this->keywords = implode( ', ', $this->keyword_array );
		} else {
			$this->keywords = false;
		}

		// Build out the category string
		$cats = get_the_category( $this->ID );
		if ( count( $cats ) > 0 ) {
			$this->category_array = wp_list_pluck( $cats, 'name' );
			$this->categories = implode( ', ', $this->category_array );
		} else {
			$this->categories = false;
		}

		// Build out the author string
		$auths = wp_get_post_terms( $this->ID, 'publication-author' );
		if ( count( $auths ) > 0 ) {
			$this->author_array = wp_list_pluck( $auths, 'name' );
			$this->authors = implode( ', ', $this->author_array );
		} else {
			$this->authors = false;
		}

		// Build out alternates array
		$this->alternates = get_post_meta( $this->ID, 'wpa-upload_alternates' );

		wp_reset_postdata();
	}

	/**
	 * Get markup for the publication title.
	 *
	 * @uses apply_filters() Calls 'wpa-title' to modify the publication title.
	 *
	 * @param string $before
	 * @param string $after
	 *
	 * @return string
	 */
	public function get_the_title( $before = '<div class="publication_title">', $after = '</div>' ) {
		$title = '<a href="' . get_permalink( $this->ID ) . '">';
		$title .= apply_filters( 'wpa-title', $this->title, $this->ID );
		$title .= '</a>';

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
	 * @param string $before
	 * @param string $after
	 *
	 * @return string
	 */
	public function get_the_thumbnail( $before = '<div class="publication_thumbnail">', $after = '</div>' ) {
		$thumb = apply_filters( 'wpa-upload_image', $this->upload_image, $this->ID );

		if ( '' == trim( $thumb ) ) {
			return '';
		}

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
	 * @param string $before
	 * @param string $after
	 *
	 * @return string
	 */
	public function get_the_authors( $before = '<div class="publication_authors">', $after = '</div>' ) {
		$authors = apply_filters( 'wpa-authors', $this->authors, $this->ID );

		$list = '';

		if ( $authors ) {
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
	 * @see  mimetype
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
		$output .= '<span class="title">' . $this->filename . ' </span>';
		$output .= '<span class="description">';
		$output .= '<img height="16" width="16" alt="download" src="' . WP_Publication_Archive::get_image( $mime->getType( $this->uri ) ) . '" /> ';
		$output .= '<a ';
		if ( apply_filters( 'wp_pubarch_open_in_blank', false ) ) {
			$output .= 'target="_blank" ';
		}
		$output .= 'href="' . WP_Publication_Archive::get_open_link( $this->ID ) . '">';
		$output .= __( 'View', 'wp_pubarch_translate' ) . '</a> | ';
		$output .= '<a href="' . WP_Publication_Archive::get_download_link( $this->ID ) . '">';
		$output .= __( 'Download', 'wp_pubarch_translate' ) . '</a>';
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

	/**
	 * List out the downloads associated with this publication
	 */
	public function list_downloads() {
		if ( count( $this->alternates ) == 0 ) {
			return;
		}

		echo '<span class="title">' . __( 'Other Files:', 'wp_pubarch_translate' ) . ' </span>';
		echo '<ul>';
		foreach( $this->alternates as $alt ) {
			echo '<li>';
			echo '<strong>' . $alt['description'] . '</strong> &mdash; ';
			echo '<a href="' . WP_Publication_Archive::get_alternate_open_link( $this->ID, $alt['description'] ) . '">' . __( 'View', 'wp_pubarch_translate' ) . '</a> | ';
			echo '<a href="' . WP_Publication_Archive::get_alternate_download_link( $this->ID, $alt['description'] ) . '">' . __( 'Download', 'wp_pubarch_translate' ) . '</a>';
			echo '</li>';
		}
		echo '</ul>';
	}
}