<?php
/**
 * Implements SPEC.md §8 Phase 0 item 4: the 3.0.1 [wp-publication-archive]
 * shortcode. Closes D15: reads shortcode_atts() and the container array
 * explicitly, with no call that dumps an array into local variables
 * (Decisions).
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA;

final class Shortcode {

	private Templates $templates;

	public function __construct( Templates $templates ) {
		$this->templates = $templates;
	}

	public function templates(): Templates {
		return $this->templates;
	}

	/**
	 * 3.0.1 WP_Publication_Archive::shortcode_handler(). Hooked as the
	 * Keys::SHORTCODE callback.
	 *
	 * @param array<string, mixed>|string $atts
	 *
	 * @return string
	 */
	public function render( $atts ) {
		$parsed = shortcode_atts(
			array(
				'categories' => '',
				'author'     => '',
				'limit'      => Keys::DEFAULT_LIST_LIMIT,
				'showas'     => 'list',
			),
			$atts
		);

		$categories = (string) $parsed['categories'];
		$author     = (string) $parsed['author'];
		$limit      = $parsed['limit'];
		$showas     = (string) $parsed['showas'];

		$limit = Hooks::pubs_per_page( $limit ); // Ugly, deprecated filter.
		$limit = Hooks::list_limit( $limit );

		$paged_qv = absint( get_query_var( Keys::QV_PAGED ) );

		if ( $paged_qv > 0 ) {
			$paged  = $paged_qv;
			$offset = $limit * ( $paged - 1 );
		} else {
			$paged  = 1;
			$offset = 0;
		}

		// Get publications
		$args = array(
			'offset'      => $offset,
			'numberposts' => $limit,
			'post_type'   => Keys::POST_TYPE,
			'orderby'     => 'post_date',
			'order'       => 'DESC',
			'post_status' => 'publish',
		);

		if ( '' !== $categories ) {
			// Create an array of category IDs based on the categories fed in.
			$cat_filter = array();
			$cat_list   = explode( ',', $categories );
			foreach ( $cat_list as $cat_name ) {
				$id = get_cat_id( trim( $cat_name ) );
				if ( 0 !== $id ) {
					$cat_filter[] = $id;
				}
			}
			// if no categories matched categories in the database, report failure
			if ( empty( $cat_filter ) ) {
				return "<div class='publication-archive'><p>" . esc_html__( ' Sorry, but the categories you passed to the wp-publication-archive shortcode do not match any publication categories.', 'wp-publication-archive' ) . '</p><p>' . esc_html__( 'You passed: ', 'wp-publication-archive' ) . '<code>' . esc_html( $categories ) . '</code></p></div>';
			}
			$args['category'] = implode( ',', $cat_filter );
		}

		if ( '' !== $author ) {
			$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- reason: 3.0.1 behaviour, author filtering by taxonomy term slug.
				array(
					'taxonomy' => Keys::TAX_AUTHOR,
					'field'    => 'slug',
					'terms'    => $author,
				),
			);
		}

		$publications = get_posts( $args );

		$args['numberposts'] = -1; // phpcs:ignore WordPressVIPMinimum.Performance.NoPaging.posts_per_page_numberposts -- reason: 3.0.1 behaviour, counting the total matching publications for pagination.
		$total_pubs           = count( get_posts( $args ) );

		// Report if there are no publications matching filters
		if ( 0 === $total_pubs ) {
			$error_msg = '<p>' . esc_html__( 'There are no publications to display', 'wp-publication-archive' );
			if ( '' !== $author ) {
				$error_msg .= esc_html__( ' by ', 'wp-publication-archive' ) . esc_html( $author );
			}
			if ( '' !== $categories ) {
				// There is probably a better way to do this
				$error_msg .= esc_html__( ' categorized ', 'wp-publication-archive' );
				$cat_list = explode( ',', $categories );
				$cat_num  = count( $cat_list );
				$x        = 3; // number of terms necessary for grammar to require commas after each term
				if ( $cat_num > 2 ) {
					$x = 1;
				}
				for ( $i = 0; $i < $cat_num; $i++ ) {
					if ( $cat_num > 1 && $i === ( $cat_num - 1 ) ) {
						$error_msg .= 'or ';
					}
					$error_msg .= esc_html( $cat_list[ $i ] );
					if ( $i < ( $cat_num - $x ) ) {
						$error_msg .= ', ';
					} elseif ( $i < ( $cat_num - 1 ) ) {
						$error_msg .= ' ';
					}
				}
			}
			$error_msg .= '.</p>';

			return $error_msg;
		}

		switch ( $showas ) {
			case 'dropdown':
				$template_name = Hooks::dropdown_template( Keys::TEMPLATE_DROPDOWN );
				break;
			case 'list':
			default:
				$template_name = Hooks::list_template( Keys::TEMPLATE_LIST );
		}

		$path = $this->templates->locate( $template_name );

		// Get a global container variable and populate it with our data.
		global $wppa_container;
		$wppa_container = array(
			'publications' => $publications,
			'total_pubs'   => $total_pubs,
			'limit'        => $limit,
			'offset'       => $offset,
			'paged'        => $paged,
			'post'         => get_post(),
		);
		$wppa_container = Hooks::list_container( $wppa_container );

		// Start a buffer to capture the HTML output of the shortcode.
		ob_start();

		include $path;

		$output = ob_get_contents();

		ob_end_clean();

		// Because globals are evil, clean up afterwards.
		unset( $wppa_container );

		return $output;
	}
}
