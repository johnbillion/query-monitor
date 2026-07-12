<?php declare(strict_types = 1);
/**
 * Enqueued styles output for HTML pages.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QM_Output_Html_Assets_Styles extends QM_Output_Html_Assets {

	/**
	 * Collector instance.
	 *
	 * @var QM_Collector_Assets_Styles Collector.
	 */
	protected $collector;

	/**
	 * @var bool
	 */
	public static $client_side_rendered = true;

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 71 );
	}

	/**
	 * @return string
	 */
	public function name() {
		return _x( 'Styles', 'Enqueued styles', 'query-monitor' );
	}

}

/**
 * @param array<string, QM_Output> $output
 * @param QM_Collectors $collectors
 * @return array<string, QM_Output>
 */
function register_qm_output_html_assets_styles( array $output, QM_Collectors $collectors ) {
	$collector = QM_Collectors::get( 'assets_styles' );
	if ( $collector ) {
		$output['assets_styles'] = new QM_Output_Html_Assets_Styles( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_assets_styles', 80, 2 );
