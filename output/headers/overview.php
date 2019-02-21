<?php
/**
 * General overview output for HTTP headers.
 *
 * @package query-monitor
 */

class QM_Output_Headers_Overview extends QM_Output_Headers {

	public function get_output() {

		$data    = $this->collector->get_data();
		$headers = array();

		$headers['time_taken'] = number_format_i18n( $data['time_taken'], 4 );
		$headers['time_usage'] = sprintf(
			/* translators: 1: Percentage of time limit used, 2: Time limit in seconds */
			__( '%1$s%% of %2$ss limit', 'query-monitor' ),
			number_format_i18n( $data['time_usage'], 1 ),
			number_format_i18n( $data['time_limit'] )
		);

		if ( ! empty( $data['memory'] ) ) {
			$headers['memory'] = sprintf(
				/* translators: %s: Memory used in kilobytes */
				__( '%s kB', 'query-monitor' ),
				number_format_i18n( $data['memory'] / 1024 )
			);
			$headers['memory_usage'] = sprintf(
				/* translators: 1: Percentage of memory limit used, 2: Memory limit in kilobytes */
				__( '%1$s%% of %2$s kB limit', 'query-monitor' ),
				number_format_i18n( $data['memory_usage'], 1 ),
				number_format_i18n( $data['memory_limit'] / 1024 )
			);
		}

		return $headers;

	}

}

function register_qm_output_headers_overview( array $output, QM_Collectors $collectors ) {
	$collector = QM_Collectors::get( 'overview' );
	if ( $collector ) {
		$output['overview'] = new QM_Output_Headers_Overview( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/headers', 'register_qm_output_headers_overview', 10, 2 );
