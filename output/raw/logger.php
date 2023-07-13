<?php declare(strict_types = 1);
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
	 * @return array<string, array<int, array<string, mixed>>>
	 * @phpstan-return array<QM_Collector_Logger::*, list<array{
	 *   message: string,
	 *   stack: list<string>,
	 * }>>
	 */
	public function get_output() {
		$output = array();
		/** @var QM_Data_Logger $data */
		$data = $this->collector->get_data();

		if ( empty( $data->logs ) ) {
			return $output;
		}

		foreach ( $data->logs as $log ) {
			$output[ $log['level'] ][] = array(
				'message' => $log['message'],
				'stack' => array_column( $log['filtered_trace'], 'display' ),
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
