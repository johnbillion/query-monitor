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
	 * @return void
	 */
	public function output() {
		/** @var QM_Data_Logger $data */
		$data = $this->collector->get_data();

		if ( empty( $data->logs ) ) {
			$this->before_non_tabular_output();

			$notice = sprintf(
				/* translators: %s: Link to help article */
				__( 'No data logged. <a href="%s">Read about logging variables in Query Monitor</a>.', 'query-monitor' ),
				'https://querymonitor.com/docs/logging-variables/'
			);
			echo $this->build_notice( $notice ); // WPCS: XSS ok.

			$this->after_non_tabular_output();

			return;
		}

		$levels = array();

		foreach ( $this->collector->get_levels() as $level ) {
			if ( $data->counts[ $level ] ) {
				$levels[ $level ] = sprintf(
					'%s (%d)',
					ucfirst( $level ),
					$data->counts[ $level ]
				);
			} else {
				$levels[ $level ] = ucfirst( $level );
			}
		}

		$this->before_tabular_output();

		$level_args = array(
			'all' => sprintf(
				/* translators: %s: Total number of items in a list */
				__( 'All (%d)', 'query-monitor' ),
				count( $data->logs )
			),
		);

		echo '<thead>';
		echo '<tr>';
		echo '<th scope="col" class="qm-filterable-column">';
		echo $this->build_filter( 'type', $levels, __( 'Level', 'query-monitor' ), $level_args ); // WPCS: XSS ok.
		echo '</th>';
		echo '<th scope="col" class="qm-col-message">' . esc_html__( 'Message', 'query-monitor' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'Caller', 'query-monitor' ) . '</th>';
		echo '<th scope="col" class="qm-filterable-column">';
		echo $this->build_filter( 'component', $data->components, __( 'Component', 'query-monitor' ) ); // WPCS: XSS ok.
		echo '</th>';
		echo '</tr>';
		echo '</thead>';

		echo '<tbody>';

		foreach ( $data->logs as $row ) {
			$component = $row['component'];

			$row_attr = array();
			$row_attr['data-qm-component'] = $component->name;
			$row_attr['data-qm-type'] = $row['level'];

			$attr = '';

			foreach ( $row_attr as $a => $v ) {
				$attr .= ' ' . $a . '="' . esc_attr( $v ) . '"';
			}

			$is_warning = in_array( $row['level'], $this->collector->get_warning_levels(), true );

			if ( $is_warning ) {
				$class = 'qm-warn';
			} else {
				$class = '';
			}

			echo '<tr' . $attr . ' class="' . esc_attr( $class ) . '">'; // WPCS: XSS ok.

			echo '<td class="qm-nowrap">';

			if ( $is_warning ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo QueryMonitor::icon( 'warning' );
			} else {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo QueryMonitor::icon( 'blank' );
			}

			echo esc_html( ucfirst( $row['level'] ) );
			echo '</td>';

			printf(
				'<td><pre>%s</pre></td>',
				esc_html( $row['message'] )
			);

			$stack = array();
			$filtered_trace = $row['filtered_trace'];

			foreach ( $filtered_trace as $frame ) {
				$stack[] = self::output_filename( $frame['display'], $frame['calling_file'], $frame['calling_line'] );
			}

			$caller = array_shift( $stack );

			echo '<td class="qm-has-toggle qm-nowrap qm-ltr">';

			if ( ! empty( $stack ) ) {
				echo self::build_toggler(); // WPCS: XSS ok;
			}

			echo '<ol>';

			echo "<li>{$caller}</li>"; // WPCS: XSS ok.

			if ( ! empty( $stack ) ) {
				echo '<div class="qm-toggled"><li>' . implode( '</li><li>', $stack ) . '</li></div>'; // WPCS: XSS ok.
			}

			echo '</ol></td>';

			printf(
				'<td class="qm-nowrap">%s</td>',
				esc_html( $component->name )
			);

			echo '</tr>';

		}

		echo '</tbody>';

		$this->after_tabular_output();
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
			foreach ( $data->logs as $log ) {
				if ( in_array( $log['level'], $this->collector->get_warning_levels(), true ) ) {
					$key = 'warning';
					break;
				}
			}

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
