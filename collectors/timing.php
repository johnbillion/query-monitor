<?php declare(strict_types = 1);
/**
 * Timing and profiling collector.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @extends QM_DataCollector<QM_Data_Timing>
 */
class QM_Collector_Timing extends QM_DataCollector {

	/**
	 * @var string
	 */
	public $id = 'timing';

	/**
	 * @var array<string, QM_Timer>
	 */
	private $track_timer = array();

	/**
	 * @var array<string, QM_Timer>
	 */
	private $start = array();

	/**
	 * @var array<string, QM_Timer>
	 */
	private $stop = array();

	public function get_storage(): QM_Data {
		return new QM_Data_Timing();
	}

	/**
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		add_action( 'qm/start', array( $this, 'action_function_time_start' ), 10, 1 );
		add_action( 'qm/stop', array( $this, 'action_function_time_stop' ), 10, 1 );
		add_action( 'qm/lap', array( $this, 'action_function_time_lap' ), 10, 2 );
	}

	/**
	 * @return void
	 */
	public function tear_down() {
		remove_action( 'qm/start', array( $this, 'action_function_time_start' ), 10 );
		remove_action( 'qm/stop', array( $this, 'action_function_time_stop' ), 10 );
		remove_action( 'qm/lap', array( $this, 'action_function_time_lap' ), 10 );

		parent::tear_down();
	}

	/**
	 * @param string $function
	 * @return void
	 */
	public function action_function_time_start( $function ) {
		$this->track_timer[ $function ] = new QM_Timer();
		$this->start[ $function ] = $this->track_timer[ $function ]->start();
	}

	/**
	 * @param string $function
	 * @return void
	 */
	public function action_function_time_stop( $function ) {
		if ( ! isset( $this->track_timer[ $function ] ) ) {
			$trace = new QM_Backtrace();
			$this->data->warning[] = array(
				'function' => $function,
				'message' => __( 'Timer not started', 'query-monitor' ),
				'filtered_trace' => $trace->get_filtered_trace(),
				'component' => $trace->get_component(),
			);
			return;
		}
		$this->stop[ $function ] = $this->track_timer[ $function ]->stop();
		$this->calculate_time( $function );
	}

	/**
	 * @param string $function
	 * @param string $name
	 * @return void
	 */
	public function action_function_time_lap( $function, $name = null ) {
		if ( ! isset( $this->track_timer[ $function ] ) ) {
			$trace = new QM_Backtrace();
			$this->data->warning[] = array(
				'function' => $function,
				'message' => __( 'Timer not started', 'query-monitor' ),
				'filtered_trace' => $trace->get_filtered_trace(),
				'component' => $trace->get_component(),
			);
			return;
		}
		$this->track_timer[ $function ]->lap( array(), $name );
	}

	/**
	 * @param string $function
	 * @return void
	 */
	public function calculate_time( $function ) {
		$trace = $this->track_timer[ $function ]->get_trace();
		$function_time = $this->track_timer[ $function ]->get_time();
		$function_memory = $this->track_timer[ $function ]->get_memory();
		$function_laps = $this->track_timer[ $function ]->get_laps();
		$start_time = $this->track_timer[ $function ]->get_start_time();
		$end_time = $this->track_timer[ $function ]->get_end_time();

		$this->data->timing[] = array(
			'function' => $function,
			'function_time' => $function_time,
			'function_memory' => $function_memory,
			'laps' => $function_laps,
			'filtered_trace' => $trace->get_filtered_trace(),
			'component' => $trace->get_component(),
			'start_time' => ( $start_time - $_SERVER['REQUEST_TIME_FLOAT'] ),
			'end_time' => ( $end_time - $_SERVER['REQUEST_TIME_FLOAT'] ),
		);
	}

	/**
	 * @return void
	 */
	public function process() {
		foreach ( $this->start as $function => $value ) {
			if ( ! isset( $this->stop[ $function ] ) ) {
				$trace = $this->track_timer[ $function ]->get_trace();
				$this->data->warning[] = array(
					'function' => $function,
					'message' => __( 'Timer not stopped', 'query-monitor' ),
					'filtered_trace' => $trace->get_filtered_trace(),
					'component' => $trace->get_component(),
				);
			}
		}

		if ( ! empty( $this->data->timing ) ) {
			usort( $this->data->timing, array( $this, 'sort_by_start_time' ) );
		}
	}

	/**
	 * @param mixed[] $a
	 * @param mixed[] $b
	 * @return int
	 * @phpstan-return -1|0|1
	 */
	public function sort_by_start_time( array $a, array $b ) {
		return $a['start_time'] <=> $b['start_time'];
	}

}

# Load early in case a plugin is setting the function to be checked when it initialises instead of after the `plugins_loaded` hook
QM_Collectors::add( new QM_Collector_Timing() );
