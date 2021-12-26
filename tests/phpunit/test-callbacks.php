<?php

class TestCallbacks extends QM_UnitTestCase {

	/**
	 * @param mixed $function
	 */
	protected static function get_callback( $function ) {

		add_action( 'qm/tests', $function );

		$actions = $GLOBALS['wp_filter']['qm/tests'][10];
		$keys = array_keys( $actions );

		return $actions[ $keys[0] ];

	}

	public function testCallbackIsCorrectlyPopulatedWithProceduralFunction() {

		$function = '__return_false';
		$callback = self::get_callback( $function );

		$ref = new ReflectionFunction( $function );
		$actual = QM_Util::populate_callback( $callback );

		self::assertEquals( $function,            $actual['function'] );
		self::assertEquals( '__return_false()',   $actual['name'] );
		self::assertEquals( $ref->getFileName(),  $actual['file'] );
		self::assertEquals( $ref->getStartLine(), $actual['line'] );

	}

	public function testCallbackIsCorrectlyPopulatedWithObjectMethod() {

		$obj = new QM_Test_Object;
		$function = array( $obj, 'hello' );
		$callback = self::get_callback( $function );

		$ref = new ReflectionMethod( $function[0], $function[1] );
		$actual = QM_Util::populate_callback( $callback );

		self::assertEquals( $function,                 $actual['function'] );
		self::assertEquals( 'QM_Test_Object->hello()', $actual['name'] );
		self::assertEquals( $ref->getFileName(),       $actual['file'] );
		self::assertEquals( $ref->getStartLine(),      $actual['line'] );

	}

	public function testCallbackIsCorrectlyPopulatedWithInvokable() {

		$function = new QM_Test_Invokable;
		$callback = self::get_callback( $function );

		$ref = new ReflectionMethod( $function, '__invoke' );
		$actual = QM_Util::populate_callback( $callback );
		$name = 'QM_Test_Invokable->__invoke()';

		self::assertEquals( $function,            $actual['function'] );
		self::assertEquals( $name,                $actual['name'] );
		self::assertEquals( $ref->getFileName(),  $actual['file'] );
		self::assertEquals( $ref->getStartLine(), $actual['line'] );

	}

	public function testCallbackIsCorrectlyPopulatedWithStaticMethodArray() {

		$function = array( 'QM_Test_Object', 'hello' );
		$callback = self::get_callback( $function );

		$ref = new ReflectionMethod( $function[0], $function[1] );
		$actual = QM_Util::populate_callback( $callback );

		self::assertEquals( $function,                 $actual['function'] );
		self::assertEquals( 'QM_Test_Object::hello()', $actual['name'] );
		self::assertEquals( $ref->getFileName(),       $actual['file'] );
		self::assertEquals( $ref->getStartLine(),      $actual['line'] );

	}

	public function testCallbackIsCorrectlyPopulatedWithStaticMethodString() {

		$function = 'QM_Test_Object::hello';
		$callback = self::get_callback( $function );

		$ref = new ReflectionMethod( 'QM_Test_Object', 'hello' );
		$actual = QM_Util::populate_callback( $callback );

		self::assertEquals( array( 'QM_Test_Object', 'hello' ), $actual['function'] );
		self::assertEquals( 'QM_Test_Object::hello()',          $actual['name'] );
		self::assertEquals( $ref->getFileName(),                $actual['file'] );
		self::assertEquals( $ref->getStartLine(),               $actual['line'] );

	}

	public function testCallbackIsCorrectlyPopulatedWithClosure() {

		$function = require_once __DIR__ . '/includes/dummy-closures.php';

		$callback = self::get_callback( $function );

		$ref = new ReflectionFunction( $function );
		$actual = QM_Util::populate_callback( $callback );
		$name = sprintf( 'Closure on line %1$d of %2$s', $ref->getStartLine(), 'tests/phpunit/includes/dummy-closures.php' );

		self::assertEquals( $function,            $actual['function'] );
		self::assertEquals( $name,                $actual['name'] );
		self::assertEquals( $ref->getFileName(),  $actual['file'] );
		self::assertEquals( $ref->getStartLine(), $actual['line'] );

	}

	public function testCallbackIsCorrectlyPopulatedWithInvalidProceduralFunction() {

		$function = 'invalid_function';
		$callback = self::get_callback( $function );

		$actual = QM_Util::populate_callback( $callback );

		$this->assertWPError( $actual['error'] );

	}

	public function testCallbackIsCorrectlyPopulatedWithInvalidObjectMethod() {

		$obj = new QM_Test_Object;
		$function = array( $obj, 'goodbye' );
		$callback = self::get_callback( $function );

		$actual = QM_Util::populate_callback( $callback );

		$this->assertWPError( $actual['error'] );

	}

	public function testCallbackIsCorrectlyPopulatedWithInvalidInvokable() {

		$function = new QM_Test_Object;
		$callback = self::get_callback( $function );

		$actual = QM_Util::populate_callback( $callback );

		$this->assertWPError( $actual['error'] );

	}

	public function testCallbackIsCorrectlyPopulatedWithInvalidStaticMethodArray() {

		$function = array( 'QM_Test_Object', 'goodbye' );
		$callback = self::get_callback( $function );

		$actual = QM_Util::populate_callback( $callback );

		$this->assertWPError( $actual['error'] );

	}

	public function testCallbackIsCorrectlyPopulatedWithInvalidStaticMethodString() {

		$function = 'QM_Test_Object::goodbye';
		$callback = self::get_callback( $function );

		$actual = QM_Util::populate_callback( $callback );

		$this->assertWPError( $actual['error'] );

	}

	public function testCallbackIsCorrectlyPopulatedWithInvalidStaticClassArray() {

		$function = array( 'Invalid_Class', 'goodbye' );
		$callback = self::get_callback( $function );

		$actual = QM_Util::populate_callback( $callback );

		$this->assertWPError( $actual['error'] );

	}

	public function testCallbackIsCorrectlyPopulatedWithInvalidStaticClassString() {

		$function = 'Invalid_Class::goodbye';
		$callback = self::get_callback( $function );

		$actual = QM_Util::populate_callback( $callback );

		$this->assertWPError( $actual['error'] );

	}

}
