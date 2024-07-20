<?php declare(strict_types = 1);
/**
 * Enqueued styles collector.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * qm-collectors assets_styles
 */
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
