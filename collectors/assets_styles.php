<?php
/**
 * Enqueued styles collector.
 *
 * @package query-monitor
 */

defined( 'ABSPATH' ) || exit;

class QM_Collector_Assets_Styles extends QM_Collector_Assets {

	public $id = 'assets_styles';

	public function get_dependency_type() {
		return 'styles';
	}

	public function get_concerned_filters() {
		return array(
			'print_styles_array',
			'style_loader_src',
			'style_loader_tag',
		);
	}
}

function register_qm_collector_assets_styles( array $collectors, QueryMonitor $qm ) {
	$collectors['assets_styles'] = new QM_Collector_Assets_Styles();
	return $collectors;
}

add_filter( 'qm/collectors', 'register_qm_collector_assets_styles', 10, 2 );
