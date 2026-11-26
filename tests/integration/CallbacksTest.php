<?php declare(strict_types = 1);

namespace QM\Tests;

class CallbacksTest extends Test {

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
		$actual = \QM_Util::determine_callback( $callback );

		self::assertSame( '__return_false()',   $actual->name );
		self::assertSame( $ref->getFileName(),  $actual->file );
		self::assertSame( $ref->getStartLine(), $actual->line );

	}

	public function testCallbackIsCorrectlyPopulatedWithObjectMethod(): void {

		$obj = new Supports\TestObject;
		$function = array( $obj, 'hello' );
		$callback = self::get_callback( $function );

		$ref = new \ReflectionMethod( $function[0], $function[1] );
		$actual = \QM_Util::determine_callback( $callback );

		self::assertSame( 'QM\Tests\Supports\TestObject->hello()', $actual->name );
		self::assertSame( $ref->getFileName(),       $actual->file );
		self::assertSame( $ref->getStartLine(),      $actual->line );

	}

	public function testCallbackIsCorrectlyPopulatedWithInvokable(): void {

		$function = new Supports\TestInvokable;
		$callback = self::get_callback( $function );

		$ref = new \ReflectionMethod( $function, '__invoke' );
		$actual = \QM_Util::determine_callback( $callback );
		$name = 'QM\Tests\Supports\TestInvokable->__invoke()';

		self::assertSame( $name,                $actual->name );
		self::assertSame( $ref->getFileName(),  $actual->file );
		self::assertSame( $ref->getStartLine(), $actual->line );

	}

	public function testCallbackIsCorrectlyPopulatedWithStaticMethodArray(): void {

		$function = array( '\QM\Tests\Supports\TestObject', 'hello' );
		$callback = self::get_callback( $function );

		$ref = new \ReflectionMethod( $function[0], $function[1] );
		$actual = \QM_Util::determine_callback( $callback );

		self::assertSame( '\QM\Tests\Supports\TestObject::hello()', $actual->name );
		self::assertSame( $ref->getFileName(),       $actual->file );
		self::assertSame( $ref->getStartLine(),      $actual->line );

	}

	public function testCallbackIsCorrectlyPopulatedWithStaticMethodString(): void {

		$function = '\QM\Tests\Supports\TestObject::hello';
		$callback = self::get_callback( $function );

		$ref = new \ReflectionMethod( '\QM\Tests\Supports\TestObject', 'hello' );
		$actual = \QM_Util::determine_callback( $callback );

		self::assertSame( '\QM\Tests\Supports\TestObject::hello()', $actual->name );
		self::assertSame( $ref->getFileName(),                $actual->file );
		self::assertSame( $ref->getStartLine(),               $actual->line );

	}

	public function testCallbackIsCorrectlyPopulatedWithClosure(): void {

		$function = require_once __DIR__ . '/includes/dummy-closures.php';

		$callback = self::get_callback( $function );

		$ref = new \ReflectionFunction( $function );
		$actual = \QM_Util::determine_callback( $callback );

		self::assertSame( 'closure', $actual->callback_type );
		self::assertNotNull( $actual->file );
		self::assertSame( $ref->getFileName(), $actual->file );
		self::assertSame( $ref->getStartLine(), $actual->line );

	}

	// `Closure::fromCallable()` creates the same closures as PHP 8.1's `$obj->method(...)` syntax.
	public function testCallbackIsCorrectlyPopulatedWithFirstClassCallableObjectMethod(): void {

		$obj = new Supports\TestObject;
		$function = \Closure::fromCallable( array( $obj, 'hello' ) );
		$callback = self::get_callback( $function );

		$ref = new \ReflectionMethod( $obj, 'hello' );
		$actual = \QM_Util::determine_callback( $callback );

		self::assertSame( 'method', $actual->callback_type );
		self::assertSame( 'QM\Tests\Supports\TestObject->hello()', $actual->name );
		self::assertSame( $ref->getFileName(), $actual->file );
		self::assertSame( $ref->getStartLine(), $actual->line );

	}

	public function testCallbackIsCorrectlyPopulatedWithFirstClassCallableStaticMethod(): void {

		$function = \Closure::fromCallable( array( Supports\TestObject::class, 'greet' ) );
		$callback = self::get_callback( $function );

		$ref = new \ReflectionMethod( Supports\TestObject::class, 'greet' );
		$actual = \QM_Util::determine_callback( $callback );

		self::assertSame( 'static_method', $actual->callback_type );
		self::assertSame( 'QM\Tests\Supports\TestObject::greet()', $actual->name );
		self::assertSame( $ref->getFileName(), $actual->file );
		self::assertSame( $ref->getStartLine(), $actual->line );

	}

	public function testCallbackIsCorrectlyPopulatedWithFirstClassCallableProceduralFunction(): void {

		$function = \Closure::fromCallable( '__return_false' );
		$callback = self::get_callback( $function );

		$ref = new \ReflectionFunction( '__return_false' );
		$actual = \QM_Util::determine_callback( $callback );

		self::assertSame( 'function', $actual->callback_type );
		self::assertSame( '__return_false()', $actual->name );
		self::assertSame( $ref->getFileName(), $actual->file );
		self::assertSame( $ref->getStartLine(), $actual->line );

	}

	public function testCallbackIsCorrectlyPopulatedWithFirstClassCallableInternalFunction(): void {

		$function = \Closure::fromCallable( 'strlen' );
		$callback = self::get_callback( $function );

		$actual = \QM_Util::determine_callback( $callback );

		self::assertSame( 'function', $actual->callback_type );
		self::assertSame( 'strlen()', $actual->name );
		self::assertNotNull( $actual->component );
		self::assertSame( 'php', $actual->component->context );

	}

	public function testCallbackIsCorrectlyPopulatedWithFirstClassCallableMagicMethod(): void {

		$obj = new /** @method void hello() */ class() {
			/**
			 * @param mixed[] $args
			 */
			public function __call( string $name, array $args ): void {}
		};
		$function = \Closure::fromCallable( array( $obj, 'hello' ) );
		$callback = self::get_callback( $function );

		$actual = \QM_Util::determine_callback( $callback );

		self::assertSame( 'unknown_closure', $actual->callback_type );

	}

	public function testCallbackIsCorrectlyPopulatedWithClosureDefinedInMethod(): void {

		$function = function (): void {};
		$callback = self::get_callback( $function );

		$ref = new \ReflectionFunction( $function );
		$actual = \QM_Util::determine_callback( $callback );

		self::assertSame( 'closure', $actual->callback_type );
		self::assertSame( $ref->getFileName(), $actual->file );
		self::assertSame( $ref->getStartLine(), $actual->line );

	}

	public function testCallbackIsCorrectlyPopulatedWithInvalidProceduralFunction(): void {

		$function = 'invalid_function';
		$callback = self::get_callback( $function );

		$actual = \QM_Util::determine_callback( $callback );

		self::assertSame( 'unknown', $actual->callback_type );

	}

	public function testCallbackIsCorrectlyPopulatedWithInvalidObjectMethod(): void {

		$obj = new Supports\TestObject;
		$function = array( $obj, 'goodbye' );
		$callback = self::get_callback( $function );

		$actual = \QM_Util::determine_callback( $callback );

		self::assertSame( 'unknown', $actual->callback_type );

	}

	public function testCallbackIsCorrectlyPopulatedWithInvalidInvokable(): void {

		$function = new Supports\TestObject;
		$callback = self::get_callback( $function );

		$actual = \QM_Util::determine_callback( $callback );

		self::assertSame( 'unknown', $actual->callback_type );

	}

	public function testCallbackIsCorrectlyPopulatedWithInvalidStaticMethodArray(): void {

		$function = array( '\QM\Tests\Supports\TestObject', 'goodbye' );
		$callback = self::get_callback( $function );

		$actual = \QM_Util::determine_callback( $callback );

		self::assertSame( 'unknown', $actual->callback_type );

	}

	public function testCallbackIsCorrectlyPopulatedWithInvalidStaticMethodString(): void {

		$function = '\QM\Tests\Supports\TestObject::goodbye';
		$callback = self::get_callback( $function );

		$actual = \QM_Util::determine_callback( $callback );

		self::assertSame( 'unknown', $actual->callback_type );

	}

	public function testCallbackIsCorrectlyPopulatedWithInvalidStaticClassArray(): void {

		$function = array( 'Invalid_Class', 'goodbye' );
		$callback = self::get_callback( $function );

		$actual = \QM_Util::determine_callback( $callback );

		self::assertSame( 'unknown', $actual->callback_type );

	}

	public function testCallbackIsCorrectlyPopulatedWithInvalidStaticClassString(): void {

		$function = 'Invalid_Class::goodbye';
		$callback = self::get_callback( $function );

		$actual = \QM_Util::determine_callback( $callback );

		self::assertSame( 'unknown', $actual->callback_type );

	}

	public function testCallbackIsCorrectlyPopulatedWithClosureFrameFromPhp84(): void {

		$actual = \QM_Util::determine_callback( array(
			'function' => '{closure:QM\Tests\Supports\TestObject::hello():12}',
			'class' => 'QM\Tests\Supports\TestObject',
		) );

		self::assertSame( 'unknown', $actual->callback_type );

	}

	/**
	 * @requires PHP >= 8.4
	 */
	public function testCallbackIsCorrectlyPopulatedWithRealClosureFrameFromPhp84(): void {

		$closure = function (): void {
			throw new \Exception( 'Testing' );
		};

		try {
			$closure();
		} catch ( \Exception $e ) {
			$frame = $e->getTrace()[0];
		}

		self::assertStringStartsWith( '{closure:' . __METHOD__ . '():', $frame['function'] );

		$actual = \QM_Util::determine_callback( $frame );

		self::assertSame( 'unknown', $actual->callback_type );
		self::assertSame( __CLASS__ . '::' . $frame['function'] . '()', $actual->name );

	}

}
