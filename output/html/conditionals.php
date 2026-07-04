<?php declare(strict_types = 1);
/**
 * Template conditionals output for HTML pages.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QM_Output_Html_Conditionals extends QM_Output_Html {

	/**
	 * Collector instance.
	 *
	 * @var QM_Collector_Conditionals Collector.
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
		return __( 'Conditionals', 'query-monitor' );
	}

}

/**
 * @param array<string, QM_Output> $output
 * @param QM_Collectors $collectors
 * @return array<string, QM_Output>
 */
function register_qm_output_html_conditionals( array $output, QM_Collectors $collectors ) {
	$collector = QM_Collectors::get( 'conditionals' );
	if ( $collector ) {
		$output['conditionals'] = new QM_Output_Html_Conditionals( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_conditionals', 50, 2 );
