<?php
/**
 * PHP error output for HTTP headers.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QM_Output_Headers_PHP_Errors extends QM_Output_Headers {

	/**
	 * Collector instance.
	 *
	 * @var QM_Collector_PHP_Errors Collector.
	 */
	protected $collector;

	/**
	 * @return array<string, mixed>
	 */
	public function get_output() {

		$data = $this->collector->get_data();
		$headers = array();

		if ( empty( $data['errors'] ) ) {
			return array();
		}

		$count = 0;

		foreach ( $data['errors'] as $type => $errors ) {

			foreach ( $errors as $error_key => $error ) {

				$count++;

				if ( $error['component'] ) {
					$component = $error['component']->name;
					$stack = wp_list_pluck( $error['filtered_trace'], 'display' );
				} else {
					$component = __( 'Unknown', 'query-monitor' );
					$stack = array();
				}

				$output_error = array(
					'key' => $error_key,
					'type' => $error['type'],
					'message' => $error['message'],
					'file' => QM_Util::standard_dir( $error['file'], '' ),
					'line' => $error['line'],
					'stack' => $stack,
					'component' => $component,
				);

				$key = sprintf( 'error-%d', $count );
				$headers[ $key ] = json_encode( $output_error );

			}
		}

		return array_merge(
			array(
				'error-count' => $count,
			),
			$headers
		);
	}

}

/**
 * @param array<string, QM_Output> $output
 * @param QM_Collectors $collectors
 * @return array<string, QM_Output>
 */
function register_qm_output_headers_php_errors( array $output, QM_Collectors $collectors ) {
	$collector = QM_Collectors::get( 'php_errors' );
	if ( $collector ) {
		$output['php_errors'] = new QM_Output_Headers_PHP_Errors( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/headers', 'register_qm_output_headers_php_errors', 110, 2 );
