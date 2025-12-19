<?php declare(strict_types = 1);
/**
 * Object cache output for HTML pages.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QM_Output_Html_Cache extends QM_Output_Html {

	/**
	 * Collector instance.
	 *
	 * @var QM_Collector_Cache Collector.
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
		return __( 'Object Cache', 'query-monitor' );
	}

}

/**
 * @param array<string, QM_Output> $output
 * @param QM_Collectors $collectors
 * @return array<string, QM_Output>
 */
function register_qm_output_html_cache( array $output, QM_Collectors $collectors ) {
	$collector = QM_Collectors::get( 'cache' );
	if ( $collector ) {
		$output['cache'] = new QM_Output_Html_Cache( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_cache', 9, 2 );
