<?php
/**
 * Multisite output for HTML pages.
 *
 * @package query-monitor
 */

defined( 'ABSPATH' ) || exit;

class QM_Output_Html_Multisite extends QM_Output_Html {

	/**
	 * Collector instance.
	 *
	 * @var QM_Collector_Multisite Collector.
	 */
	protected $collector;

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 55 );
	}

	public function name() {
		return __( 'Multisite', 'query-monitor' );
	}

	public function output() {

		$data = $this->collector->get_data();

		if ( empty( $data['switches'] ) ) {
			$this->before_non_tabular_output();

			$notice = __( 'No data logged.', 'query-monitor' );
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $this->build_notice( $notice );

			$this->after_non_tabular_output();

			return;
		}

		$this->before_tabular_output();

		echo '<thead>';
		echo '<tr>';
		echo '<th scope="col">' . esc_html__( 'Context', 'query-monitor' ) . '</th>';
		echo '<th scope="col" class="qm-num">' . esc_html__( 'From', 'query-monitor' ) . '</th>';
		echo '<th scope="col" class="qm-num">' . esc_html__( 'To', 'query-monitor' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'Caller', 'query-monitor' ) . '</th>';
		echo '<th scope="col" class="qm-filterable-column">';
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $this->build_filter( 'component', [], __( 'Component', 'query-monitor' ) );
		echo '</th>';
		echo '</tr>';
		echo '</thead>';

		echo '<tbody>';

		foreach ( $data['switches'] as $row ) {
			$component = $row['trace']->get_component();

			$row_attr                      = array();
			$row_attr['data-qm-component'] = $component->name;

			$attr = '';

			foreach ( $row_attr as $a => $v ) {
				$attr .= ' ' . $a . '="' . esc_attr( $v ) . '"';
			}

			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '<tr' . $attr . '>';

			echo '<td class="qm-nowrap">';
			echo esc_html( $row['to'] ? 'Switch' : 'Restore' );
			echo '</td>';

			echo '<td class="qm-num">';
			echo esc_html( $row['prev'] );
			echo '</td>';

			echo '<td class="qm-num">';
			echo esc_html( $row['new'] );
			echo '</td>';

			$stack          = array();
			$filtered_trace = $row['trace']->get_display_trace();

			foreach ( $filtered_trace as $item ) {
				$stack[] = self::output_filename( $item['display'], $item['calling_file'], $item['calling_line'] );
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
}

function register_qm_output_html_multisite( array $output, QM_Collectors $collectors ) {
	$collector = is_multisite() ? QM_Collectors::get( 'multisite' ) : null;

	if ( $collector ) {
		$output['multisite'] = new QM_Output_Html_Multisite( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_multisite', 65, 2 );
