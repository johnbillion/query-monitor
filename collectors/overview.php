<?php
/**
 * General overview collector.
 *
 * @package query-monitor
 */

class QM_Collector_Overview extends QM_Collector {

	public $id = 'overview';

	public function name() {
		return __( 'Overview', 'query-monitor' );
	}

	public function process() {

		$this->data['time_taken'] = self::timer_stop_float();
		$this->data['time_limit'] = ini_get( 'max_execution_time' );
		$this->data['time_start'] = $GLOBALS['timestart'];

		if ( ! empty( $this->data['time_limit'] ) ) {
			$this->data['time_usage'] = ( 100 / $this->data['time_limit'] ) * $this->data['time_taken'];
		} else {
			$this->data['time_usage'] = 0;
		}

		if ( function_exists( 'memory_get_peak_usage' ) ) {
			$this->data['memory'] = memory_get_peak_usage();
		} elseif ( function_exists( 'memory_get_usage' ) ) {
			$this->data['memory'] = memory_get_usage();
		} else {
			$this->data['memory'] = 0;
		}

		if ( is_user_logged_in() ) {
			$this->data['current_user'] = self::format_user( wp_get_current_user() );
		} else {
			$this->data['current_user'] = false;
		}

		if ( function_exists( 'current_user_switched' ) && current_user_switched() ) {
			$this->data['switched_user'] = self::format_user( current_user_switched() );
		} else {
			$this->data['switched_user'] = false;
		}

		$this->data['memory_limit'] = QM_Util::convert_hr_to_bytes( ini_get( 'memory_limit' ) );

		if ( $this->data['memory_limit'] > 0 ) {
			$this->data['memory_usage'] = ( 100 / $this->data['memory_limit'] ) * $this->data['memory'];
		} else {
			$this->data['memory_usage'] = 0;
		}

		$this->data['display_time_usage_warning']   = ( $this->data['time_usage'] >= 75 );
		$this->data['display_memory_usage_warning'] = ( $this->data['memory_usage'] >= 75 );

		$this->data['is_admin'] = is_admin();
	}

}

function register_qm_collector_overview( array $collectors, QueryMonitor $qm ) {
	$collectors['overview'] = new QM_Collector_Overview;
	return $collectors;
}

add_filter( 'qm/collectors', 'register_qm_collector_overview', 1, 2 );
