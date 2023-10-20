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
		add_filter( 'qm/output/menu_class', array( $this, 'admin_class' ) );
	}

	/**
	 * @return string
	 */
	public function name() {
		return __( 'Logger', 'query-monitor' );
	}

	/**
	 * @param array<int, string> $class
	 * @return array<int, string>
	 */
	public function admin_class( array $class ) {
		/** @var QM_Data_Logger $data */
		$data = $this->collector->get_data();

		if ( empty( $data->logs ) ) {
			return $class;
		}

		foreach ( $data->logs as $log ) {
			if ( in_array( $log['level'], $this->collector->get_warning_levels(), true ) ) {
				$class[] = 'qm-warning';
				break;
			}
		}

		return $class;
	}

	/**
	 * @param array<string, mixed[]> $menu
	 * @return array<string, mixed[]>
	 */
	public function admin_menu( array $menu ) {
		/** @var QM_Data_Logger $data */
		$data = $this->collector->get_data();
		$key = 'log';
		$count = 0;

		if ( ! empty( $data->logs ) ) {
			$count = count( $data->logs );

			/* translators: %s: Number of logs that are available */
			$label = __( 'Logs (%s)', 'query-monitor' );
		} else {
			$label = __( 'Logs', 'query-monitor' );
		}

		$menu[ $this->collector->id() ] = $this->menu( array(
			'id' => "query-monitor-logger-{$key}",
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
function register_qm_output_html_logger( array $output, QM_Collectors $collectors ) {
	$collector = QM_Collectors::get( 'logger' );
	if ( $collector ) {
		$output['logger'] = new QM_Output_Html_Logger( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_logger', 12, 2 );
