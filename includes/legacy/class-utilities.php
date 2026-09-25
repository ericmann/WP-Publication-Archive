<?php
/**
 * Implements SPEC.md §8 Phase 0 item 4: the 3.0.1
 * WP_Publication_Archive_Utilities delegate singleton. Not final; 3.0.1 kept
 * this class non-final and instantiable through get_instance()/
 * create_instance(). Every member delegates through \WPPA\Plugin::instance().
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA\Legacy;

class Utilities {

	/**
	 * @var Utilities|false
	 */
	protected static $instance = false;

	/**
	 * Hidden constructor. Adds no hooks (Plugin registers everything, P3).
	 */
	protected function __construct() {
	}

	/**
	 * @return Utilities|false
	 */
	public static function get_instance() {
		return self::$instance;
	}

	/**
	 * @throws \Exception
	 *
	 * @return void
	 */
	public static function create_instance() {
		if ( is_a( self::$instance, self::class ) ) {
			throw new \Exception( __( 'Utilities object already initialized', 'wp-publication-archive' ) );
		}

		self::$instance = new self();
	}

	/**
	 * @return void
	 */
	public function register_widgets() {
		\WPPA\Plugin::instance()->register_widgets();
	}

	/**
	 * @param string|array<string, mixed> $args
	 *
	 * @return string
	 */
	public function dropdown_categories( $args = '' ) {
		return \WPPA\Plugin::instance()->categories()->dropdown_categories( $args );
	}

	/**
	 * @param string|array<string, mixed> $args
	 *
	 * @return string|false|void
	 */
	public function list_categories( $args = '' ) {
		return \WPPA\Plugin::instance()->categories()->list_categories( $args );
	}

	/**
	 * @param string|array<int, string> $taxonomies
	 * @param array<string, mixed>      $args
	 *
	 * @return array<int, mixed>|\WP_Error
	 */
	public function get_terms( $taxonomies, $args = array() ) {
		return \WPPA\Plugin::instance()->categories()->get_terms( $taxonomies, $args );
	}

	/**
	 * @param array<string, string>    $pieces
	 * @param string|array<int,string> $tax
	 * @param array<string, mixed>     $args
	 *
	 * @return array<string, string>
	 */
	public function filter_terms_by_cpt( $pieces, $tax, $args ) {
		return \WPPA\Plugin::instance()->categories()->filter_terms_by_cpt( $pieces, $tax, $args );
	}

	/**
	 * @param string $termlink
	 * @param mixed  $term
	 * @param mixed  $taxonomy
	 *
	 * @return string
	 */
	public function filter_category_link( $termlink, $term, $taxonomy ) {
		return \WPPA\Plugin::instance()->categories()->filter_category_link( $termlink, $term, $taxonomy );
	}

	/**
	 * @param string $template
	 *
	 * @return string
	 */
	public function single_publication( $template ) {
		return \WPPA\Plugin::instance()->templates()->single_template( $template );
	}

	/**
	 * @param string $template
	 *
	 * @return string
	 */
	public function publication_archives( $template ) {
		return \WPPA\Plugin::instance()->templates()->archive_template( $template );
	}

	/**
	 * @param string $template_name
	 *
	 * @return string|false
	 */
	protected function find_template( $template_name ) {
		return \WPPA\Plugin::instance()->templates()->find( $template_name );
	}
}
