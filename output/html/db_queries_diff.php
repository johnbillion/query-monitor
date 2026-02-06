<?php declare(strict_types = 1);
/**
 * Database query diff output for HTML pages.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QM_Output_Html_DB_Queries_Diff extends QM_Output_Html {

	/**
	 * Collector instance.
	 *
	 * @var QM_Collector_DB_Queries Collector.
	 */
	protected $collector;

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 50 );
		add_filter( 'qm/output/panel_menus', array( $this, 'panel_menu' ), 50 );
	}

	/**
	 * @return string
	 */
	public function name() {
		return __( 'Query Diff', 'query-monitor' );
	}

	/**
	 * @return void
	 */
	public function output() {
		$this->before_non_tabular_output( 'qm-query-diff', __( 'Query Diff', 'query-monitor' ) );

		echo '<div class="qm-query-diff-content">' . "\n";
		echo '<p class="qm-query-diff-disabled">' . esc_html__( 'Query diff tracking is disabled. Enable it in the Settings panel to compare queries between page loads.', 'query-monitor' ) . '</p>' . "\n";
		echo '</div>' . "\n";

		$this->after_non_tabular_output();
	}

	/**
	 * @param array<string, mixed[]> $menu
	 * @return array<string, mixed[]>
	 */
	public function admin_menu( array $menu ) {
		$menu['qm-query-diff'] = $this->menu( array(
			'title' => esc_html__( 'Query Diff', 'query-monitor' ),
			'id'    => 'query-monitor-query-diff',
			'href'  => '#qm-query-diff',
		) );

		return $menu;
	}

	/**
	 * @param array<string, mixed[]> $menu
	 * @return array<string, mixed[]>
	 */
	public function panel_menu( array $menu ) {
		if ( isset( $menu['qm-query-diff'] ) ) {
			$menu['qm-db_queries']['children']['qm-query-diff'] = $menu['qm-query-diff'];
			unset( $menu['qm-query-diff'] );
		}

		return $menu;
	}

}

/**
 * @param array<string, QM_Output> $output
 * @param QM_Collectors $collectors
 * @return array<string, QM_Output>
 */
function register_qm_output_html_db_queries_diff( array $output, QM_Collectors $collectors ) {
	$collector = QM_Collectors::get( 'db_queries' );
	if ( $collector ) {
		$output['db_queries_diff'] = new QM_Output_Html_DB_Queries_Diff( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_db_queries_diff', 50, 2 );
