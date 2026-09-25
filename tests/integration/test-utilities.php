<?php
/**
 * Implements SPEC.md §8 Phase 0 item 4: the 3.0.1
 * WP_Publication_Archive_Utilities delegate singleton.
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA\Tests;

use WPPA\Keys;
use WPPA\Legacy\Utilities;
use WPPA\Tests\Fixtures\V3_Site;

class Test_Utilities extends \WP_UnitTestCase {

	public function test_get_instance_returns_the_singleton_created_at_boot() {
		$instance = Utilities::get_instance();

		$this->assertInstanceOf( Utilities::class, $instance );
	}

	public function test_create_instance_throws_if_already_initialized() {
		$this->expectException( \Exception::class );

		Utilities::create_instance();
	}

	public function test_utilities_is_not_final() {
		$reflection = new \ReflectionClass( Utilities::class );

		$this->assertFalse( $reflection->isFinal() );
	}

	public function test_dropdown_categories_delegates_to_plugin_categories_service() {
		V3_Site::create( self::factory() );

		$instance = Utilities::get_instance();
		$this->assertNotFalse( $instance );

		// See Test_Categories::test_dropdown_categories_lists_only_publication_categories
		// for why the default 'name' argument must be overridden here.
		$output = $instance->dropdown_categories(
			array(
				'echo' => 0,
				'name' => '',
			)
		);

		$this->assertStringContainsString( 'Reports', $output );
	}

	public function test_list_categories_delegates_to_plugin_categories_service() {
		V3_Site::create( self::factory() );

		$instance = Utilities::get_instance();
		$this->assertNotFalse( $instance );

		$output = $instance->list_categories( array( 'echo' => 0 ) );

		$this->assertStringContainsString( 'Reports', $output );
	}

	public function test_get_terms_delegates_to_plugin_categories_service() {
		V3_Site::create( self::factory() );

		$instance = Utilities::get_instance();
		$this->assertNotFalse( $instance );

		$terms = $instance->get_terms( 'category', array( 'post_types' => array( Keys::POST_TYPE ) ) );

		$this->assertIsArray( $terms );
	}

	public function test_single_publication_delegates_to_plugin_templates_service() {
		$id = self::factory()->post->create( array( 'post_type' => Keys::POST_TYPE ) );
		$this->go_to( get_permalink( $id ) );

		$instance = Utilities::get_instance();
		$this->assertNotFalse( $instance );

		$result = $instance->single_publication( 'default.php' );

		$this->assertSame( \WPPA\Plugin::instance()->templates()->single_template( 'default.php' ), $result );
	}

	public function test_register_widgets_delegates_to_plugin() {
		$instance = Utilities::get_instance();
		$this->assertNotFalse( $instance );

		// Should not throw; delegates to Plugin::register_widgets().
		$instance->register_widgets();

		global $wp_widget_factory;
		$this->assertArrayHasKey( Keys::LEGACY_CLASS_CAT_COUNT_WIDGET, $wp_widget_factory->widgets );
	}
}
