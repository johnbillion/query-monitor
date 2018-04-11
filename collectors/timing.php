<?php
/**
 * Timing and profiling collector.
 *
 * @package query-monitor
 */

class QM_Collector_Timing extends QM_Collector {

	public $id = 'timing';
	private $track_timer = array();
	private $start = array();
	private $stop = array();
	private $laps = array();

	public function name() {
		return __( 'Timing', 'query-monitor' );
	}

	public function __construct() {
		parent::__construct();
		add_action( 'qm/start', array( $this, 'action_function_time_start' ), 10, 1 );
		add_action( 'qm/stop',  array( $this, 'action_function_time_stop' ), 10, 1 );
		add_action( 'qm/lap',   array( $this, 'action_function_time_lap' ), 10, 2 );
	}

	public function action_function_time_start( $function ) {
		$this->track_timer[ $function ] = new QM_Timer;
		$this->start[ $function ] = $this->track_timer[ $function ]->start();
	}

	public function action_function_time_stop( $function ) {
		if ( ! isset( $this->track_timer[ $function ] ) ) {
			$trace = new QM_Backtrace;
			$this->data['warning'][] = array(
				'function'  => $function,
				'message'   => __( 'Timer not started', 'query-monitor' ),
				'trace'     => $trace,
			);
			return;
		}
		$this->stop[ $function ] = $this->track_timer[ $function ]->stop();
		$this->calculate_time( $function );
	}

	public function action_function_time_lap( $function, $name = null ) {
		if ( ! isset( $this->track_timer[ $function ] ) ) {
			$trace = new QM_Backtrace;
			$this->data['warning'][] = array(
				'function'  => $function,
				'message'   => __( 'Timer not started', 'query-monitor' ),
				'trace'     => $trace,
			);
			return;
		}
		$this->track_timer[ $function ]->lap( $name );
	}

	public function calculate_time( $function ) {
		$trace           = $this->track_timer[ $function ]->get_trace();
		$function_time   = $this->track_timer[ $function ]->get_time();
		$function_memory = $this->track_timer[ $function ]->get_memory();
		$function_laps   = $this->track_timer[ $function ]->get_laps();

		$this->data['timing'][] = array(
			'function'        => $function,
			'function_time'   => $function_time,
			'function_memory' => $function_memory,
			'laps'            => $function_laps,
			'trace'           => $trace,
		);
	}

	public function process() {
		foreach ( $this->start as $function => $value ) {
			if ( ! isset( $this->stop[ $function ] ) ) {
				$trace = $this->track_timer[ $function ]->get_trace();
				$this->data['warning'][] = array(
					'function'  => $function,
					'message'   => __( 'Timer not stopped', 'query-monitor' ),
					'trace'     => $trace,
				);
			}
		}
	}

}

# Load early in case a plugin is setting the function to be checked when it initialises instead of after the `plugins_loaded` hook
QM_Collectors::add( new QM_Collector_Timing );
