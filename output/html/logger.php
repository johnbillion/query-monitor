<?php declare(strict_types = 1);
/**
 * PSR-3 compatible logging output for HTML pages.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QM_Output_Html_Logger extends QM_Output_Html {

	/**
	 * Collector instance.
	 *
	 * @var QM_Collector_Logger Collector.
	 */
	protected $collector;

	/**
	 * @var bool
	 */
	public static $client_side_rendered = true;

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 47 );
	}

	/**
	 * @return string
	 */
	public function name() {
		return __( 'Logger', 'query-monitor' );
	}

	/**
	 * @param array<string, mixed[]> $menu
	 * @return array<string, mixed[]>
	 */
	public function admin_menu( array $menu ) {
		/** @var QM_Data_Logger $data */
		$data = $this->collector->get_data();

		$logs = ! empty( $data->logs ) ? $data->logs : array();
		$warning_levels = $this->collector->get_warning_levels();
		$warning_count = 0;

		foreach ( $logs as $log ) {
			if ( in_array( $log['level'], $warning_levels, true ) ) {
				++$warning_count;
			}
		}

		$menu[ $this->collector->id() ] = $this->menu( array(
			'title' => __( 'Logs', 'query-monitor' ),
			'ok_count' => ( count( $logs ) - $warning_count ) ?: null,
			'warning_count' => $warning_count ?: null,
		) );

		return $menu;
	}

}

/**
 * @param array<string, QM_Output> $output
 * @param QM_Collectors $collectors
 * @return array<string, QM_Output>
 */
function register_qm_output_html_logger( array $output, QM_Collectors $collectors ) {
	$collector = QM_Collectors::get( 'logger' );
	if ( $collector ) {
		$output['logger'] = new QM_Output_Html_Logger( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_logger', 12, 2 );
