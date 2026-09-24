<?php
/**
 * Implements SPEC.md §8 Phase 0 item 4: class_alias() keeps 3.0.1 class
 * names resolvable.
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA\Tests;

use WPPA\Keys;
use WPPA\Publication_Item;

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
}
