<?php
/**
 * Enqueued scripts output for HTML pages.
 *
 * @package query-monitor
 */

class QM_Output_Html_Assets_Scripts extends QM_Output_Html_Assets {

	/**
	 * Collector instance.
	 *
	 * @var QM_Collector_Assets_Scripts Collector.
	 */
	protected $collector;

	public function name() {
		return __( 'Scripts', 'query-monitor' );
	}

	public function get_type_labels() {
		return array(
			/* translators: %s: Total number of enqueued scripts */
			'total'  => _x( 'Total: %s', 'Enqueued scripts', 'query-monitor' ),
			'plural' => __( 'Scripts', 'query-monitor' ),
			/* translators: %s: Total number of enqueued scripts */
			'count'  => _x( 'Scripts (%s)', 'Enqueued scripts', 'query-monitor' ),
		);
	}

}

function register_qm_output_html_assets_scripts( array $output, QM_Collectors $collectors ) {
	$collector = QM_Collectors::get( 'assets_scripts' );
	if ( $collector ) {
		$output['assets_scripts'] = new QM_Output_Html_Assets_Scripts( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_assets_scripts', 80, 2 );
