<?php declare(strict_types = 1);
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
		/** @var QM_Data_PHP_Errors $data */
		$data = $this->collector->get_data();

		$counts = [];

		foreach ( $data->errors as $error ) {
			if ( ! isset( $counts[ $error->level ] ) ) {
				$counts[ $error->level ] = 0;
			}
			$counts[ $error->level ]++;
		}

		$output = [];

		foreach ( $counts as $level => $count ) {
			$output[ "count-{$level}" ] = $count;
		}

		return $output;
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
