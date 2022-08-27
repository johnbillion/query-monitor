<?php
/**
 * Raw logger output.
 *
 * @package query-monitor
 */

class QM_Output_Raw_Logger extends QM_Output_Raw {

	/**
	 * Collector instance.
	 *
	 * @var QM_Collector_Logger Collector.
	 */
	protected $collector;

	/**
	 * @return string
	 */
	public function name() {
		return __( 'Logs', 'query-monitor' );
	}

	/**
	 * @return array<string, mixed>
	 */
	public function get_output() {
		$output = array();
		$data = $this->collector->get_data();

		if ( empty( $data['logs'] ) ) {
			return $output;
		}

		foreach ( $data['logs'] as $log ) {
			$stack = array();

			if ( isset( $log['trace'] ) ) {
				$filtered_trace = $log['trace']->get_filtered_trace();

				foreach ( $filtered_trace as $item ) {
					$stack[] = $item['display'];
				}
			}

			$output[ $log['level'] ][] = array(
				'message' => $log['message'],
				'stack' => $stack,
			);
		}

		return $output;
	}
}

/**
 * @param array<string, QM_Output> $output
 * @param QM_Collectors $collectors
 * @return array<string, QM_Output>
 */
function register_qm_output_raw_logger( array $output, QM_Collectors $collectors ) {
	$collector = QM_Collectors::get( 'logger' );
	if ( $collector ) {
		$output['logger'] = new QM_Output_Raw_Logger( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/raw', 'register_qm_output_raw_logger', 30, 2 );
