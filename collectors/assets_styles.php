<?php
/**
 * Enqueued styles collector.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QM_Collector_Assets_Styles extends QM_Collector_Assets {

	public $id = 'assets_styles';

	public function get_dependency_type() {
		return 'styles';
	}

	/**
	 * @return array<int, string>
	 */
	public function get_concerned_filters() {
		return array(
			'print_styles_array',
			'style_loader_src',
			'style_loader_tag',
		);
	}
}

/**
 * @param array<string, QM_Collector> $collectors
 * @param QueryMonitor $qm
 * @return array<string, QM_Collector>
 */
function register_qm_collector_assets_styles( array $collectors, QueryMonitor $qm ) {
	$collectors['assets_styles'] = new QM_Collector_Assets_Styles();
	return $collectors;
}

add_filter( 'qm/collectors', 'register_qm_collector_assets_styles', 10, 2 );
