<?php
/**
 * General overview collector.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QM_Collector_Overview extends QM_Collector {

	public $id = 'overview';

	/**
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		add_action( 'shutdown', array( $this, 'process_timing' ), 0 );
	}

	/**
	 * @return void
	 */
	public function tear_down() {
		remove_action( 'shutdown', array( $this, 'process_timing' ), 0 );

		parent::tear_down();
	}

	/**
	 * Processes the timing and memory related stats as early as possible, so the
	 * data isn't skewed by collectors that are processed before this one.
	 *
	 * @return void
	 */
	public function process_timing() {
		$this->data['time_taken'] = self::timer_stop_float();

		if ( function_exists( 'memory_get_peak_usage' ) ) {
			$this->data['memory'] = memory_get_peak_usage();
		} elseif ( function_exists( 'memory_get_usage' ) ) {
			$this->data['memory'] = memory_get_usage();
		} else {
			$this->data['memory'] = 0;
		}
	}

	/**
	 * @return void
	 */
	public function process() {
		if ( ! isset( $this->data['time_taken'] ) ) {
			$this->process_timing();
		}

		$this->data['time_limit'] = (int) ini_get( 'max_execution_time' );
		$this->data['time_start'] = $GLOBALS['timestart'];

		if ( ! empty( $this->data['time_limit'] ) ) {
			$this->data['time_usage'] = ( 100 / $this->data['time_limit'] ) * $this->data['time_taken'];
		} else {
			$this->data['time_usage'] = 0;
		}

		if ( is_user_logged_in() ) {
			$this->data['current_user'] = self::format_user( wp_get_current_user() );
		} else {
			$this->data['current_user'] = null;
		}

		if ( function_exists( 'current_user_switched' ) && current_user_switched() ) {
			$this->data['switched_user'] = self::format_user( current_user_switched() );
		} else {
			$this->data['switched_user'] = null;
		}

		$this->data['memory_limit'] = QM_Util::convert_hr_to_bytes( ini_get( 'memory_limit' ) );

		if ( $this->data['memory_limit'] > 0 ) {
			$this->data['memory_usage'] = ( 100 / $this->data['memory_limit'] ) * $this->data['memory'];
		} else {
			$this->data['memory_usage'] = 0;
		}

		$this->data['wp_memory_limit'] = QM_Util::convert_hr_to_bytes( trim( WP_MEMORY_LIMIT ) ); // Pull the config value

		if ( $this->data['wp_memory_limit'] > 0 ) {
			$this->data['wp_memory_usage'] = ( 100 / $this->data['wp_memory_limit'] ) * $this->data['memory'];
		} else {
			$this->data['wp_memory_usage'] = 0;
		}

		$this->data['display_time_usage_warning'] = ( $this->data['time_usage'] >= 75 );
		$this->data['display_memory_usage_warning'] = ( $this->data['memory_usage'] >= 75 );

		$this->data['is_admin'] = is_admin();
	}

}

/**
 * @param array<string, QM_Collector> $collectors
 * @param QueryMonitor $qm
 * @return array<string, QM_Collector>
 */
function register_qm_collector_overview( array $collectors, QueryMonitor $qm ) {
	$collectors['overview'] = new QM_Collector_Overview();
	return $collectors;
}

add_filter( 'qm/collectors', 'register_qm_collector_overview', 1, 2 );
