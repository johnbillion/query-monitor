<?php

class Test_Stack_Traces extends QM_UnitTestCase {

	protected static function get_callback( $function ) {

		add_action( 'qm/tests', $function );

		$actions = $GLOBALS['wp_filter']['qm/tests'][10];
		$keys    = array_keys( $actions );

		return $actions[ $keys[0] ];

	}

	public function test_populate_callback_procedural_function() {

		$function = '__return_false';
		$callback = self::get_callback( $function );

		$ref    = new ReflectionFunction( $function );
		$actual = QM_Util::populate_callback( $callback );

		$this->assertEquals( $function,            $actual['function'] );
		$this->assertEquals( '__return_false()',   $actual['name'] );
		$this->assertEquals( $ref->getFileName(),  $actual['file'] );
		$this->assertEquals( $ref->getStartLine(), $actual['line'] );

	}

	public function test_populate_callback_object_method() {

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

	public function test_populate_callback_invokable() {

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

	public function test_populate_callback_static_method_array() {

		$function = array( 'QM_Test_Object', 'hello' );
		$callback = self::get_callback( $function );

		$ref    = new ReflectionMethod( $function[0], $function[1] );
		$actual = QM_Util::populate_callback( $callback );

		$this->assertEquals( $function,                 $actual['function'] );
		$this->assertEquals( 'QM_Test_Object::hello()', $actual['name'] );
		$this->assertEquals( $ref->getFileName(),       $actual['file'] );
		$this->assertEquals( $ref->getStartLine(),      $actual['line'] );

	}

	public function test_populate_callback_static_method_string() {

		$function = 'QM_Test_Object::hello';
		$callback = self::get_callback( $function );

		$ref    = new ReflectionMethod( 'QM_Test_Object', 'hello' );
		$actual = QM_Util::populate_callback( $callback );

		$this->assertEquals( array( 'QM_Test_Object', 'hello' ), $actual['function'] );
		$this->assertEquals( 'QM_Test_Object::hello()',          $actual['name'] );
		$this->assertEquals( $ref->getFileName(),                $actual['file'] );
		$this->assertEquals( $ref->getStartLine(),               $actual['line'] );

	}

	public function test_populate_callback_closure() {

		if ( version_compare( phpversion(), '5.3', '<' ) ) {
			$this->markTestSkipped( 'PHP < 5.3 does not support closures' );
		}

		require_once dirname( __FILE__ ) . '/includes/dummy-closures.php';

		$callback = self::get_callback( $function );

		$ref    = new ReflectionFunction( $function );
		$actual = QM_Util::populate_callback( $callback );
		$file   = QM_Util::standard_dir( $ref->getFileName(), '' );
		$name   = sprintf( 'Closure on line %1$d of %2$s', $ref->getStartLine(), $file );

		$this->assertEquals( $function,            $actual['function'] );
		$this->assertEquals( $name,                $actual['name'] );
		$this->assertEquals( $ref->getFileName(),  $actual['file'] );
		$this->assertEquals( $ref->getStartLine(), $actual['line'] );

	}

	public function test_populate_callback_lambda() {

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

	public function test_populate_callback_invalid_procedural_function() {

		$function = 'invalid_function';
		$callback = self::get_callback( $function );

		$actual = QM_Util::populate_callback( $callback );

		$this->assertWPError( $actual['error'] );

	}

	public function test_populate_callback_invalid_object_method() {

		$obj      = new QM_Test_Object;
		$function = array( $obj, 'goodbye' );
		$callback = self::get_callback( $function );

		$actual = QM_Util::populate_callback( $callback );

		$this->assertWPError( $actual['error'] );

	}

	public function test_populate_callback_invalid_invokable() {

		$function = new QM_Test_Object;
		$callback = self::get_callback( $function );

		$actual = QM_Util::populate_callback( $callback );

		$this->assertWPError( $actual['error'] );

	}

	public function test_populate_callback_invalid_static_method_array() {

		$function = array( 'QM_Test_Object', 'goodbye' );
		$callback = self::get_callback( $function );

		$actual = QM_Util::populate_callback( $callback );

		$this->assertWPError( $actual['error'] );

	}

	public function test_populate_callback_invalid_static_method_string() {

		$function = 'QM_Test_Object::goodbye';
		$callback = self::get_callback( $function );

		$actual = QM_Util::populate_callback( $callback );

		$this->assertWPError( $actual['error'] );

	}

	public function test_populate_callback_invalid_static_class_array() {

		$function = array( 'Invalid_Class', 'goodbye' );
		$callback = self::get_callback( $function );

		$actual = QM_Util::populate_callback( $callback );

		$this->assertWPError( $actual['error'] );

	}

	public function test_populate_callback_invalid_static_class_string() {

		$function = 'Invalid_Class::goodbye';
		$callback = self::get_callback( $function );

		$actual = QM_Util::populate_callback( $callback );

		$this->assertWPError( $actual['error'] );

	}

}
