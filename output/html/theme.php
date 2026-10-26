<?php declare(strict_types = 1);
/**
 * Template and theme output for HTML pages.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QM_Output_Html_Theme extends QM_Output_Html {

	/**
	 * Collector instance.
	 *
	 * @var QM_Collector_Theme Collector.
	 */
	protected $collector;

	/**
	 * @var bool
	 */
	public static $client_side_rendered = true;

	/**
	 * @return string
	 */
	public function name() {
		return __( 'Theme', 'query-monitor' );
	}

}

/**
 * @param array<string, QM_Output> $output
 * @param QM_Collectors $collectors
 * @return array<string, QM_Output>
 */
function register_qm_output_html_theme( array $output, QM_Collectors $collectors ) {
	if ( is_admin() ) {
		return $output;
	}
	$collector = QM_Collectors::get( 'response' );
	if ( $collector ) {
		$output['response'] = new QM_Output_Html_Theme( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_theme', 70, 2 );
