<?php declare(strict_types = 1);
/**
 * General overview output for HTTP headers.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QM_Output_Headers_Overview extends QM_Output_Headers {

	/**
	 * Collector instance.
	 *
	 * @var QM_Collector_Overview Collector.
	 */
	protected $collector;

	/**
	 * @return array<string, mixed>
	 */
	public function get_output() {
		/** @var QM_Data_Overview $data */
		$data = $this->collector->get_data();
		$headers = array();

		# Not localised because HTTP header values must be ASCII
		$headers['time_taken'] = number_format( $data->time_taken, 4 );
		$headers['time_usage'] = number_format( $data->time_usage, 1 ) . '%';
		$headers['time_limit'] = number_format( $data->time_limit ) . 's';

		if ( ! empty( $data->memory ) ) {
			$headers['memory'] = number_format( ( $data->memory / 1024 / 1024 ), 1 ) . ' MB';

			if ( $data->memory_limit > 0 ) {
				$headers['memory_usage'] = number_format( $data->memory_usage, 1 ) . '%';
				$headers['memory_limit'] = number_format( $data->memory_limit / 1024 / 1024 ) . ' MB';
			}
		}

		return $headers;

	}

}

/**
 * @param array<string, QM_Output> $output
 * @param QM_Collectors $collectors
 * @return array<string, QM_Output>
 */
function register_qm_output_headers_overview( array $output, QM_Collectors $collectors ) {
	$collector = QM_Collectors::get( 'overview' );
	if ( $collector ) {
		$output['overview'] = new QM_Output_Headers_Overview( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/headers', 'register_qm_output_headers_overview', 10, 2 );
