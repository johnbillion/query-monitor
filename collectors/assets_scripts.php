<?php
/**
 * Enqueued scripts collector.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QM_Collector_Assets_Scripts extends QM_Collector_Assets {

	public $id = 'assets_scripts';

	public function get_dependency_type() {
		return 'scripts';
	}

	/**
	 * @return array<int, string>
	 */
	public function get_concerned_actions() {
		if ( is_admin() ) {
			return array(
				'admin_enqueue_scripts',
				'admin_print_footer_scripts',
				'admin_print_scripts',
			);
		} else {
			return array(
				'wp_enqueue_scripts',
				'wp_print_footer_scripts',
				'wp_print_scripts',
			);
		}
	}

	/**
	 * @return array<int, string>
	 */
	public function get_concerned_filters() {
		return array(
			'print_scripts_array',
			'script_loader_src',
			'script_loader_tag',
		);
	}
}

/**
 * @param array<string, QM_Collector> $collectors
 * @param QueryMonitor $qm
 * @return array<string, QM_Collector>
 */
function register_qm_collector_assets_scripts( array $collectors, QueryMonitor $qm ) {
	$collectors['assets_scripts'] = new QM_Collector_Assets_Scripts();
	return $collectors;
}

add_filter( 'qm/collectors', 'register_qm_collector_assets_scripts', 10, 2 );
