<?php
/**
 * Enqueued styles output for HTML pages.
 *
 * @package query-monitor
 */

class QM_Output_Html_Assets_Styles extends QM_Output_Html_Assets {

	/**
	 * Collector instance.
	 *
	 * @var QM_Collector_Assets_Styles Collector.
	 */
	protected $collector;

	public function get_type_labels() {
		return array(
			/* translators: %s: Total number of enqueued styles */
			'total'  => _nx_noop( 'Total: %s', 'Total: %s', 'Enqueued styles', 'query-monitor' ),
			'plural' => __( 'Styles', 'query-monitor' ),
			/* translators: %s: Total number of enqueued styles */
			'count'  => _nx_noop( 'Styles (%s)', 'Styles (%s)', 'Enqueued styles', 'query-monitor' ),
		);
	}

}

function register_qm_output_html_assets_styles( array $output, QM_Collectors $collectors ) {
	$collector = QM_Collectors::get( 'assets_styles' );
	if ( $collector ) {
		$output['assets_styles'] = new QM_Output_Html_Assets_Styles( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_assets_styles', 80, 2 );
