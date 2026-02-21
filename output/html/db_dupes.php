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

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 45 );
		add_filter( 'qm/output/panel_menus', array( $this, 'panel_menu' ), 25 );
	}

	/**
	 * @return string
	 */
	public function name() {
		return __( 'Duplicate Queries', 'query-monitor' );
	}

	/**
	 * @param array<string, mixed[]> $menu
	 * @return array<string, mixed[]>
	 */
	public function admin_menu( array $menu ) {
		/** @var QM_Collector_DB_Queries|null $dbq */
		$dbq = QM_Collectors::get( 'db_queries' );

		/** @var QM_Data_DB_Queries $dbq_data */
		$dbq_data = $dbq->get_data();

		if ( ! empty( $dbq_data->dupes ) ) {
			$menu[ $this->collector->id() ] = $this->menu( array(
				'title' => sprintf(
					/* translators: %s: Number of duplicate database queries */
					__( 'Duplicate Queries (%s)', 'query-monitor' ),
					number_format_i18n( array_sum( array_column( $dbq_data->dupes, 'count' ) ) )
				),
			) );
		}

		return $menu;
	}

	/**
	 * @param array<string, mixed[]> $menu
	 * @return array<string, mixed[]>
	 */
	public function panel_menu( array $menu ) {
		$id = $this->collector->id();
		if ( isset( $menu[ $id ] ) ) {
			$menu['db_queries']['children'][] = $menu[ $id ];
			unset( $menu[ $id ] );
		}

		return $menu;
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
