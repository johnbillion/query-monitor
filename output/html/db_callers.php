<?php declare(strict_types = 1);
/**
 * Database query calling function output for HTML pages.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QM_Output_Html_DB_Callers extends QM_Output_Html {

	/**
	 * Collector instance.
	 *
	 * @var QM_Collector_DB_Callers Collector.
	 */
	protected $collector;

	/**
	 * @var bool
	 */
	public static $client_side_rendered = true;

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/panel_menus', array( $this, 'panel_menu' ), 30 );
	}

	/**
	 * @return string
	 */
	public function name() {
		return __( 'Queries by Caller', 'query-monitor' );
	}

	/**
	 * @param array<string, mixed[]> $menu
	 * @return array<string, mixed[]>
	 */
	public function panel_menu( array $menu ) {
		/** @var QM_Collector_DB_Queries|null $dbq */
		$dbq = QM_Collectors::get( 'db_queries' );

		if ( $dbq ) {
			/** @var QM_Data_DB_Queries $dbq_data */
			$dbq_data = $dbq->get_data();
			if ( ! empty( $dbq_data->times ) ) {
				$menu['db_queries']['children'][] = $this->menu( array(
					'title' => esc_html__( 'Queries by Caller', 'query-monitor' ),
				) );
			}
		}
		return $menu;

	}

}

/**
 * @param array<string, QM_Output> $output
 * @param QM_Collectors $collectors
 * @return array<string, QM_Output>
 */
function register_qm_output_html_db_callers( array $output, QM_Collectors $collectors ) {
	$collector = QM_Collectors::get( 'db_callers' );
	if ( $collector ) {
		$output['db_callers'] = new QM_Output_Html_DB_Callers( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_db_callers', 30, 2 );
