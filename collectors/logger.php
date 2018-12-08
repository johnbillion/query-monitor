<?php
/**
 * PSR-3 compatible logging collector.
 *
 * @package query-monitor
 */

class QM_Collector_Logger extends QM_Collector {

	public $id = 'logger';

	const EMERGENCY = 'emergency';
	const ALERT     = 'alert';
	const CRITICAL  = 'critical';
	const ERROR     = 'error';
	const WARNING   = 'warning';
	const NOTICE    = 'notice';
	const INFO      = 'info';
	const DEBUG     = 'debug';

	public function name() {
		return __( 'Logger', 'query-monitor' );
	}

	public function __construct() {
		parent::__construct();
		foreach ( $this->get_levels() as $level ) {
			add_action( "qm/{$level}", array( $this, $level ), 10, 2 );
		}

		add_action( 'qm/log', array( $this, 'log' ), 10, 3 );
	}

	public function emergency( $message, array $context = array() ) {
		$this->store( 'emergency', $message, $context );
	}

	public function alert( $message, array $context = array() ) {
		$this->store( 'alert', $message, $context );
	}

	public function critical( $message, array $context = array() ) {
		$this->store( 'critical', $message, $context );
	}

	public function error( $message, array $context = array() ) {
		$this->store( 'error', $message, $context );
	}

	public function warning( $message, array $context = array() ) {
		$this->store( 'warning', $message, $context );
	}

	public function notice( $message, array $context = array() ) {
		$this->store( 'notice', $message, $context );
	}

	public function info( $message, array $context = array() ) {
		$this->store( 'info', $message, $context );
	}

	public function debug( $message, array $context = array() ) {
		$this->store( 'debug', $message, $context );
	}

	public function log( $level, $message, array $context = array() ) {
		if ( ! in_array( $level, $this->get_levels(), true ) ) {
			throw new InvalidArgumentException( __( 'Unsupported log level', 'query-monitor' ) );
		}

		$this->store( $level, $message, $context );
	}

	protected function store( $level, $message, array $context = array() ) {
		$trace = new QM_Backtrace( array(
			'ignore_frames' => 2,
		) );

		if ( is_wp_error( $message ) ) {
			$message = sprintf(
				'%s (%s)',
				$message->get_error_message(),
				$message->get_error_code()
			);
		}

		if ( $message instanceof Exception ) {
			$message = $message->getMessage();
		}

		$this->data['logs'][] = array(
			'message' => $this->interpolate( $message, $context ),
			'context' => $context,
			'trace'   => $trace,
			'level'   => $level,
		);
	}

	protected function interpolate( $message, array $context = array() ) {
		// build a replacement array with braces around the context keys
		$replace = array();

		foreach ( $context as $key => $val ) {
			// check that the value can be casted to string
			if ( is_scalar( $val ) || ( is_object( $val ) && method_exists( $val, '__toString' ) ) ) {
				$replace[ "{{$key}}" ] = $val;
			}
		}

		// interpolate replacement values into the message and return
		return strtr( $message, $replace );
	}

	public function process() {
		if ( empty( $this->data['logs'] ) ) {
			return;
		}

		$components = array();

		foreach ( $this->data['logs'] as $row ) {
			$component                      = $row['trace']->get_component();
			$components[ $component->name ] = $component->name;
		}

		$this->data['components'] = $components;
	}

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
