<?php
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

	public function name() {
		return __( 'HTTP API Calls', 'query-monitor' );
	}

	public function get_output() {
		$output = array();
		$data   = $this->collector->get_data();

		if ( empty( $data['http'] ) ) {
			return $output;
		}

		$requests = array();

		foreach ( $data['http'] as $http ) {
			$stack = array();

			if ( isset( $http['trace'] ) ) {
				$filtered_trace = $http['trace']->get_display_trace();

				foreach ( $filtered_trace as $item ) {
					$stack[] = $item['display'];
				}
			}

			$requests[] = array(
				'url' => $http['url'],
				'method' => $http['args']['method'],
				'response' => $http['response']['response'],
				'time' => (float) number_format_i18n( $http['end'] - $http['start'], 4 ),
				'stack' => $stack,
			);
		}

		$output['total'] = count( $requests );
		$output['time'] = (float) number_format_i18n( $data['ltime'], 4 );
		$output['requests'] = $requests;

		return $output;
	}
}

function register_qm_output_raw_http( array $output, QM_Collectors $collectors ) {
	$collector = QM_Collectors::get( 'http' );
	if ( $collector ) {
		$output['http'] = new QM_Output_Raw_HTTP( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/raw', 'register_qm_output_raw_http', 30, 2 );
