<?php
/**
 * PSR-3 compatible logging collector.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QM_Collector_Logger extends QM_Collector {

	public $id = 'logger';

	const EMERGENCY = 'emergency';
	const ALERT = 'alert';
	const CRITICAL = 'critical';
	const ERROR = 'error';
	const WARNING = 'warning';
	const NOTICE = 'notice';
	const INFO = 'info';
	const DEBUG = 'debug';

	/**
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		$this->data['counts'] = array_fill_keys( $this->get_levels(), 0 );

		foreach ( $this->get_levels() as $level ) {
			add_action( "qm/{$level}", array( $this, $level ), 10, 2 );
		}

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
	 * @return void
	 */
	public function emergency( $message, array $context = array() ) {
		$this->store( self::EMERGENCY, $message, $context );
	}

	/**
	 * @param mixed $message
	 * @param array<string, mixed> $context
	 * @return void
	 */
	public function alert( $message, array $context = array() ) {
		$this->store( self::ALERT, $message, $context );
	}

	/**
	 * @param mixed $message
	 * @param array<string, mixed> $context
	 * @return void
	 */
	public function critical( $message, array $context = array() ) {
		$this->store( self::CRITICAL, $message, $context );
	}

	/**
	 * @param mixed $message
	 * @param array<string, mixed> $context
	 * @return void
	 */
	public function error( $message, array $context = array() ) {
		$this->store( self::ERROR, $message, $context );
	}

	/**
	 * @param mixed $message
	 * @param array<string, mixed> $context
	 * @return void
	 */
	public function warning( $message, array $context = array() ) {
		$this->store( self::WARNING, $message, $context );
	}

	/**
	 * @param mixed $message
	 * @param array<string, mixed> $context
	 * @return void
	 */
	public function notice( $message, array $context = array() ) {
		$this->store( self::NOTICE, $message, $context );
	}

	/**
	 * @param mixed $message
	 * @param array<string, mixed> $context
	 * @return void
	 */
	public function info( $message, array $context = array() ) {
		$this->store( self::INFO, $message, $context );
	}

	/**
	 * @param mixed $message
	 * @param array<string, mixed> $context
	 * @return void
	 */
	public function debug( $message, array $context = array() ) {
		$this->store( self::DEBUG, $message, $context );
	}

	/**
	 * @param string $level
	 * @param mixed $message
	 * @param array<string, mixed> $context
	 * @phpstan-param self::* $level
	 * @return void
	 */
	public function log( $level, $message, array $context = array() ) {
		if ( ! in_array( $level, $this->get_levels(), true ) ) {
			throw new InvalidArgumentException( __( 'Unsupported log level', 'query-monitor' ) );
		}

		$this->store( $level, $message, $context );
	}

	/**
	 * @param string $level
	 * @param mixed $message
	 * @param array<string, mixed> $context
	 * @phpstan-param self::* $level
	 * @return void
	 */
	protected function store( $level, $message, array $context = array() ) {
		$trace = new QM_Backtrace( array(
			'ignore_hook' => array(
				current_filter() => true,
			),
		) );

		if ( is_wp_error( $message ) ) {
			$message = sprintf(
				'WP_Error: %s (%s)',
				$message->get_error_message(),
				$message->get_error_code()
			);
		}

		if ( ( $message instanceof Exception ) || ( $message instanceof Throwable ) ) {
			$message = sprintf(
				'%1$s: %2$s',
				get_class( $message ),
				$message->getMessage()
			);
		}

		if ( ! QM_Util::is_stringy( $message ) ) {
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

		$this->data['counts'][ $level ]++;
		$this->data['logs'][] = array(
			'message' => self::interpolate( $message, $context ),
			'filtered_trace' => $trace->get_filtered_trace(),
			'component' => $trace->get_component(),
			'level' => $level,
		);
	}

	/**
	 * @param string $message
	 * @param array<string, mixed> $context
	 * @return string
	 */
	protected static function interpolate( $message, array $context = array() ) {
		// build a replacement array with braces around the context keys
		$replace = array();

		foreach ( $context as $key => $val ) {
			// check that the value can be casted to string
			if ( is_bool( $val ) ) {
				$replace[ "{{$key}}" ] = ( $val ? 'true' : 'false' );
			} elseif ( is_scalar( $val ) || QM_Util::is_stringy( $val ) ) {
				$replace[ "{{$key}}" ] = $val;
			}
		}

		// interpolate replacement values into the message and return
		return strtr( $message, $replace );
	}

	/**
	 * @return void
	 */
	public function process() {
		if ( empty( $this->data['logs'] ) ) {
			return;
		}

		$components = array();

		foreach ( $this->data['logs'] as $row ) {
			$component = $row['component'];
			$components[ $component->name ] = $component->name;
		}

		$this->data['components'] = $components;
	}

	/**
	 * @return array<int, string>
	 * @phpstan-return array<int, self::*>
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
	 * @phpstan-return array<int, self::*>
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
