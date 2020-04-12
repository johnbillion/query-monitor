<?php
/**
 * Enqueued scripts collector.
 *
 * @package query-monitor
 */

class QM_Collector_Assets_Scripts extends QM_Collector_Assets {

	public $id = 'assets_scripts';

	public function get_dependency_type() {
		return 'scripts';
	}

	public function get_concerned_actions() {
		return array(
			'admin_print_footer_scripts',
			'wp_print_footer_scripts',
		);
	}

	public function get_concerned_filters() {
		return array(
			'print_scripts_array',
			'script_loader_src',
			'script_loader_tag',
		);
	}
}

function register_qm_collector_assets_scripts( array $collectors, QueryMonitor $qm ) {
	$collectors['assets_scripts'] = new QM_Collector_Assets_Scripts();
	return $collectors;
}

add_filter( 'qm/collectors', 'register_qm_collector_assets_scripts', 10, 2 );
