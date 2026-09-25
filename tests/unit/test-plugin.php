<?php
/**
 * Implements SPEC.md §4: structural checks on the composition root that
 * need no WordPress. Behaviour is covered in tests/integration/test-plugin.php.
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA\Tests\Unit;

use WPPA\Plugin;

class Test_Plugin extends \PHPUnit\Framework\TestCase {

	public function test_is_final_with_a_private_constructor() {
		$reflection = new \ReflectionClass( Plugin::class );

		$this->assertTrue( $reflection->isFinal() );

		$constructor = $reflection->getConstructor();
		$this->assertNotNull( $constructor );
		$this->assertTrue( $constructor->isPrivate() );
	}

	public function test_boot_and_instance_are_static() {
		$this->assertTrue( ( new \ReflectionMethod( Plugin::class, 'boot' ) )->isStatic() );
		$this->assertTrue( ( new \ReflectionMethod( Plugin::class, 'instance' ) )->isStatic() );
	}

	public function test_instance_throws_before_boot() {
		if ( function_exists( 'add_action' ) ) {
			$this->markTestSkipped( 'WordPress is loaded, so the plugin is already booted.' );
		}

		$this->expectException( \LogicException::class );

		Plugin::instance();
	}
}
