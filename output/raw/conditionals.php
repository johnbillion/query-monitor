<?php
/**
 * Raw conditionals output.
 *
 * @package query-monitor
 */

class QM_Output_Raw_Conditionals extends QM_Output_Raw {

	/**
	 * Collector instance.
	 *
	 * @var QM_Collector_Conditionals Collector.
	 */
	protected $collector;

	public function name() {
		return __( 'Conditionals', 'query-monitor' );
	}

	public function get_output() {
		$data = $this->collector->get_data();

		return $data['conds']['true'];
	}
}

function register_qm_output_raw_conditionals( array $output, QM_Collectors $collectors ) {
	$collector = QM_Collectors::get( 'conditionals' );
	if ( $collector ) {
		$output['conditionals'] = new QM_Output_Raw_Conditionals( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/raw', 'register_qm_output_raw_conditionals', 20, 2 );
