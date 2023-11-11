<?php declare(strict_types = 1);
/**
 * PSR-3 compatible logging collector.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @extends QM_DataCollector<QM_Data_Logger>
 * @phpstan-type LogMessage WP_Error|Throwable|string|bool|null
 */
class QM_Collector_Logger extends QM_DataCollector {

	public $id = 'logger';

	public const EMERGENCY = 'emergency';
	public const ALERT = 'alert';
	public const CRITICAL = 'critical';
	public const ERROR = 'error';
	public const WARNING = 'warning';
	public const NOTICE = 'notice';
	public const INFO = 'info';
	public const DEBUG = 'debug';

	public function get_storage(): QM_Data {
		return new QM_Data_Logger();
	}

	/**
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		$this->data->counts = array_fill_keys( $this->get_levels(), 0 );

		foreach ( $this->get_levels() as $level ) {
			add_action( "qm/{$level}", array( $this, $level ), 10, 2 );
		}

		add_action( 'qm/assert', array( $this, 'assert' ), 10, 3 );
		add_action( 'qm/log', array( $this, 'log' ), 10, 3 );
	}

	/**
	 * @return void
	 */
	public function tear_down() {
		foreach ( $this->get_levels() as $level ) {
			remove_action( "qm/{$level}", array( $this, $level ), 10 );
		}

		remove_action( 'qm/log', array( $this, 'log' ), 10 );

		parent::tear_down();
	}

	/**
	 * @param mixed $message
	 * @param array<string, mixed> $context
	 * @phpstan-param LogMessage $message
	 * @return void
	 */
	public function emergency( $message, array $context = array() ) {
		$this->store( self::EMERGENCY, $message, $context );
	}

	/**
	 * @param mixed $message
	 * @param array<string, mixed> $context
	 * @phpstan-param LogMessage $message
	 * @return void
	 */
	public function alert( $message, array $context = array() ) {
		$this->store( self::ALERT, $message, $context );
	}

	/**
	 * @param mixed $message
	 * @param array<string, mixed> $context
	 * @phpstan-param LogMessage $message
	 * @return void
	 */
	public function critical( $message, array $context = array() ) {
		$this->store( self::CRITICAL, $message, $context );
	}

	/**
	 * @param mixed $message
	 * @param array<string, mixed> $context
	 * @phpstan-param LogMessage $message
	 * @return void
	 */
	public function error( $message, array $context = array() ) {
		$this->store( self::ERROR, $message, $context );
	}

	/**
	 * @param mixed $message
	 * @param array<string, mixed> $context
	 * @phpstan-param LogMessage $message
	 * @return void
	 */
	public function warning( $message, array $context = array() ) {
		$this->store( self::WARNING, $message, $context );
	}

	/**
	 * @param mixed $message
	 * @param array<string, mixed> $context
	 * @phpstan-param LogMessage $message
	 * @return void
	 */
	public function notice( $message, array $context = array() ) {
		$this->store( self::NOTICE, $message, $context );
	}

	/**
	 * @param mixed $message
	 * @param array<string, mixed> $context
	 * @phpstan-param LogMessage $message
	 * @return void
	 */
	public function info( $message, array $context = array() ) {
		$this->store( self::INFO, $message, $context );
	}

	/**
	 * @param mixed $message
	 * @param array<string, mixed> $context
	 * @phpstan-param LogMessage $message
	 * @return void
	 */
	public function debug( $message, array $context = array() ) {
		$this->store( self::DEBUG, $message, $context );
	}

	/**
	 * @param mixed $assertion
	 * @param string $message
	 * @param ?mixed $value
	 * @return void
	 */
	public function assert( $assertion, string $message = '', $value = null ) {
		$prefix = null;

		if ( $assertion ) {
			$level = self::DEBUG;

			if ( $message ) {
				$message = sprintf(
					/* translators: %s: Assertion message */
					__( 'Assertion passed: %s', 'query-monitor' ),
					$message
				);
			} else {
				$message = __( 'Assertion passed', 'query-monitor' );
			}
		} else {
			$level = self::ERROR;

			if ( $message ) {
				$message = sprintf(
					/* translators: %s: Assertion message */
					__( 'Assertion failed: %s', 'query-monitor' ),
					$message
				);
			} else {
				$message = __( 'Assertion failed', 'query-monitor' );
			}

			if ( $value !== null ) {
				$prefix = $message;
				$message = $value;
			}
		}

		$this->store( $level, $message, array(), $prefix );
	}

	/**
	 * @param string $level
	 * @param mixed $message
	 * @param array<string, mixed> $context
	 * @phpstan-param self::* $level
	 * @phpstan-param LogMessage $message
	 * @return void
	 */
	public function log( $level, $message, array $context = array() ) {
		if ( ! in_array( $level, $this->get_levels(), true ) ) {
			throw new InvalidArgumentException( 'Unsupported log level' );
		}

		$this->store( $level, $message, $context );
	}

	/**
	 * @param string $level
	 * @param mixed $message
	 * @param array<string, mixed> $context
	 * @param ?string $prefix
	 * @phpstan-param self::* $level
	 * @phpstan-param LogMessage $message
	 * @return void
	 */
	protected function store( $level, $message, array $context = array(), string $prefix = null ) {
		$trace = new QM_Backtrace( array(
			'ignore_hook' => array(
				current_filter() => true,
			),
		) );

		if ( $message instanceof WP_Error ) {
			$message = sprintf(
				'WP_Error: %s (%s)',
				$message->get_error_message(),
				$message->get_error_code()
			);
		}

		if ( $message instanceof Throwable ) {
			$message = sprintf(
				'%1$s: %2$s',
				get_class( $message ),
				$message->getMessage()
			);
		}

		if ( ! is_string( $message ) ) {
			if ( null === $message ) {
				$message = 'null';
			} elseif ( false === $message ) {
				$message = 'false';
			} elseif ( true === $message ) {
				$message = 'true';
			}

			$message = print_r( $message, true );
		} elseif ( '' === trim( $message ) ) {
			$message = '(Empty string)';
		}

		$this->data->counts[ $level ]++;
		$this->data->logs[] = array(
			'message' => self::interpolate( $message, $context, $prefix ),
			'filtered_trace' => $trace->get_filtered_trace(),
			'component' => $trace->get_component(),
			'level' => $level,
		);
	}

	/**
	 * @param string $message
	 * @param array<string, mixed> $context
	 * @param ?string $prefix
	 * @return string
	 */
	protected static function interpolate( $message, array $context = array(), string $prefix = null ) {
		// build a replacement array with braces around the context keys
		$replace = array();

		foreach ( $context as $key => $val ) {
			// check that the value can be casted to string
			if ( is_bool( $val ) ) {
				$replace[ "{{$key}}" ] = ( $val ? 'true' : 'false' );
			} elseif ( is_scalar( $val ) ) {
				$replace[ "{{$key}}" ] = $val;
			} elseif ( is_object( $val ) ) {
				$replace[ "{{$key}}" ] = sprintf( '[%s]', get_class( $val ) );
			} else {
				$replace[ "{{$key}}" ] = sprintf( '[%s]', gettype( $val ) );
			}
		}

		// interpolate replacement values into the message and return
		$message = strtr( $message, $replace );

		if ( $prefix !== null ) {
			$message = $prefix . "\n" . $message;
		}

		return $message;
	}

	/**
	 * @return void
	 */
	public function process() {
		if ( empty( $this->data->logs ) ) {
			return;
		}

		$components = array();

		foreach ( $this->data->logs as $row ) {
			$component = $row['component'];
			$components[ $component->name ] = $component->name;
		}

		$this->data->components = $components;
	}

	/**
	 * @return array<int, string>
	 * @phpstan-return list<self::*>
	 */
	public function get_levels() {
		return array(
			self::EMERGENCY,
			self::ALERT,
			self::CRITICAL,
			self::ERROR,
			self::WARNING,
			self::NOTICE,
			self::INFO,
			self::DEBUG,
		);
	}

	/**
	 * @return array<int, string>
	 * @phpstan-return list<self::*>
	 */
	public function get_warning_levels() {
		return array(
			self::EMERGENCY,
			self::ALERT,
			self::CRITICAL,
			self::ERROR,
			self::WARNING,
		);
	}

}

# Load early in case a plugin wants to log a message early in the bootstrap process
QM_Collectors::add( new QM_Collector_Logger() );
