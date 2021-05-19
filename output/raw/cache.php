<?php
/**
 * Raw cache output.
 *
 * @package query-monitor
 */

class QM_Output_Raw_Cache extends QM_Output_Raw {

	/**
	 * Collector instance.
	 *
	 * @var QM_Collector_Cache Collector.
	 */
	protected $collector;

	public function name() {
		return __( 'Object Cache', 'query-monitor' );
	}

	public function get_output() {
		$output = array(
			'hit_percentage' => null,
			'hits'           => null,
			'misses'         => null,
		);
		$data = $this->collector->get_data();

		if ( isset( $data['stats'] ) && isset( $data['cache_hit_percentage'] ) ) {
			$output['hit_percentage'] = (float) number_format_i18n( $data['cache_hit_percentage'], 1 );
			$output['hits'] = (int) number_format_i18n( $data['stats']['cache_hits'], 0 );
			$output['misses'] = (int) number_format_i18n( $data['stats']['cache_misses'], 0 );
		}

		return $output;
	}
}

function register_qm_output_raw_cache( array $output, QM_Collectors $collectors ) {
	$collector = QM_Collectors::get( 'cache' );
	if ( $collector ) {
		$output['cache'] = new QM_Output_Raw_Cache( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/raw', 'register_qm_output_raw_cache', 30, 2 );
