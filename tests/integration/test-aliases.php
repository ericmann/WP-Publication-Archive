<?php
/**
 * Implements SPEC.md §8 Phase 0 item 4: class_alias() keeps 3.0.1 class
 * names resolvable.
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA\Tests;

use WPPA\Keys;
use WPPA\Legacy\Utilities;
use WPPA\Publication_Item;
use WPPA\Widgets\Archive_Widget;
use WPPA\Widgets\Category_Count_Widget;
use WPPA\Widgets\Related_Widget;

class Test_Aliases extends \WP_UnitTestCase {

	/**
	 * Every public method (and constructor) of every 3.0.1 class this plugin
	 * still exposes an alias for, written out literally from
	 * e913681:lib/*.php (not computed): method name => [parameter count,
	 * is static].
	 *
	 * @var array<string, array<string, array{0: int, 1: bool}>>
	 */
	private const METHODS_301 = array(
		'WP_Publication_Archive'                => array(
			'upgrade'                     => array( 1, true ),
			'get_open_link'                => array( 1, true ),
			'get_download_link'            => array( 1, true ),
			'get_alternate_open_link'      => array( 2, true ),
			'get_alternate_download_link'  => array( 2, true ),
			'open_file'                    => array( 0, true ),
			'download_file'                => array( 0, true ),
			'get_image'                    => array( 1, true ),
			'enqueue_scripts_and_styles'   => array( 0, true ),
			'register_publication'         => array( 0, true ),
			'register_author'              => array( 0, true ),
			'pub_meta_boxes'               => array( 0, true ),
			'doc_uri_box'                  => array( 1, true ),
			'doc_thumb_box'                => array( 1, true ),
			'doc_alternates_box'           => array( 1, true ),
			'save_meta'                    => array( 1, true ),
			'shortcode_handler'            => array( 1, true ),
			'query_vars'                   => array( 1, true ),
			'custom_rewrites'              => array( 0, true ),
			'publication_link'             => array( 2, true ),
			'the_content'                  => array( 1, true ),
			'the_title'                    => array( 2, true ),
			'search'                       => array( 1, true ),
			'search_join'                  => array( 1, true ),
			'search_distinct'              => array( 1, true ),
			'query_publications'           => array( 1, true ),
			'custom_excerpt_length'        => array( 1, true ),
		),
		'WP_Publication_Archive_Item'           => array(
			'__construct'          => array( 1, false ),
			'get_the_title'        => array( 2, false ),
			'the_title'            => array( 0, false ),
			'get_the_thumbnail'    => array( 2, false ),
			'the_thumbnail'        => array( 0, false ),
			'get_the_authors'      => array( 2, false ),
			'the_authors'          => array( 0, false ),
			'get_the_link'         => array( 0, false ),
			'get_the_uri'          => array( 0, false ),
			'the_uri'              => array( 0, false ),
			'get_the_summary'      => array( 0, false ),
			'the_summary'          => array( 0, false ),
			'get_the_keywords'     => array( 0, false ),
			'the_keywords'         => array( 0, false ),
			'get_the_categories'   => array( 0, false ),
			'the_categories'       => array( 0, false ),
			'list_downloads'       => array( 0, false ),
		),
		'WP_Publication_Archive_Utilities'      => array(
			'get_instance'          => array( 0, true ),
			'create_instance'       => array( 0, true ),
			'register_widgets'      => array( 0, false ),
			'dropdown_categories'   => array( 1, false ),
			'list_categories'       => array( 1, false ),
			'get_terms'             => array( 2, false ),
			'filter_terms_by_cpt'   => array( 3, false ),
			'filter_category_link'  => array( 3, false ),
			'single_publication'    => array( 1, false ),
			'publication_archives'  => array( 1, false ),
		),
		'WP_Publication_Archive_Widget'         => array(
			'__construct' => array( 0, false ),
			'form'        => array( 1, false ),
			'update'      => array( 2, false ),
			'widget'      => array( 2, false ),
		),
		'WP_Publication_Archive_Cat_Count_Widget' => array(
			'__construct' => array( 0, false ),
			'form'        => array( 1, false ),
			'update'      => array( 2, false ),
			'widget'      => array( 2, false ),
		),
		'WP_Publication_Archive_Category_Widget' => array(
			'__construct'            => array( 0, false ),
			'form'                   => array( 1, false ),
			'update'                 => array( 2, false ),
			'widget'                 => array( 2, false ),
			'limit_summary_length'   => array( 1, false ),
		),
	);

	public function test_item_alias_resolves_to_publication_item() {
		$this->assertTrue( class_exists( Keys::LEGACY_CLASS_ITEM, false ) );

		$reflection = new \ReflectionClass( Keys::LEGACY_CLASS_ITEM );
		$this->assertSame( Publication_Item::class, $reflection->getName() );
	}

	public function test_publication_item_is_not_final() {
		$reflection = new \ReflectionClass( Publication_Item::class );

		$this->assertFalse( $reflection->isFinal() );
	}

	public function test_utilities_and_cat_count_aliases_resolve() {
		$this->assertTrue( class_exists( Keys::LEGACY_CLASS_UTILITIES, false ) );
		$this->assertTrue( class_exists( Keys::LEGACY_CLASS_CAT_COUNT_WIDGET, false ) );

		$utilities_reflection = new \ReflectionClass( Keys::LEGACY_CLASS_UTILITIES );
		$this->assertSame( Utilities::class, $utilities_reflection->getName() );
		$this->assertFalse( $utilities_reflection->isFinal() );

		$widget_reflection = new \ReflectionClass( Keys::LEGACY_CLASS_CAT_COUNT_WIDGET );
		$this->assertSame( Category_Count_Widget::class, $widget_reflection->getName() );
		$this->assertFalse( $widget_reflection->isFinal() );
	}

	public function test_widget_aliases_resolve_and_are_the_factory_keys() {
		\WPPA\Plugin::instance()->register_widgets();

		global $wp_widget_factory;

		$this->assertTrue( class_exists( Keys::LEGACY_CLASS_ARCHIVE_WIDGET, false ) );
		$this->assertTrue( class_exists( Keys::LEGACY_CLASS_RELATED_WIDGET, false ) );

		$archive_reflection = new \ReflectionClass( Keys::LEGACY_CLASS_ARCHIVE_WIDGET );
		$this->assertSame( Archive_Widget::class, $archive_reflection->getName() );
		$this->assertFalse( $archive_reflection->isFinal() );

		$related_reflection = new \ReflectionClass( Keys::LEGACY_CLASS_RELATED_WIDGET );
		$this->assertSame( Related_Widget::class, $related_reflection->getName() );
		$this->assertFalse( $related_reflection->isFinal() );

		$this->assertArrayHasKey( Keys::LEGACY_CLASS_ARCHIVE_WIDGET, $wp_widget_factory->widgets );
		$this->assertInstanceOf( Archive_Widget::class, $wp_widget_factory->widgets[ Keys::LEGACY_CLASS_ARCHIVE_WIDGET ] );
		$this->assertSame( Keys::WIDGET_ARCHIVE_ID_BASE, $wp_widget_factory->widgets[ Keys::LEGACY_CLASS_ARCHIVE_WIDGET ]->id_base );

		$this->assertArrayHasKey( Keys::LEGACY_CLASS_RELATED_WIDGET, $wp_widget_factory->widgets );
		$this->assertInstanceOf( Related_Widget::class, $wp_widget_factory->widgets[ Keys::LEGACY_CLASS_RELATED_WIDGET ] );
		$this->assertSame( Keys::WIDGET_RELATED_ID_BASE, $wp_widget_factory->widgets[ Keys::LEGACY_CLASS_RELATED_WIDGET ]->id_base );
	}

	public function test_every_301_class_name_resolves() {
		foreach ( array_keys( self::METHODS_301 ) as $class_name ) {
			$this->assertTrue( class_exists( $class_name, false ), "$class_name does not resolve." );
		}
	}

	public function test_every_301_public_method_exists_with_the_same_parameter_count_and_staticness() {
		foreach ( self::METHODS_301 as $class_name => $methods ) {
			$reflection = new \ReflectionClass( $class_name );

			foreach ( $methods as $method_name => list( $parameter_count, $is_static ) ) {
				$this->assertTrue( $reflection->hasMethod( $method_name ), "$class_name::$method_name does not exist." );

				$method = $reflection->getMethod( $method_name );

				$this->assertTrue( $method->isPublic(), "$class_name::$method_name is not public." );
				$this->assertSame( $is_static, $method->isStatic(), "$class_name::$method_name staticness changed." );
				$this->assertSame( $parameter_count, $method->getNumberOfParameters(), "$class_name::$method_name parameter count changed." );
			}
		}
	}

	public function test_aliased_classes_are_not_final() {
		foreach ( array_keys( self::METHODS_301 ) as $class_name ) {
			$reflection = new \ReflectionClass( $class_name );

			$this->assertFalse( $reflection->isFinal(), "$class_name is final." );
		}
	}

	public function test_mimetype_is_not_aliased() {
		$this->assertFalse( class_exists( 'WP_Publication_Archive_Mimetype', false ) );
	}
}
