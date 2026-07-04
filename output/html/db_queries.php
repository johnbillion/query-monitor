<?php declare(strict_types = 1);
/**
 * Database query output for HTML pages.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @phpstan-import-type QueryRow from QM_Data_DB_Queries
 */
class QM_Output_Html_DB_Queries extends QM_Output_Html {

	/**
	 * Collector instance.
	 *
	 * @var QM_Collector_DB_Queries Collector.
	 */
	protected $collector;

	/**
	 * @var bool
	 */
	public static $client_side_rendered = true;

	/**
	 * @var int
	 */
	public $query_row = 0;

	/**
	 * @return string
	 */
	public function name() {
		return __( 'Database Queries', 'query-monitor' );
	}

}

/**
 * @param array<string, QM_Output> $output
 * @param QM_Collectors $collectors
 * @return array<string, QM_Output>
 */
function register_qm_output_html_db_queries( array $output, QM_Collectors $collectors ) {
	$collector = QM_Collectors::get( 'db_queries' );
	if ( $collector ) {
		$output['db_queries'] = new QM_Output_Html_DB_Queries( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_db_queries', 20, 2 );
