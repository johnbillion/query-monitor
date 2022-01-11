<?php

declare(strict_types = 1);

namespace QM\Tests;

class Callbacks extends Test {

	/**
	 * @param mixed $function
	 * @return mixed[]
	 */
	protected static function get_callback( $function ) {

		add_action( 'qm/tests', $function );

		$actions = $GLOBALS['wp_filter']['qm/tests'][10];
		$keys = array_keys( $actions );

		return $actions[ $keys[0] ];

	}

	public function testCallbackIsCorrectlyPopulatedWithProceduralFunction(): void {

		$function = '__return_false';
		$callback = self::get_callback( $function );

		$ref = new \ReflectionFunction( $function );
		$actual = \QM_Util::populate_callback( $callback );

		self::assertEquals( $function,            $actual['function'] );
		self::assertEquals( '__return_false()',   $actual['name'] );
		self::assertEquals( $ref->getFileName(),  $actual['file'] );
		self::assertEquals( $ref->getStartLine(), $actual['line'] );

	}

	public function testCallbackIsCorrectlyPopulatedWithObjectMethod(): void {

		$obj = new Supports\TestObject;
		$function = array( $obj, 'hello' );
		$callback = self::get_callback( $function );

		$ref = new \ReflectionMethod( $function[0], $function[1] );
		$actual = \QM_Util::populate_callback( $callback );

		self::assertEquals( $function,                 $actual['function'] );
		self::assertEquals( 'QM\T\S\TestObject->hello()', $actual['name'] );
		self::assertEquals( $ref->getFileName(),       $actual['file'] );
		self::assertEquals( $ref->getStartLine(),      $actual['line'] );

	}

	public function testCallbackIsCorrectlyPopulatedWithInvokable(): void {

		$function = new Supports\TestInvokable;
		$callback = self::get_callback( $function );

		$ref = new \ReflectionMethod( $function, '__invoke' );
		$actual = \QM_Util::populate_callback( $callback );
		$name = 'QM\T\S\TestInvokable->__invoke()';

		self::assertEquals( $function,            $actual['function'] );
		self::assertEquals( $name,                $actual['name'] );
		self::assertEquals( $ref->getFileName(),  $actual['file'] );
		self::assertEquals( $ref->getStartLine(), $actual['line'] );

	}

	public function testCallbackIsCorrectlyPopulatedWithStaticMethodArray(): void {

		$function = array( '\QM\Tests\Supports\TestObject', 'hello' );
		$callback = self::get_callback( $function );

		$ref = new \ReflectionMethod( $function[0], $function[1] );
		$actual = \QM_Util::populate_callback( $callback );

		self::assertEquals( $function,                 $actual['function'] );
		self::assertEquals( '\Q\T\S\TestObject::hello()', $actual['name'] );
		self::assertEquals( $ref->getFileName(),       $actual['file'] );
		self::assertEquals( $ref->getStartLine(),      $actual['line'] );

	}

	public function testCallbackIsCorrectlyPopulatedWithStaticMethodString(): void {

		$function = '\QM\Tests\Supports\TestObject::hello';
		$callback = self::get_callback( $function );

		$ref = new \ReflectionMethod( '\QM\Tests\Supports\TestObject', 'hello' );
		$actual = \QM_Util::populate_callback( $callback );

		self::assertEquals( array( '\QM\Tests\Supports\TestObject', 'hello' ), $actual['function'] );
		self::assertEquals( '\Q\T\S\TestObject::hello()',          $actual['name'] );
		self::assertEquals( $ref->getFileName(),                $actual['file'] );
		self::assertEquals( $ref->getStartLine(),               $actual['line'] );

	}

	public function testCallbackIsCorrectlyPopulatedWithClosure(): void {

		$function = require_once __DIR__ . '/includes/dummy-closures.php';

		$callback = self::get_callback( $function );

		$ref = new \ReflectionFunction( $function );
		$actual = \QM_Util::populate_callback( $callback );
		$name = sprintf(
			'Closure on line %1$d of %2$s',
			$ref->getStartLine(),
			'wp-content/plugins/query-monitor/tests/integration/includes/dummy-closures.php'
		);

		self::assertEquals( $function,            $actual['function'] );
		self::assertEquals( $name,                $actual['name'] );
		self::assertEquals( $ref->getFileName(),  $actual['file'] );
		self::assertEquals( $ref->getStartLine(), $actual['line'] );

	}

	public function testCallbackIsCorrectlyPopulatedWithInvalidProceduralFunction(): void {

		$function = 'invalid_function';
		$callback = self::get_callback( $function );

		$actual = \QM_Util::populate_callback( $callback );

		$this->assertWPError( $actual['error'] );

	}

	public function testCallbackIsCorrectlyPopulatedWithInvalidObjectMethod(): void {

		$obj = new Supports\TestObject;
		$function = array( $obj, 'goodbye' );
		$callback = self::get_callback( $function );

		$actual = \QM_Util::populate_callback( $callback );

		$this->assertWPError( $actual['error'] );

	}

	public function testCallbackIsCorrectlyPopulatedWithInvalidInvokable(): void {

		$function = new Supports\TestObject;
		$callback = self::get_callback( $function );

		$actual = \QM_Util::populate_callback( $callback );

		$this->assertWPError( $actual['error'] );

	}

	public function testCallbackIsCorrectlyPopulatedWithInvalidStaticMethodArray(): void {

		$function = array( '\QM\Tests\Supports\TestObject', 'goodbye' );
		$callback = self::get_callback( $function );

		$actual = \QM_Util::populate_callback( $callback );

		$this->assertWPError( $actual['error'] );

	}

	public function testCallbackIsCorrectlyPopulatedWithInvalidStaticMethodString(): void {

		$function = '\QM\Tests\Supports\TestObject::goodbye';
		$callback = self::get_callback( $function );

		$actual = \QM_Util::populate_callback( $callback );

		$this->assertWPError( $actual['error'] );

	}

	public function testCallbackIsCorrectlyPopulatedWithInvalidStaticClassArray(): void {

		$function = array( 'Invalid_Class', 'goodbye' );
		$callback = self::get_callback( $function );

		$actual = \QM_Util::populate_callback( $callback );

		$this->assertWPError( $actual['error'] );

	}

	public function testCallbackIsCorrectlyPopulatedWithInvalidStaticClassString(): void {

		$function = 'Invalid_Class::goodbye';
		$callback = self::get_callback( $function );

		$actual = \QM_Util::populate_callback( $callback );

		$this->assertWPError( $actual['error'] );

	}

}
