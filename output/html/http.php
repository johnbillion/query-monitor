<?php declare(strict_types = 1);
/**
 * HTTP API request output for HTML pages.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QM_Output_Html_HTTP extends QM_Output_Html {

	/**
	 * Collector instance.
	 *
	 * @var QM_Collector_HTTP Collector.
	 */
	protected $collector;

	/**
	 * @var bool
	 */
	public static $client_side_rendered = true;

	/**
	 * @return string
	 */
	public function name() {
		return __( 'HTTP API Calls', 'query-monitor' );
	}

}

/**
 * @param array<string, QM_Output> $output
 * @param QM_Collectors $collectors
 * @return array<string, QM_Output>
 */
function register_qm_output_html_http( array $output, QM_Collectors $collectors ) {
	$collector = QM_Collectors::get( 'http' );
	if ( $collector ) {
		$output['http'] = new QM_Output_Html_HTTP( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_http', 90, 2 );
