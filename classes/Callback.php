<?php declare(strict_types = 1);
/**
 * Class that represents a valid callback.
 *
 * @package query-monitor
 */

final class QM_Callback {
	/**
	 * @var string
	 */
	public $name;

	/**
	 * @var ?string
	 */
	public $file;

	/**
	 * @var ?int
	 */
	public $line;

	/**
	 * @var QM_Component
	 */
	public $component;

	/**
	 * Create an instance from a callback that may or may not be a valid callable.
	 *
	 * @param mixed $callback
	 * @throws QM_CallbackException
	 */
	public static function from_callable( $callback ): self {
		if ( is_string( $callback ) && ( false !== strpos( $callback, '::' ) ) ) {
			// Static class method:
			$callback = explode( '::', $callback );
		}

		if ( is_array( $callback ) ) {
			return self::from_method( $callback );
		}

		if ( $callback instanceof Closure ) {
			return self::from_closure( $callback );
		}

		if ( is_object( $callback ) ) {
			return self::from_invokable( $callback );
		}

		if ( is_string( $callback ) ) {
			return self::from_function( $callback );
		}

		throw ( new QM_CallbackException( 'Invalid callback' ) )->add_data( (string) $callback );
	}

	/**
	 * @param array $callback
	 * @phpstan-param array{
	 *   0: string|object,
	 *   1: string,
	 * } $callback
	 * @throws QM_CallbackException
	 */
	protected static function from_method( array $callback ): self {
		// Static class method:
		$class = $callback[0];
		$access = '::';

		if ( is_object( $class ) ) {
			// Class instance method
			$class = get_class( $class );
			$access = '->';
		}

		$name = QM_Util::shorten_fqn( $class . $access . $callback[1] ) . '()';

		try {
			$ref = new ReflectionMethod( $callback[0], $callback[1] );
		} catch ( ReflectionException $e ) {
			throw ( new QM_CallbackException( $e->getMessage(), $e->getCode(), $e ) )->add_data( $name );
		}

		return self::init( $name, $ref );
	}

	/**
	 * @param Closure $callback
	 * @throws QM_CallbackException
	 */
	protected static function from_closure( Closure $callback ): self {
		$name = '{closure}';

		try {
			$ref = new ReflectionFunction( $callback );
		} catch ( ReflectionException $e ) {
			throw ( new QM_CallbackException( $e->getMessage(), $e->getCode(), $e ) )->add_data( $name );
		}

		$filename = $ref->getFileName();

		if ( $filename ) {
			$file = QM_Util::standard_dir( $filename, '' );
			if ( 0 === strpos( $file, '/' ) ) {
				$file = basename( $filename );
			}
			$name = sprintf(
				/* translators: A closure is an anonymous PHP function. 1: Line number, 2: File name */
				__( 'Closure on line %1$d of %2$s', 'query-monitor' ),
				$ref->getStartLine(),
				$file
			);
		}

		return self::init( $name, $ref );
	}

	/**
	 * @param object $callback
	 * @throws QM_CallbackException
	 */
	protected static function from_invokable( object $callback ): self {
		$class = get_class( $callback );
		$name = QM_Util::shorten_fqn( $class ) . '->__invoke()';

		try {
			$ref = new ReflectionMethod( $class, '__invoke' );
		} catch ( ReflectionException $e ) {
			throw ( new QM_CallbackException( $e->getMessage(), $e->getCode(), $e ) )->add_data( $name );
		}

		return self::init( $name, $ref );
	}

	/**
	 * @param string $callback
	 * @throws QM_CallbackException
	 */
	protected static function from_function( string $callback ): self {
		$name = QM_Util::shorten_fqn( $callback ) . '()';

		try {
			$ref = new ReflectionFunction( $callback );
		} catch ( ReflectionException $e ) {
			throw ( new QM_CallbackException( $e->getMessage(), $e->getCode(), $e ) )->add_data( $name );
		}

		$func = self::init( $name, $ref );

		if ( $func->file && '__lambda_func' === $ref->getName() ) {
			preg_match( '#(?P<file>.*)\((?P<line>[0-9]+)\)#', $func->file, $matches );

			$func->file = $matches['file'];
			$func->line = (int) $matches['line'];
		}

		return $func;
	}

	protected static function init( string $name, ReflectionFunctionAbstract $ref ): self {
		$callback = new self();

		$callback->name = $name;
		$callback->file = $ref->getFileName() ?: null;
		$callback->line = $ref->getStartLine() ?: null;

		if ( $callback->file ) {
			$callback->component = QM_Util::get_file_component( $callback->file );
		} else {
			$callback->component = new QM_Component();
			$callback->component->type = 'php';
			$callback->component->name = 'PHP';
			$callback->component->context = '';
		}

		return $callback;
	}

	protected function __construct() {}
}
