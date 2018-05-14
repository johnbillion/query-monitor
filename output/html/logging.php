<?php
/**
 * PSR-3 compatible logging output for HTML pages.
 *
 * @package query-monitor
 */

class QM_Output_Html_Logging extends QM_Output_Html {

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 12 );
		add_filter( 'qm/output/menu_class', array( $this, 'admin_class' ) );
	}

	public function output() {

		$data = $this->collector->get_data();

		if ( empty( $data['logs'] ) ) {
			return;
		}

		echo '<div class="qm" id="' . esc_attr( $this->collector->id() ) . '">';
		echo '<table>';

		echo '<thead>';
		echo '<tr>';
		echo '<th scope="col">' . esc_html__( 'Level', 'query-monitor' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'Message', 'query-monitor' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'Caller', 'query-monitor' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'Component', 'query-monitor' ) . '</th>';
		echo '</tr>';
		echo '</thead>';

		echo '<tbody>';

		foreach ( $this->collector->get_levels() as $level ) {

			if ( empty( $data['logs'][ $level ] ) ) {
				continue;
			}

			foreach ( $data['logs'][ $level ] as $row ) {

				echo '<tr>';

				printf(
					'<td>%s</td>',
					esc_html( ucfirst( $level ) )
				);

				printf(
					'<td>%s</td>',
					esc_html( $row['message'] )
				);

				$stack          = array();
				$filtered_trace = $row['trace']->get_display_trace();
				$component      = $row['trace']->get_component();

				foreach ( $filtered_trace as $item ) {
					$stack[] = self::output_filename( $item['display'], $item['calling_file'], $item['calling_line'] );
				}

				echo '<td class="qm-has-toggle qm-nowrap qm-ltr"><ol class="qm-toggler qm-numbered">';

				$caller = array_pop( $stack );

				if ( ! empty( $stack ) ) {
					echo self::build_toggler(); // WPCS: XSS ok;
					echo '<div class="qm-toggled"><li>' . implode( '</li><li>', $stack ) . '</li></div>'; // WPCS: XSS ok.
				}

				echo "<li>{$caller}</li>"; // WPCS: XSS ok.
				echo '</ol></td>';

				printf(
					'<td class="qm-nowrap">%s</td>',
					esc_html( $component->name )
				);

				echo '</tr>';

			}
		}

		echo '</tbody>';
		echo '</table>';
		echo '</div>';

	}

	public function admin_class( array $class ) {
		$data = $this->collector->get_data();

		foreach ( $this->collector->get_warning_levels() as $level ) {
			if ( ! empty( $data['logs'][ $level ] ) ) {
				$class[] = 'qm-warning';
				break;
			}
		}

		return $class;
	}

	public function admin_menu( array $menu ) {
		$data = $this->collector->get_data();

		if ( empty( $data['logs'] ) ) {
			return $menu;
		}

		$count = 0;
		$key   = 'log';

		foreach ( $this->collector->get_warning_levels() as $level ) {
			if ( ! empty( $data['logs'][ $level ] ) ) {
				$key = 'warning';
				break;
			}
		}

		foreach ( $data['logs'] as $level => $logs ) {
			$count += count( $logs );
		}

		/* translators: %s: Number of logs that are available */
		$label = _n( 'Logs (%s)', 'Logs (%s)', $count, 'query-monitor' );
		$menu[] = $this->menu( array(
			'id'    => "query-monitor-logging-{$key}",
			'title' => esc_html( sprintf(
				$label,
				number_format_i18n( $count )
			) ),
		) );

		return $menu;
	}

}

function register_qm_output_html_logging( array $output, QM_Collectors $collectors ) {
	if ( $collector = QM_Collectors::get( 'logging' ) ) {
		$output['logging'] = new QM_Output_Html_Logging( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_logging', 12, 2 );
