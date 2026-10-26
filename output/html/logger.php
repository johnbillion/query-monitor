<?php declare(strict_types = 1);
/**
 * PSR-3 compatible logging output for HTML pages.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QM_Output_Html_Logger extends QM_Output_Html {

	/**
	 * Collector instance.
	 *
	 * @var QM_Collector_Logger Collector.
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
		return __( 'Logger', 'query-monitor' );
	}

}

/**
 * @param array<string, QM_Output> $output
 * @param QM_Collectors $collectors
 * @return array<string, QM_Output>
 */
function register_qm_output_html_logger( array $output, QM_Collectors $collectors ) {
	$collector = QM_Collectors::get( 'logger' );
	if ( $collector ) {
		$output['logger'] = new QM_Output_Html_Logger( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_logger', 12, 2 );
