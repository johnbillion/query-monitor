<?php declare(strict_types = 1);
/**
 * Multisite output for HTML pages.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
		echo '<th scope="col" class="qm-num">#</th>';
		echo '<th scope="col">' . esc_html__( 'Function', 'query-monitor' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'Site Switch', 'query-monitor' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'Caller', 'query-monitor' ) . '</th>';
		echo '<th scope="col" class="qm-filterable-column">';
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $this->build_filter( 'component', array(), __( 'Component', 'query-monitor' ) );
		echo '</th>';
		echo '</tr>';
		echo '</thead>';

		echo '<tbody>';

		$i = 0;

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

			echo '<td class="qm-num">';
			if ( $row['to'] ) {
				echo intval( ++$i );
			}
			echo '</td>';

			echo '<td class="qm-nowrap"><code>';
			if ( $row['to'] ) {
				printf(
					'switch_to_blog(%d)',
					intval($row['new'] )
				);
			} else {
				echo 'restore_current_blog()';
			}
			echo '</code></td>';

			echo '<td class="qm-nowrap">';
			if ( $row['to'] ) {
				echo esc_html( sprintf(
					'%1$s &rarr; %2$s',
					$row['prev'],
					$row['new']
				) );
			} else {
				echo esc_html( sprintf(
					'%1$s &larr; %2$s',
					$row['new'],
					$row['prev']
				) );
			}
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

/**
 * @param array<string, QM_Output> $output
 * @param QM_Collectors $collectors
 * @return array<string, QM_Output>
 */
function register_qm_output_html_multisite( array $output, QM_Collectors $collectors ) {
	$collector = is_multisite() ? QM_Collectors::get( 'multisite' ) : null;

	if ( $collector ) {
		$output['multisite'] = new QM_Output_Html_Multisite( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_multisite', 65, 2 );
