<?php declare(strict_types = 1);
/**
 * Emails output for HTML pages.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QM_Output_Html_Emails extends QM_Output_Html {

	/**
	 * Collector instance.
	 *
	 * @var QM_Collector_Emails Collector.
	 */
	protected $collector;

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 70 );
		add_filter( 'qm/output/menu_class', array( $this, 'admin_class' ) );
	}

	/**
	 * @return string
	 */
	public function name() {
		return __( 'Emails', 'query-monitor' );
	}

	/**
	 * @return array<string, string>
	 */
	public function get_type_labels() {
		return array(
			/* translators: %s: Total number of emails */
			'total' => _x( 'Total: %s', 'Emails', 'query-monitor' ),
			'plural' => __( 'Emails', 'query-monitor' ),
			/* translators: %s: Total number of emails */
			'count' => _x( 'Emails (%s)', 'Emails', 'query-monitor' ),
		);
	}

	/**
	 * @return void
	 */
	public function output() {
		/** @var QM_Data_Emails $data */
		$data = $this->collector->get_data();

		if ( empty( $data->emails ) ) {
			$this->before_non_tabular_output();

			$notice = __( 'No emails.', 'query-monitor' );
			echo $this->build_notice( $notice ); // WPCS: XSS ok.

			$this->after_non_tabular_output();

			return;
		}

		$this->before_tabular_output();

		echo '<thead>';
		echo '<tr>';
		echo '<th scope="col">' . esc_html__( 'To', 'query-monitor' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'Subject', 'query-monitor' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'Caller', 'query-monitor' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'Headers', 'query-monitor' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'Attachments', 'query-monitor' ) . '</th>';
		echo '</tr>';
		echo '</thead>';

		echo '<tbody>';

		foreach ( $data->emails as $hash => $row ) {
			$is_error = false;
			$row_attr = array();
			$stack    = array();
			$css      = '';
			$to       = $row['atts']['to'];

			if ( is_array( $to ) ) {
				$to = implode( PHP_EOL, $to );
			}

			$filtered_trace = $row['filtered_trace'];

			foreach ( $filtered_trace as $frame ) {
				$stack[] = self::output_filename( $frame['display'], $frame['calling_file'], $frame['calling_line'] );
			}

			$is_error = in_array( $hash, $data->preempted ) || in_array( $hash, $data->failed );

			if ( $is_error ) {
				$css = 'qm-warn';
			}

			$attr = '';
			foreach ( $row_attr as $a => $v ) {
				$attr .= ' ' . $a . '="' . esc_attr( (string) $v ) . '"';
			}

			if ( ! is_array( $row['atts']['headers'] ) ) {
				$row['atts']['headers'] = array();
			}

			printf( // WPCS: XSS ok.
				'<tr %s class="%s">',
				$attr,
				esc_attr( $css )
			);

			printf(
				'<td>%s</td>',
				nl2br( esc_html( $to ) )
			);

			echo '<td class="qm-wrap">';

			echo esc_html( $row['atts']['subject'] );

			if ( is_wp_error( $row['error'] ) ) {
				$icon = QueryMonitor::icon( 'warning' );
				echo '<br />' . $icon . __( 'Failed sending:', 'query-monitor' ) . ' ' . $row['error']->get_error_message();
			}

			echo '</td>';

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

			echo '<td class="qm-has-inner qm-ltr">';
			self::output_inner( $row['atts']['headers'] );
			echo '</td>';

			printf(
				'<td class="qm-num">%d</td>',
				count( $row['atts']['attachments'] )
			);

			echo '</tr>';
		}

		echo '</tbody>';

		echo '<tfoot>';
		printf(
			'<tr><td colspan="5">%s %s %s</td></tr>',
			sprintf(
				/* translators: %s: Total number of emails */
				esc_html_x( 'Total: %s', 'Total emails', 'query-monitor' ),
				'<span class="qm-items-number">' . esc_html( number_format_i18n( $data->counts['total'] ) ) . '</span>'
			),
			sprintf(
				/* translators: %s: Total number of emails preempted */
				esc_html_x( 'Preempted: %s', 'Preempted emails', 'query-monitor' ),
				'<span class="qm-items-number">' . esc_html( number_format_i18n( $data->counts['preempted'] ) ) . '</span>'
			),
			sprintf(
				/* translators: %s: Total number of emails failed */
				esc_html_x( 'Failed: %s', 'Failed emails', 'query-monitor' ),
				'<span class="qm-items-number">' . esc_html( number_format_i18n( $data->counts['failed'] ) ) . '</span>'
			)
		);
		echo '</tfoot>';

		$this->after_tabular_output();
	}

	/**
	 * @param array<int, string> $class
	 * @return array<int, string>
	 */
	public function admin_class( array $class ) {
		/** @var QM_Data_Email */
		$data = $this->collector->get_data();

		if ( ! empty( $data->preempted ) || ! empty( $data->failed ) ) {
			$class[] = 'qm-error';
		}

		return $class;

	}

	/**
	 * @param array<string, mixed[]> $menu
	 * @return array<string, mixed[]>
	 */
	public function admin_menu( array $menu ) {
		/** @var QM_Data_Email */
		$data = $this->collector->get_data();

		$type_label = $this->get_type_labels();
		$label = sprintf(
			$type_label['count'],
			number_format_i18n( $data->counts['total'] )
		);

		$args = array(
			'title' => esc_html( $label ),
			'id' => esc_attr( "query-monitor-{$this->collector->id}" ),
			'href' => esc_attr( '#' . $this->collector->id() ),
		);

		if ( ! empty( $data->preempted ) || ! empty( $data->failed ) ) {
			$args['meta']['classname'] = 'qm-error';
		}

		$id = $this->collector->id();
		$menu[ $id ] = $this->menu( $args );

		return $menu;

	}

}

/**
 * @param array<string, QM_Output> $output
 * @param QM_Collectors $collectors
 * @return array<string, QM_Output>
 */
function register_qm_output_html_emails( array $output, QM_Collectors $collectors ) {
	$collector = QM_Collectors::get( 'emails' );
	if ( $collector ) {
		$output['emails'] = new QM_Output_Html_Emails( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_emails', 110, 2 );
