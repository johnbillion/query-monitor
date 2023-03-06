<?php declare(strict_types = 1);

namespace QM\Tests;

use QM_Callback;

class Callbacks extends Test {
	public function testCallbackIsCorrectlyPopulatedWithProceduralFunction(): void {
		$callback = '__return_false';

		$ref = new \ReflectionFunction( $callback );
		$actual = QM_Callback::from_callable( $callback );

		self::assertTrue( $actual->isValid() );
		self::assertSame( '__return_false()',   $actual->name );
		self::assertSame( $ref->getFileName(),  $actual->file );
		self::assertSame( $ref->getStartLine(), $actual->line );
	}

	public function testCallbackIsCorrectlyPopulatedWithObjectMethod(): void {
		$obj = new Supports\TestObject;
		$callback = array( $obj, 'hello' );

		$ref = new \ReflectionMethod( $callback[0], $callback[1] );
		$actual = QM_Callback::from_callable( $callback );

		self::assertTrue( $actual->isValid() );
		self::assertSame( 'QM\T\S\TestObject->hello()', $actual->name );
		self::assertSame( $ref->getFileName(),          $actual->file );
		self::assertSame( $ref->getStartLine(),         $actual->line );
	}

	public function testCallbackIsCorrectlyPopulatedWithInvokable(): void {
		$callback = new Supports\TestInvokable;

		$ref = new \ReflectionMethod( $callback, '__invoke' );
		$actual = QM_Callback::from_callable( $callback );
		$name = 'QM\T\S\TestInvokable->__invoke()';

		self::assertTrue( $actual->isValid() );
		self::assertSame( $name,                $actual->name );
		self::assertSame( $ref->getFileName(),  $actual->file );
		self::assertSame( $ref->getStartLine(), $actual->line );
	}

	public function testCallbackIsCorrectlyPopulatedWithStaticMethodArray(): void {
		$callback = array( '\QM\Tests\Supports\TestObject', 'hello' );

		$ref = new \ReflectionMethod( $callback[0], $callback[1] );
		$actual = QM_Callback::from_callable( $callback );

		self::assertTrue( $actual->isValid() );
		self::assertSame( '\Q\T\S\TestObject::hello()', $actual->name );
		self::assertSame( $ref->getFileName(),          $actual->file );
		self::assertSame( $ref->getStartLine(),         $actual->line );
	}

	public function testCallbackIsCorrectlyPopulatedWithStaticMethodString(): void {
		$callback = '\QM\Tests\Supports\TestObject::hello';

		$ref = new \ReflectionMethod( '\QM\Tests\Supports\TestObject', 'hello' );
		$actual = QM_Callback::from_callable( $callback );

		self::assertTrue( $actual->isValid() );
		self::assertSame( '\Q\T\S\TestObject::hello()', $actual->name );
		self::assertSame( $ref->getFileName(),          $actual->file );
		self::assertSame( $ref->getStartLine(),         $actual->line );
	}

	public function testCallbackIsCorrectlyPopulatedWithClosure(): void {
		$callback = require_once __DIR__ . '/includes/dummy-closures.php';

		$ref = new \ReflectionFunction( $callback );
		$actual = QM_Callback::from_callable( $callback );
		$name = sprintf(
			'Closure on line %1$d of %2$s',
			$ref->getStartLine(),
			'wp-content/plugins/query-monitor/tests/integration/includes/dummy-closures.php'
		);

		self::assertTrue( $actual->isValid() );
		self::assertSame( $name,                $actual->name );
		self::assertSame( $ref->getFileName(),  $actual->file );
		self::assertSame( $ref->getStartLine(), $actual->line );
	}

	public function testCallbackIsCorrectlyPopulatedWithInvalidProceduralFunction(): void {
		$callback = 'invalid_function';

		$actual = QM_Callback::from_callable( $callback );

		self::assertFalse( $actual->isValid() );
		self::assertSame( '@todo error message', $actual->error );
	}

	public function testCallbackIsCorrectlyPopulatedWithInvalidObjectMethod(): void {
		$obj = new Supports\TestObject;
		$callback = array( $obj, 'goodbye' );

		$actual = QM_Callback::from_callable( $callback );

		self::assertFalse( $actual->isValid() );
	}

	public function testCallbackIsCorrectlyPopulatedWithInvalidInvokable(): void {
		$callback = new Supports\TestObject;

		$actual = QM_Callback::from_callable( $callback );

		self::assertFalse( $actual->isValid() );
	}

	public function testCallbackIsCorrectlyPopulatedWithInvalidStaticMethodArray(): void {
		$callback = array( '\QM\Tests\Supports\TestObject', 'goodbye' );

		$actual = QM_Callback::from_callable( $callback );

		self::assertFalse( $actual->isValid() );
	}

	public function testCallbackIsCorrectlyPopulatedWithInvalidStaticMethodString(): void {
		$callback = '\QM\Tests\Supports\TestObject::goodbye';

		$actual = QM_Callback::from_callable( $callback );

		self::assertFalse( $actual->isValid() );
	}

	public function testCallbackIsCorrectlyPopulatedWithInvalidStaticClassArray(): void {
		$callback = array( 'Invalid_Class', 'goodbye' );

		$actual = QM_Callback::from_callable( $callback );

		self::assertFalse( $actual->isValid() );
	}

	public function testCallbackIsCorrectlyPopulatedWithInvalidStaticClassString(): void {
		$callback = 'Invalid_Class::goodbye';

		$actual = QM_Callback::from_callable( $callback );

		self::assertFalse( $actual->isValid() );
	}
}
