<?php declare(strict_types = 1);
/**
 * Raw HTTP API request output.
 *
 * @package query-monitor
 */

class QM_Output_Raw_HTTP extends QM_Output_Raw {

	/**
	 * Collector instance.
	 *
	 * @var QM_Collector_HTTP Collector.
	 */
	protected $collector;

	/**
	 * @return string
	 */
	public function name() {
		return __( 'HTTP API Calls', 'query-monitor' );
	}

	/**
	 * @return array<string, mixed>
	 */
	public function get_output() {
		$output = array();
		/** @var QM_Data_HTTP $data */
		$data = $this->collector->get_data();

		if ( empty( $data->http ) ) {
			return $output;
		}

		$requests = array();

		foreach ( $data->http as $http ) {
			$stack = array();

			foreach ( $http['filtered_trace'] as $item ) {
				$stack[] = $item['display'];
			}

			$requests[] = array(
				'url' => $http['url'],
				'method' => $http['args']['method'],
				'response' => ( $http['response'] instanceof WP_Error ) ? $http['response']->get_error_message() : $http['response']['response'],
				'time' => round( $http['ltime'], 4 ),
				'stack' => $stack,
			);
		}

		$output['total'] = count( $requests );
		$output['time'] = round( $data->ltime, 4 );
		$output['requests'] = $requests;

		return $output;
	}
}

/**
 * @param array<string, QM_Output> $output
 * @param QM_Collectors $collectors
 * @return array<string, QM_Output>
 */
function register_qm_output_raw_http( array $output, QM_Collectors $collectors ) {
	$collector = QM_Collectors::get( 'http' );
	if ( $collector ) {
		$output['http'] = new QM_Output_Raw_HTTP( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/raw', 'register_qm_output_raw_http', 30, 2 );
