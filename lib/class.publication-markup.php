<?php
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

	public function __construct() {
		@list($this->ID, $this->title, $this->date) = func_get_args();

		$this->summary = get_post_meta( $this->ID, 'wpa_doc_desc', true );
		$this->uri = get_post_meta( $this->ID, 'wpa_upload_doc', true );
		$this->upload_image = get_post_meta( $this->ID, 'wpa-upload_image', true );

		$this->uri = str_replace('http://', 'http|', $this->uri);
		$this->uri = str_replace('https://', 'https|', $this->uri);

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

	public function get_the_title() {
		$before = '<div class="publication_title">';
		$after = '</div>';

		$title = apply_filters( 'wpa-title', $this->title, $this->ID );

		return $before . $title . $after;
	}
	public function the_title() {
		echo $this->get_the_title();
	}

	public function get_the_thumbnail() {
		$before = '<div class="publication_thumbnail">';
		$after = '</div>';

		$thumb = apply_filters( 'wpa-upload_image', $this->upload_image, $this->ID );

		if ( '' ==$thumb )
			return '';

		return $before . '<img src="' . $thumb . '" />' . $after;
	}
	public function the_thumbnail() {
		echo $this->get_the_thumbnail();
	}

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
	public function the_authors() {
		echo $this->get_the_authors();
	}

	public function get_the_link() {
		$downloadroot = WP_PUB_ARCH_INC_URL . '/openfile.php?file=';

		$uri = apply_filters( 'wpa-uri', $this->uri, $this->ID );

		if ( 'http|' == $uri || '' == $uri )
			return '';

		return $downloadroot . $uri;
	}

	public function get_the_uri() {
		require_once('class.mimetype.php');
		$mime = new mimetype();

		$before = '<div class="publication_download">';
		$before .= '<span class="title">Download: </span>';
		$before .= '<span class="description"><a href="';

		$uri = $this->get_the_link();

		$after = '">';
		$after .= '<img height="16" width="16" alt="download" src="' . WP_Publication_Archive::get_image( $mime->getType( $uri ) ) . '" />Download</a>';
		$after .= '</span>';
		$after .= '</div>';

		if ( $uri != '' ) {
			return $before . $uri . $after;
		}

		return $uri;
	}
	public function the_uri() {
		echo $this->get_the_uri();
	}

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
	public function the_summary() {
		echo $this->get_the_summary();
	}

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
	public function the_keywords() {
		echo $this->get_the_keywords();
	}

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
	public function the_categories() {
		echo $this->get_the_categories();
	}
}
?>