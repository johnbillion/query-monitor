<?php
/**
 * Timing and profiling collector.
 *
 * @package query-monitor
 */

class QM_Collector_Timing extends QM_Collector {

	public $id           = 'timing';
	private $track_timer = array();
	private $start       = array();
	private $stop        = array();

	public function __construct() {
		parent::__construct();
		add_action( 'qm/start', array( $this, 'action_function_time_start' ), 10, 1 );
		add_action( 'qm/stop',  array( $this, 'action_function_time_stop' ), 10, 1 );
		add_action( 'qm/lap',   array( $this, 'action_function_time_lap' ), 10, 2 );
	}

	public function action_function_time_start( $function ) {
		$this->track_timer[ $function ] = new QM_Timer();
		$this->start[ $function ]       = $this->track_timer[ $function ]->start();
	}

	public function action_function_time_stop( $function ) {
		if ( ! isset( $this->track_timer[ $function ] ) ) {
			$trace                   = new QM_Backtrace();
			$this->data['warning'][] = array(
				'function' => $function,
				'message'  => __( 'Timer not started', 'query-monitor' ),
				'trace'    => $trace,
			);
			return;
		}
		$this->stop[ $function ] = $this->track_timer[ $function ]->stop();
		$this->calculate_time( $function );
	}

	public function action_function_time_lap( $function, $name = null ) {
		if ( ! isset( $this->track_timer[ $function ] ) ) {
			$trace                   = new QM_Backtrace();
			$this->data['warning'][] = array(
				'function' => $function,
				'message'  => __( 'Timer not started', 'query-monitor' ),
				'trace'    => $trace,
			);
			return;
		}
		$this->track_timer[ $function ]->lap( array(), $name );
	}

	public function calculate_time( $function ) {
		$trace           = $this->track_timer[ $function ]->get_trace();
		$function_time   = $this->track_timer[ $function ]->get_time();
		$function_memory = $this->track_timer[ $function ]->get_memory();
		$function_laps   = $this->track_timer[ $function ]->get_laps();
		$start_time      = $this->track_timer[ $function ]->get_start_time();
		$end_time        = $this->track_timer[ $function ]->get_end_time();

		$this->data['timing'][] = array(
			'function'        => $function,
			'function_time'   => $function_time,
			'function_memory' => $function_memory,
			'laps'            => $function_laps,
			'trace'           => $trace,
			'start_time'      => ( $start_time - $GLOBALS['timestart'] ),
			'end_time'        => ( $end_time - $GLOBALS['timestart'] ),
		);
	}

	public function process() {
		foreach ( $this->start as $function => $value ) {
			if ( ! isset( $this->stop[ $function ] ) ) {
				$trace                   = $this->track_timer[ $function ]->get_trace();
				$this->data['warning'][] = array(
					'function' => $function,
					'message'  => __( 'Timer not stopped', 'query-monitor' ),
					'trace'    => $trace,
				);
			}
		}

		if ( ! empty( $this->data['timing'] ) ) {
			usort( $this->data['timing'], array( $this, 'sort_by_start_time' ) );
		}
	}

	public function sort_by_start_time( array $a, array $b ) {
		if ( $a['start_time'] === $b['start_time'] ) {
			return 0;
		} else {
			return ( $a['start_time'] > $b['start_time'] ) ? 1 : -1;
		}
	}

}

# Load early in case a plugin is setting the function to be checked when it initialises instead of after the `plugins_loaded` hook
QM_Collectors::add( new QM_Collector_Timing() );
