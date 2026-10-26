<?php declare(strict_types = 1);
/**
 * Block editor data output for HTML pages.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QM_Output_Html_Block_Editor extends QM_Output_Html {

	/**
	 * Collector instance.
	 *
	 * @var QM_Collector_Block_Editor Collector.
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
		return __( 'Blocks', 'query-monitor' );
	}

}

/**
 * @param array<string, QM_Output> $output
 * @param QM_Collectors $collectors
 * @return array<string, QM_Output>
 */
function register_qm_output_html_block_editor( array $output, QM_Collectors $collectors ) {
	$collector = QM_Collectors::get( 'block_editor' );
	if ( $collector ) {
		$output['block_editor'] = new QM_Output_Html_Block_Editor( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_block_editor', 60, 2 );
