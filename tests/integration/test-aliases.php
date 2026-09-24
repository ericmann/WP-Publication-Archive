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
use WPPA\Widgets\Category_Count_Widget;

class Test_Aliases extends \WP_UnitTestCase {

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
}
