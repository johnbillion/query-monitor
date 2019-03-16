<?php

class TestStackTraces extends QM_UnitTestCase {

	protected static function get_callback( $function ) {

		add_action( 'qm/tests', $function );

		$actions = $GLOBALS['wp_filter']['qm/tests'][10];
		$keys    = array_keys( $actions );

		return $actions[ $keys[0] ];

	}

	public function testCallbackIsCorrectlyPopulatedWithProceduralFunction() {

		$function = '__return_false';
		$callback = self::get_callback( $function );

		$ref    = new ReflectionFunction( $function );
		$actual = QM_Util::populate_callback( $callback );

		$this->assertEquals( $function,            $actual['function'] );
		$this->assertEquals( '__return_false()',   $actual['name'] );
		$this->assertEquals( $ref->getFileName(),  $actual['file'] );
		$this->assertEquals( $ref->getStartLine(), $actual['line'] );

	}

	public function testCallbackIsCorrectlyPopulatedWithObjectMethod() {

		$obj      = new QM_Test_Object;
		$function = array( $obj, 'hello' );
		$callback = self::get_callback( $function );

		$ref    = new ReflectionMethod( $function[0], $function[1] );
		$actual = QM_Util::populate_callback( $callback );

		$this->assertEquals( $function,                 $actual['function'] );
		$this->assertEquals( 'QM_Test_Object->hello()', $actual['name'] );
		$this->assertEquals( $ref->getFileName(),       $actual['file'] );
		$this->assertEquals( $ref->getStartLine(),      $actual['line'] );

	}

	public function testCallbackIsCorrectlyPopulatedWithInvokable() {

		$function = new QM_Test_Invokable;
		$callback = self::get_callback( $function );

		$ref    = new ReflectionMethod( $function, '__invoke' );
		$actual = QM_Util::populate_callback( $callback );
		$name   = 'QM_Test_Invokable->__invoke()';

		$this->assertEquals( $function,            $actual['function'] );
		$this->assertEquals( $name,                $actual['name'] );
		$this->assertEquals( $ref->getFileName(),  $actual['file'] );
		$this->assertEquals( $ref->getStartLine(), $actual['line'] );

	}

	public function testCallbackIsCorrectlyPopulatedWithStaticMethodArray() {

		$function = array( 'QM_Test_Object', 'hello' );
		$callback = self::get_callback( $function );

		$ref    = new ReflectionMethod( $function[0], $function[1] );
		$actual = QM_Util::populate_callback( $callback );

		$this->assertEquals( $function,                 $actual['function'] );
		$this->assertEquals( 'QM_Test_Object::hello()', $actual['name'] );
		$this->assertEquals( $ref->getFileName(),       $actual['file'] );
		$this->assertEquals( $ref->getStartLine(),      $actual['line'] );

	}

	public function testCallbackIsCorrectlyPopulatedWithStaticMethodString() {

		$function = 'QM_Test_Object::hello';
		$callback = self::get_callback( $function );

		$ref    = new ReflectionMethod( 'QM_Test_Object', 'hello' );
		$actual = QM_Util::populate_callback( $callback );

		$this->assertEquals( array( 'QM_Test_Object', 'hello' ), $actual['function'] );
		$this->assertEquals( 'QM_Test_Object::hello()',          $actual['name'] );
		$this->assertEquals( $ref->getFileName(),                $actual['file'] );
		$this->assertEquals( $ref->getStartLine(),               $actual['line'] );

	}

	public function testCallbackIsCorrectlyPopulatedWithClosure() {

		require_once dirname( __FILE__ ) . '/includes/dummy-closures.php';

		$callback = self::get_callback( $function );

		$ref    = new ReflectionFunction( $function );
		$actual = QM_Util::populate_callback( $callback );
		$file   = QM_Util::standard_dir( $ref->getFileName(), '' );
		$name   = sprintf( 'Closure on line %1$d of %2$s', $ref->getStartLine(), 'tests/phpunit/includes/dummy-closures.php' );

		$this->assertEquals( $function,            $actual['function'] );
		$this->assertEquals( $name,                $actual['name'] );
		$this->assertEquals( $ref->getFileName(),  $actual['file'] );
		$this->assertEquals( $ref->getStartLine(), $actual['line'] );

	}

	public function testCallbackIsCorrectlyPopulatedWithLambda() {

		if ( version_compare( phpversion(), '7.2', '>=' ) ) {
			$this->markTestSkipped( 'Lambda functions are deprecated in PHP 7.2' );
		}

		$file_name = dirname( __FILE__ ) . '/includes/dummy-lambdas.php';

		require_once $file_name;

		$callback = self::get_callback( $function );

		$ref    = new ReflectionFunction( $function );
		$actual = QM_Util::populate_callback( $callback );
		$file   = trim( QM_Util::standard_dir( $file_name, '' ), '/' );

		preg_match( '|(?P<file>.*)\((?P<line>[0-9]+)\)|', $ref->getFileName(), $matches );

		$line = $matches['line'];
		$name = sprintf( 'Anonymous function on line %1$d of %2$s', $line, $file );

		$this->assertEquals( $function,  $actual['function'] );
		$this->assertEquals( $name,      $actual['name'] );
		$this->assertEquals( $file_name, $actual['file'] );
		$this->assertEquals( $line,      $actual['line'] );

	}

	public function testCallbackIsCorrectlyPopulatedWithInvalidProceduralFunction() {

		$function = 'invalid_function';
		$callback = self::get_callback( $function );

		$actual = QM_Util::populate_callback( $callback );

		$this->assertWPError( $actual['error'] );

	}

	public function testCallbackIsCorrectlyPopulatedWithInvalidObjectMethod() {

		$obj      = new QM_Test_Object;
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
