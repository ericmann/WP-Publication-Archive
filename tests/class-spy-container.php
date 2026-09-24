<?php
/**
 * Builds mocks that fail the test if any method is called. Use it to assert
 * that a flag-gated callback touches no collaborator while the flag is off.
 */

namespace WPPA\Tests;

final class Spy_Container {

	private \PHPUnit\Framework\TestCase $test;

	/** @var list<class-string> */
	public array $mocked = array();

	public function __construct( \PHPUnit\Framework\TestCase $test ) {
		$this->test = $test;
	}

	public function never_touched( string $class ): object {
		$mock = $this->test->getMockBuilder( $class )
			->disableOriginalConstructor()
			->getMock();

		$mock->expects( new \PHPUnit\Framework\MockObject\Rule\InvokedCount( 0 ) )
			->method( new \PHPUnit\Framework\Constraint\IsAnything() );

		$this->mocked[] = $class;

		return $mock;
	}
}
