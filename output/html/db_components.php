<?php declare(strict_types = 1);
/**
 * Database query calling component output for HTML pages.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QM_Output_Html_DB_Components extends QM_Output_Html {

	/**
	 * Collector instance.
	 *
	 * @var QM_Collector_DB_Components Collector.
	 */
	protected $collector;

	/**
	 * @var bool
	 */
	public static $client_side_rendered = true;

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/panel_menus', array( $this, 'panel_menu' ), 40 );
	}

	/**
	 * @return string
	 */
	public function name() {
		return __( 'Queries by Component', 'query-monitor' );
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
			if ( ! empty( $dbq_data->component_times ) ) {
				$menu['db_queries']['children'][] = $this->menu( array(
					'title' => esc_html__( 'Queries by Component', 'query-monitor' ),
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
function register_qm_output_html_db_components( array $output, QM_Collectors $collectors ) {
	$collector = QM_Collectors::get( 'db_components' );
	if ( $collector ) {
		$output['db_components'] = new QM_Output_Html_DB_Components( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_db_components', 40, 2 );
