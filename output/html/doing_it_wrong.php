<?php declare(strict_types = 1);
/**
 * Doing it Wrong output for HTML pages.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QM_Output_Html_Doing_It_Wrong extends QM_Output_Html {

	/**
	 * Collector instance.
	 *
	 * @var QM_Collector_Doing_It_Wrong Collector.
	 */
	protected $collector;

	public static $client_side_rendered = true;

	/**
	 * @return string
	 */
	public function name() {
		return __( 'Doing it Wrong', 'query-monitor' );
	}

}

/**
 * @param array<string, QM_Output> $output
 * @param QM_Collectors $collectors
 * @return array<string, QM_Output>
 */
function register_qm_output_html_doing_it_wrong( array $output, QM_Collectors $collectors ) {
	$collector = QM_Collectors::get( 'doing_it_wrong' );
	if ( $collector ) {
		$output['doing_it_wrong'] = new QM_Output_Html_Doing_It_Wrong( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_doing_it_wrong', 110, 2 );
