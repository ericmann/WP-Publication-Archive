<?php
/**
 * Implements SPEC.md §8 Phase 0 item 4 and §6.9: 3.0.1's
 * WP_Publication_Archive_Item, aliased as WP_Publication_Archive_Item by
 * includes/legacy/class-aliases.php. P1-08 closed D2 (output) and D3
 * (front). D7 (P2-08) closes here too: the publication date is formatted
 * through Clock::format() in the site timezone and locale.
 * Not final, same public properties/methods/parameters/defaults as
 * e913681's class.publication-markup.php (formerly under the pre-restructure
 * runtime directory, since removed), phpdoc types only (Decisions).
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA;

class Publication_Item {

	/**
	 * Underlying post object.
	 *
	 * @var object|\WP_Post
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
	 * @var array<int, array{description: string, url: string}>
	 */
	public $alternates;

	/**
	 * @var bool|string
	 */
	public $keywords;

	/**
	 * @var list<string>
	 */
	public $keyword_array = array();

	/**
	 * @var bool|string
	 */
	public $categories;

	/**
	 * @var list<string>
	 */
	public $category_array = array();

	/**
	 * @var bool|string
	 */
	public $authors;

	/**
	 * @var list<string>
	 */
	public $author_array = array();

	/**
	 * Default object constructor
	 *
	 * @param int|object|\WP_Post $post
	 */
	public function __construct( $post ) {
		if ( ! is_object( $post ) ) {
			$post = get_post( $post );
		}

		setup_postdata( $post );

		$this->post = $post;

		$this->ID      = $post->ID;
		$this->title   = $post->post_title;
		$this->date    = $post->post_date;
		$this->content = get_the_content();
		$this->summary = get_the_excerpt();

		$this->upload_image = get_post_meta( $this->ID, Keys::META_IMAGE, true );
		$this->uri           = get_post_meta( $this->ID, Keys::META_DOC, true );
		$this->filename      = basename( $this->uri );

		// Filter legacy URLs to strip out bad pipes
		$this->uri = str_replace( 'http|', 'http://', $this->uri );
		$this->uri = str_replace( 'https|', 'https://', $this->uri );

		// Build the keywords string
		$tags = wp_get_post_tags( $this->ID );
		if ( count( $tags ) > 0 ) {
			$this->keyword_array = wp_list_pluck( $tags, 'name' );
			$this->keywords       = implode( ', ', $this->keyword_array );
		} else {
			$this->keywords = false;
		}

		// Build out the category string
		$cats = get_the_category( $this->ID );
		if ( count( $cats ) > 0 ) {
			$this->category_array = wp_list_pluck( $cats, 'name' );
			$this->categories      = implode( ', ', $this->category_array );
		} else {
			$this->categories = false;
		}

		// Build out the author string
		$auths = wp_get_post_terms( $this->ID, Keys::TAX_AUTHOR );
		if ( count( $auths ) > 0 ) {
			$this->author_array = wp_list_pluck( $auths, 'name' );
			$this->authors       = implode( ', ', $this->author_array );
		} else {
			$this->authors = false;
		}

		// Build out alternates array
		$this->alternates = get_post_meta( $this->ID, Keys::META_ALTERNATES );

		wp_reset_postdata();
	}

	/**
	 * Get markup for the publication title.
	 *
	 * @param string $before
	 * @param string $after
	 *
	 * @return string
	 */
	public function get_the_title( $before = '<div class="publication_title">', $after = '</div>' ) {
		$title = '<a href="' . esc_url( (string) get_permalink( $this->ID ) ) . '">';
		$title .= esc_html( (string) Hooks::item_title( $this->title, $this->ID ) );
		$title .= '</a>';

		return $before . $title . $after;
	}

	/**
	 * Echo the markup for the publication title.
	 *
	 * @see Publication_Item::get_the_title()
	 *
	 * @return void
	 */
	public function the_title() {
		echo wp_kses_post( $this->get_the_title() );
	}

	/**
	 * Get markup for the publication thumbnail image.
	 *
	 * @param string $before
	 * @param string $after
	 *
	 * @return string
	 */
	public function get_the_thumbnail( $before = '<div class="publication_thumbnail">', $after = '</div>' ) {
		$thumb = (string) Hooks::item_upload_image( $this->upload_image, $this->ID );

		if ( '' == trim( $thumb ) ) {
			return '';
		}

		$thumb = Plugin::instance()->url_policy()->normalise( $thumb );
		$thumb = Plugin::instance()->dam_bridge()->display_url( $thumb );

		// D18: display_url() may return the DAM's data: URI placeholder for
		// a withheld image; esc_url()'s default protocol allowlist doesn't
		// include 'data', so it is added explicitly here.
		$protocols = array_merge( wp_allowed_protocols(), array( 'data' ) );

		return $before . '<img src="' . esc_url( $thumb, $protocols ) . '" />' . $after;
	}

	/**
	 * Echo the markup for the publication thumbnail.
	 *
	 * @see Publication_Item::get_the_thumbnail()
	 *
	 * @return void
	 */
	public function the_thumbnail() {
		// D18: display_url() may return the DAM's data: URI placeholder for
		// a withheld image; wp_kses_post()'s protocol allowlist doesn't
		// include 'data', so it is added explicitly here.
		$protocols = array_merge( wp_allowed_protocols(), array( 'data' ) );

		echo wp_kses( $this->get_the_thumbnail(), 'post', $protocols );
	}

	/**
	 * Get a list of authors for the publication. Also gets the date bound
	 * to the publication object. D7: formatted through Clock::format() in
	 * the site timezone and locale, not the wall-clock formatting 3.0.1
	 * used.
	 *
	 * @param string $before
	 * @param string $after
	 *
	 * @return string
	 */
	public function get_the_authors( $before = '<div class="publication_authors">', $after = '</div>' ) {
		$authors = Hooks::item_authors( $this->authors, $this->ID );

		$list = '';

		if ( $authors ) {
			$list = '<span class="author-list">' . $authors . '</span>';
		}

		$formatted = Plugin::instance()->clock()->format( 'F j, Y', (int) get_post_time( 'U', true, $this->post ) );
		$date      = '<span class="date">(' . esc_html( $formatted ) . ')</span>';

		return $before . $list . $date . $after;
	}

	/**
	 * Echos the markup for the authors of the publication.
	 *
	 * @see Publication_Item::get_the_authors()
	 *
	 * @return void
	 */
	public function the_authors() {
		echo wp_kses_post( $this->get_the_authors() );
	}

	/**
	 * Get the file open link for the current publication.
	 *
	 * @return string Download link.
	 */
	public function get_the_link() {
		return Plugin::instance()->rewrites()->open_link( $this->ID );
	}

	/**
	 * Get the markup for the publication download links.
	 *
	 * @return string
	 */
	public function get_the_uri() {
		$uri = $this->get_the_link();
		if ( '' == trim( $uri ) ) {
			return '';
		}

		$output  = '<div class="publication_download">';
		$output .= '<span class="title">' . esc_html( $this->filename ) . ' </span>';
		$output .= '<span class="description">';
		$output .= '<img height="16" width="16" alt="download" src="' . esc_url( Plugin::instance()->icons()->url_for( Plugin::instance()->icons()->mime_for( $this->uri ) ) ) . '" /> ';
		$output .= '<a ';
		if ( Hooks::open_in_blank( false ) ) {
			$output .= 'target="_blank" ';
		}
		$output .= 'href="' . esc_url( Plugin::instance()->rewrites()->open_link( $this->ID ) ) . '">';
		$output .= esc_html__( 'View', 'wp-publication-archive' ) . '</a> | ';
		$output .= '<a href="' . esc_url( Plugin::instance()->rewrites()->download_link( $this->ID ) ) . '">';
		$output .= esc_html__( 'Download', 'wp-publication-archive' ) . '</a>';
		$output .= '</span>';
		$output .= '</div>';

		return $output;
	}

	/**
	 * Echos the markup for publication download links.
	 *
	 * @see Publication_Item::get_the_uri()
	 *
	 * @return void
	 */
	public function the_uri() {
		echo wp_kses_post( $this->get_the_uri() );
	}

	/**
	 * Gets the markup for the publication summary.
	 *
	 * @return string|null
	 */
	public function get_the_summary() {
		$before  = '<div class="publication_summary">';
		$before .= '<span class="title">Summary: </span>';
		$before .= '<span class="description">';

		$after = '</span></div>';

		$summary = Hooks::item_summary( $this->summary, $this->ID );

		if ( $summary != '' ) {
			return $before . $summary . $after;
		}

		return null;
	}

	/**
	 * Echo the markup for the publication summary.
	 *
	 * @see Publication_Item::get_the_summary()
	 *
	 * @return void
	 */
	public function the_summary() {
		echo wp_kses_post( (string) $this->get_the_summary() );
	}

	/**
	 * Get the markup for the publication keyword list.
	 *
	 * @return string|null
	 */
	public function get_the_keywords() {
		$before  = '<div class="publication_keywords">';
		$before .= '<span class="title">Keywords: </span>';
		$before .= '<span class="description">';

		$after = '</span></div>';

		$keywords = Hooks::item_keywords( $this->keywords, $this->ID );

		if ( $keywords != '' ) {
			return $before . $keywords . $after;
		}

		return null;
	}

	/**
	 * Echo the publication keyword list.
	 *
	 * @see Publication_Item::get_the_keywords()
	 *
	 * @return void
	 */
	public function the_keywords() {
		echo wp_kses_post( (string) $this->get_the_keywords() );
	}

	/**
	 * Get the markup for the publication category list.
	 *
	 * @return string|null
	 */
	public function get_the_categories() {
		$before  = '<div class="publication_categories">';
		$before .= '<span class="title">Categories: </span>';
		$before .= '<span class="description">';

		$after = '</span></div>';

		$categories = Hooks::item_categories( $this->categories, $this->ID );

		if ( $categories != '' ) {
			return $before . $categories . $after;
		}

		return null;
	}

	/**
	 * Echo the publication category list.
	 *
	 * @see Publication_Item::get_the_categories()
	 *
	 * @return void
	 */
	public function the_categories() {
		echo wp_kses_post( (string) $this->get_the_categories() );
	}

	/**
	 * List out the downloads associated with this publication. D2 closes
	 * here: alternate descriptions (still stored unsanitized, D2 save) are
	 * escaped at the point of echo.
	 *
	 * @return void
	 */
	public function list_downloads() {
		if ( count( $this->alternates ) == 0 ) {
			return;
		}

		echo '<span class="title">' . esc_html__( 'Other Files:', 'wp-publication-archive' ) . ' </span>';
		echo '<ul>';
		foreach ( $this->alternates as $alt ) {
			echo '<li>';
			echo '<strong>' . esc_html( $alt['description'] ) . '</strong> &mdash; ';
			echo '<a href="' . esc_url( Plugin::instance()->rewrites()->alternate_open_link( $this->ID, $alt['description'] ) ) . '">' . esc_html__( 'View', 'wp-publication-archive' ) . '</a> | ';
			echo '<a href="' . esc_url( Plugin::instance()->rewrites()->alternate_download_link( $this->ID, $alt['description'] ) ) . '">' . esc_html__( 'Download', 'wp-publication-archive' ) . '</a>';
			echo '</li>';
		}
		echo '</ul>';
	}
}
