<?php

class Test_Stack_Traces extends WP_UnitTestCase {

	public function test_populate_callback_procedural_function() {
		global $wp_filter;

		add_action( 'qm/tests/' . __METHOD__, '__return_false' );

		$actions = $wp_filter[ 'qm/tests/' . __METHOD__ ];
		$ref     = new ReflectionFunction( '__return_false' );
		$actual  = QM_Util::populate_callback( reset( $actions[10] ) );

		$this->assertEquals( '__return_false',     $actual['function'] );
		$this->assertEquals( '__return_false()',   $actual['name'] );
		$this->assertEquals( $ref->getFileName(),  $actual['file'] );
		$this->assertEquals( $ref->getStartLine(), $actual['line'] );

	}

	public function test_populate_callback_object_method() {
		global $wp_filter;

		$obj = new QM_Test_Object;

		add_action( 'qm/tests/' . __METHOD__, array( $obj, 'hello' ) );

		$actions = $wp_filter[ 'qm/tests/' . __METHOD__ ];
		$ref     = new ReflectionMethod( $obj, 'hello' );
		$actual  = QM_Util::populate_callback( reset( $actions[10] ) );

		$this->assertEquals( array( $obj, 'hello' ),    $actual['function'] );
		$this->assertEquals( 'QM_Test_Object->hello()', $actual['name'] );
		$this->assertEquals( $ref->getFileName(),       $actual['file'] );
		$this->assertEquals( $ref->getStartLine(),      $actual['line'] );

	}

	public function test_populate_callback_invokable() {
		global $wp_filter;

		$function = new QM_Test_Invokable;

		add_action( 'qm/tests/' . __METHOD__, $function );

		$actions = $wp_filter[ 'qm/tests/' . __METHOD__ ];
		$ref     = new ReflectionMethod( $function, '__invoke' );
		$actual  = QM_Util::populate_callback( reset( $actions[10] ) );
		$name    = 'QM_Test_Invokable->__invoke()';

		$this->assertEquals( $function,            $actual['function'] );
		$this->assertEquals( $name,                $actual['name'] );
		$this->assertEquals( $ref->getFileName(),  $actual['file'] );
		$this->assertEquals( $ref->getStartLine(), $actual['line'] );

	}

	public function test_populate_callback_static_method_array() {
		global $wp_filter;

		add_action( 'qm/tests/' . __METHOD__, array( 'QM_Test_Object', 'hello' ) );

		$actions = $wp_filter[ 'qm/tests/' . __METHOD__ ];
		$ref     = new ReflectionMethod( 'QM_Test_Object', 'hello' );
		$actual  = QM_Util::populate_callback( reset( $actions[10] ) );

		$this->assertEquals( array( 'QM_Test_Object', 'hello' ), $actual['function'] );
		$this->assertEquals( 'QM_Test_Object::hello()',          $actual['name'] );
		$this->assertEquals( $ref->getFileName(),                $actual['file'] );
		$this->assertEquals( $ref->getStartLine(),               $actual['line'] );

	}

	public function test_populate_callback_static_method_string() {
		global $wp_filter;

		add_action( 'qm/tests/' . __METHOD__, 'QM_Test_Object::hello' );

		$actions = $wp_filter[ 'qm/tests/' . __METHOD__ ];
		$ref     = new ReflectionMethod( 'QM_Test_Object', 'hello' );
		$actual  = QM_Util::populate_callback( reset( $actions[10] ) );

		$this->assertEquals( array( 'QM_Test_Object', 'hello' ), $actual['function'] );
		$this->assertEquals( 'QM_Test_Object::hello()',          $actual['name'] );
		$this->assertEquals( $ref->getFileName(),                $actual['file'] );
		$this->assertEquals( $ref->getStartLine(),               $actual['line'] );

	}

	public function test_populate_callback_closure() {
		global $wp_filter;

		$function = function() {};

		add_action( 'qm/tests/' . __METHOD__, $function );

		$actions = $wp_filter[ 'qm/tests/' . __METHOD__ ];
		$ref     = new ReflectionFunction( $function );
		$actual  = QM_Util::populate_callback( reset( $actions[10] ) );
		$file    = trim( QM_Util::standard_dir( __FILE__, '' ), '/' );
		$name    = sprintf( 'Closure on line %1$d of %2$s', $ref->getStartLine(), $file );

		$this->assertEquals( $function,            $actual['function'] );
		$this->assertEquals( $name,                $actual['name'] );
		$this->assertEquals( __FILE__,             $actual['file'] );
		$this->assertEquals( $ref->getStartLine(), $actual['line'] );

	}

	public function test_populate_callback_lambda() {
		global $wp_filter;

		$function = create_function( '', '' );

		add_action( 'qm/tests/' . __METHOD__, $function );

		$actions = $wp_filter[ 'qm/tests/' . __METHOD__ ];
		$ref     = new ReflectionFunction( $function );
		$actual  = QM_Util::populate_callback( reset( $actions[10] ) );
		$file    = trim( QM_Util::standard_dir( __FILE__, '' ), '/' );

		preg_match( '|(?P<file>.*)\((?P<line>[0-9]+)\)|', $ref->getFileName(), $matches );

		$line = $matches['line'];
		$name = sprintf( 'Anonymous function on line %1$d of %2$s', $line, $file );

		$this->assertEquals( $function, $actual['function'] );
		$this->assertEquals( $name,     $actual['name'] );
		$this->assertEquals( __FILE__,  $actual['file'] );
		$this->assertEquals( $line,     $actual['line'] );

	}

	public function test_populate_callback_invalid_procedural_function() {
		global $wp_filter;

		add_action( 'qm/tests/' . __METHOD__, 'invalid_function' );

		$actions = $wp_filter[ 'qm/tests/' . __METHOD__ ];
		$actual  = QM_Util::populate_callback( reset( $actions[10] ) );

		$this->assertTrue( is_wp_error( $actual['error'] ) );

	}

	public function test_populate_callback_invalid_object_method() {
		global $wp_filter;

		$obj = new QM_Test_Object;
		add_action( 'qm/tests/' . __METHOD__, array( $obj, 'goodbye' ) );

		$actions = $wp_filter[ 'qm/tests/' . __METHOD__ ];
		$actual  = QM_Util::populate_callback( reset( $actions[10] ) );

		$this->assertTrue( is_wp_error( $actual['error'] ) );

	}

	public function test_populate_callback_invalid_invokable() {
		global $wp_filter;

		$function = new QM_Test_Object;

		add_action( 'qm/tests/' . __METHOD__, $function );

		$actions = $wp_filter[ 'qm/tests/' . __METHOD__ ];
		$actual  = QM_Util::populate_callback( reset( $actions[10] ) );

		$this->assertTrue( is_wp_error( $actual['error'] ) );

	}

	public function test_populate_callback_invalid_static_method_array() {
		global $wp_filter;

		add_action( 'qm/tests/' . __METHOD__, array( 'QM_Test_Object', 'goodbye' ) );

		$actions = $wp_filter[ 'qm/tests/' . __METHOD__ ];
		$actual  = QM_Util::populate_callback( reset( $actions[10] ) );

		$this->assertTrue( is_wp_error( $actual['error'] ) );

	}

	public function test_populate_callback_invalid_static_method_string() {
		global $wp_filter;

		add_action( 'qm/tests/' . __METHOD__, 'QM_Test_Object::goodbye' );

		$actions = $wp_filter[ 'qm/tests/' . __METHOD__ ];
		$actual  = QM_Util::populate_callback( reset( $actions[10] ) );

		$this->assertTrue( is_wp_error( $actual['error'] ) );

	}

	public function test_populate_callback_invalid_static_class_array() {
		global $wp_filter;

		add_action( 'qm/tests/' . __METHOD__, array( 'Invalid_Class', 'goodbye' ) );

		$actions = $wp_filter[ 'qm/tests/' . __METHOD__ ];
		$actual  = QM_Util::populate_callback( reset( $actions[10] ) );

		$this->assertTrue( is_wp_error( $actual['error'] ) );

	}

	public function test_populate_callback_invalid_static_class_string() {
		global $wp_filter;

		add_action( 'qm/tests/' . __METHOD__, 'Invalid_Class::goodbye' );

		$actions = $wp_filter[ 'qm/tests/' . __METHOD__ ];
		$actual  = QM_Util::populate_callback( reset( $actions[10] ) );

		$this->assertTrue( is_wp_error( $actual['error'] ) );

	}

}
