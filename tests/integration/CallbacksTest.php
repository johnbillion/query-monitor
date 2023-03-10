<?php declare(strict_types = 1);

namespace QM\Tests;

use QM_Callback;

class Callbacks extends Test {
	public function testCallbackIsCorrectlyPopulatedWithProceduralFunction(): void {
		$callback = '__return_false';

		$ref = new \ReflectionFunction( $callback );
		$actual = QM_Callback::from_callable( $callback );

		self::assertSame( '__return_false()', $actual->name );
		self::assertSame( $ref->getFileName(), $actual->file );
		self::assertSame( $ref->getStartLine(), $actual->line );
	}

	public function testCallbackIsCorrectlyPopulatedWithObjectMethod(): void {
		$obj = new Supports\TestObject;
		$callback = array( $obj, 'hello' );

		$ref = new \ReflectionMethod( $callback[0], $callback[1] );
		$actual = QM_Callback::from_callable( $callback );

		self::assertSame( 'QM\T\S\TestObject->hello()', $actual->name );
		self::assertSame( $ref->getFileName(), $actual->file );
		self::assertSame( $ref->getStartLine(), $actual->line );
	}

	public function testCallbackIsCorrectlyPopulatedWithInvokable(): void {
		$callback = new Supports\TestInvokable;

		$ref = new \ReflectionMethod( $callback, '__invoke' );
		$actual = QM_Callback::from_callable( $callback );
		$name = 'QM\T\S\TestInvokable->__invoke()';

		self::assertSame( $name, $actual->name );
		self::assertSame( $ref->getFileName(), $actual->file );
		self::assertSame( $ref->getStartLine(), $actual->line );
	}

	public function testCallbackIsCorrectlyPopulatedWithStaticMethodArray(): void {
		$callback = array( '\QM\Tests\Supports\TestObject', 'hello' );

		$ref = new \ReflectionMethod( $callback[0], $callback[1] );
		$actual = QM_Callback::from_callable( $callback );

		self::assertSame( '\Q\T\S\TestObject::hello()', $actual->name );
		self::assertSame( $ref->getFileName(), $actual->file );
		self::assertSame( $ref->getStartLine(), $actual->line );
	}

	public function testCallbackIsCorrectlyPopulatedWithStaticMethodString(): void {
		$callback = '\QM\Tests\Supports\TestObject::hello';

		$ref = new \ReflectionMethod( '\QM\Tests\Supports\TestObject', 'hello' );
		$actual = QM_Callback::from_callable( $callback );

		self::assertSame( '\Q\T\S\TestObject::hello()', $actual->name );
		self::assertSame( $ref->getFileName(), $actual->file );
		self::assertSame( $ref->getStartLine(), $actual->line );
	}

	public function testCallbackIsCorrectlyPopulatedWithClosure(): void {
		$callback = function() {};

		$ref = new \ReflectionFunction( $callback );
		$actual = QM_Callback::from_callable( $callback );
		$name = sprintf(
			'Closure on line %1$d of %2$s',
			$ref->getStartLine(),
			'wp-content/plugins/query-monitor/tests/integration/CallbacksTest.php'
		);

		self::assertSame( $name, $actual->name );
		self::assertSame( $ref->getFileName(), $actual->file );
		self::assertSame( $ref->getStartLine(), $actual->line );
	}

	public function testCallbackIsCorrectlyPopulatedWithNativePHPFunction(): void {
		$callback = 'strrev';

		$actual = QM_Callback::from_callable( $callback );
		$name = 'strrev()';

		self::assertSame( $name, $actual->name );
		self::assertNull( $actual->file );
		self::assertNull( $actual->line );
	}

	/**
	 * @requires PHP >= 7.4
	 */
	public function testCallbackIsCorrectlyPopulatedWithArrowFunction(): void {
		$callback = require_once __DIR__ . '/includes/dummy-arrow-function.php';

		$ref = new \ReflectionFunction( $callback );
		$actual = QM_Callback::from_callable( $callback );
		$name = sprintf(
			'Closure on line %d of wp-content/plugins/query-monitor/tests/integration/includes/dummy-arrow-function.php',
			$ref->getStartLine()
		);

		self::assertSame( $name, $actual->name );
		self::assertSame( $ref->getFileName(), $actual->file );
		self::assertSame( $ref->getStartLine(), $actual->line );
	}

	public function testCallbackIsCorrectlyPopulatedWithInvalidProceduralFunction(): void {
		self::expectException( \QM_CallbackException::class );

		$callback = 'invalid_function';

		$actual = QM_Callback::from_callable( $callback );
	}

	public function testCallbackIsCorrectlyPopulatedWithInvalidObjectMethod(): void {
		self::expectException( \QM_CallbackException::class );

		$obj = new Supports\TestObject;
		$callback = array( $obj, 'goodbye' );

		QM_Callback::from_callable( $callback );
	}

	public function testCallbackIsCorrectlyPopulatedWithInvalidInvokable(): void {
		self::expectException( \QM_CallbackException::class );

		$callback = new Supports\TestObject;

		QM_Callback::from_callable( $callback );
	}

	public function testCallbackIsCorrectlyPopulatedWithInvalidStaticMethodArray(): void {
		self::expectException( \QM_CallbackException::class );

		$callback = array( '\QM\Tests\Supports\TestObject', 'goodbye' );

		QM_Callback::from_callable( $callback );
	}

	public function testCallbackIsCorrectlyPopulatedWithInvalidStaticMethodString(): void {
		self::expectException( \QM_CallbackException::class );

		$callback = '\QM\Tests\Supports\TestObject::goodbye';

		QM_Callback::from_callable( $callback );
	}

	public function testCallbackIsCorrectlyPopulatedWithInvalidStaticClassArray(): void {
		self::expectException( \QM_CallbackException::class );

		$callback = array( 'Invalid_Class', 'goodbye' );

		QM_Callback::from_callable( $callback );
	}

	public function testCallbackIsCorrectlyPopulatedWithInvalidStaticClassString(): void {
		self::expectException( \QM_CallbackException::class );

		$callback = 'Invalid_Class::goodbye';

		QM_Callback::from_callable( $callback );
	}
}
