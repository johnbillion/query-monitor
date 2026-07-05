<?php declare(strict_types = 1);
/**
 * Timing and profiling output for HTML pages.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QM_Output_Html_Timing extends QM_Output_Html {

	/**
	 * Collector instance.
	 *
	 * @var QM_Collector_Timing Collector.
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
		return __( 'Timing', 'query-monitor' );
	}

}

/**
 * @param array<string, QM_Output> $output
 * @param QM_Collectors $collectors
 * @return array<string, QM_Output>
 */
function register_qm_output_html_timing( array $output, QM_Collectors $collectors ) {
	$collector = QM_Collectors::get( 'timing' );
	if ( $collector ) {
		$output['timing'] = new QM_Output_Html_Timing( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_timing', 15, 2 );
