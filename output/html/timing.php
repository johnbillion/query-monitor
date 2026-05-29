<?php declare(strict_types = 1);
/**
 * Timing and profiling output for HTML pages.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QM_Output_Html_Timing extends QM_Output_Html {

	/**
	 * Collector instance.
	 *
	 * @var QM_Collector_Timing Collector.
	 */
	protected $collector;

	/**
	 * @var bool
	 */
	public static $client_side_rendered = true;

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 46 );
	}

	/**
	 * @return string
	 */
	public function name() {
		return __( 'Timing', 'query-monitor' );
	}

	/**
	 * @param array<string, mixed[]> $menu
	 * @return array<string, mixed[]>
	 */
	public function admin_menu( array $menu ) {
		/** @var QM_Data_Timing $data */
		$data = $this->collector->get_data();
		$count = 0;

		if ( ! empty( $data->timing ) || ! empty( $data->warning ) ) {
			if ( ! empty( $data->timing ) ) {
				$count += count( $data->timing );
			}
			if ( ! empty( $data->warning ) ) {
				$count += count( $data->warning );
			}
			/* translators: %s: Number of function timing results that are available */
			$label = __( 'Timings (%s)', 'query-monitor' );
		} else {
			$label = __( 'Timings', 'query-monitor' );
		}

		$menu[ $this->collector->id() ] = $this->menu( array(
			'title' => esc_html( sprintf(
				$label,
				number_format_i18n( $count )
			) ),
		) );

		return $menu;
	}

}

/**
 * @param array<string, QM_Output> $output
 * @param QM_Collectors $collectors
 * @return array<string, QM_Output>
 */
function register_qm_output_html_timing( array $output, QM_Collectors $collectors ) {
	$collector = QM_Collectors::get( 'timing' );
	if ( $collector ) {
		$output['timing'] = new QM_Output_Html_Timing( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_timing', 15, 2 );
