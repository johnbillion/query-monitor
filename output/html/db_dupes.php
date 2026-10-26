<?php declare(strict_types = 1);
/**
 * Duplicate database query output for HTML pages.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QM_Output_Html_DB_Dupes extends QM_Output_Html {

	/**
	 * Collector instance.
	 *
	 * @var QM_Collector_DB_Dupes Collector.
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
		return __( 'Duplicate Queries', 'query-monitor' );
	}

}

/**
 * @param array<string, QM_Output> $output
 * @param QM_Collectors $collectors
 * @return array<string, QM_Output>
 */
function register_qm_output_html_db_dupes( array $output, QM_Collectors $collectors ) {
	$collector = QM_Collectors::get( 'db_dupes' );
	if ( $collector ) {
		$output['db_dupes'] = new QM_Output_Html_DB_Dupes( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_db_dupes', 45, 2 );
