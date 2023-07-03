<?php declare(strict_types = 1);
/**
 * Doing it Wrong output for HTML pages.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QM_Output_Html_Doing_It_Wrong extends QM_Output_Html {

	/**
	 * Collector instance.
	 *
	 * @var QM_Collector_Doing_It_Wrong Collector.
	 */
	protected $collector;

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 15 );
		add_filter( 'qm/output/menu_class', array( $this, 'admin_class' ) );
	}

	/**
	 * @return string
	 */
	public function name() {
		return __( 'Doing it Wrong', 'query-monitor' );
	}

	/**
	 * @return array<string, string>
	 */
	public function get_type_labels() {
		return array(
			/* translators: %s: Total number of Doing it Wrong occurrences */
			'total' => _x( 'Total: %s', 'Doing it Wrong', 'query-monitor' ),
			'plural' => __( 'Doing it Wrong occurrences', 'query-monitor' ),
			/* translators: %s: Total number of Doing it Wrong occurrences */
			'count' => _x( 'Doing it Wrong (%s)', 'Doing it Wrong', 'query-monitor' ),
		);
	}

	/**
	 * @return void
	 */
	public function output() {
		/** @var QM_Data_Doing_It_Wrong $data */
		$data = $this->collector->get_data();

		if ( empty( $data->actions ) ) {
			$this->before_non_tabular_output();

			$notice = __( 'No occurrences.', 'query-monitor' );
			echo $this->build_notice( $notice ); // WPCS: XSS ok.

			$this->after_non_tabular_output();

			return;
		}

		$this->before_tabular_output();

		echo '<thead>';
		echo '<tr>';
		echo '<th scope="col">' . esc_html__( 'Message', 'query-monitor' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'Caller', 'query-monitor' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'Component', 'query-monitor' ) . '</th>';
		echo '</tr>';
		echo '</thead>';

		echo '<tbody>';

		foreach ( $data->actions as $row ) {
			$stack = array();

			foreach ( $row['filtered_trace'] as $frame ) {
				$stack[] = self::output_filename( $frame['display'], $frame['calling_file'], $frame['calling_line'] );
			}

			$caller = array_shift( $stack );

			echo '<tr>';

			printf( '<td>%s</td>', esc_html( wp_strip_all_tags( $row['message'] ) ) );

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

			echo '<td class="qm-nowrap">' . esc_html( $row['component']->name ) . '</td>';

			echo '</tr>';
		}

		echo '</tbody>';

		echo '<tfoot>';
		printf(
			'<tr><td colspan="3">%s</td></tr>',
			sprintf(
				/* translators: %s: Total number of Doing it Wrong occurrences */
				esc_html_x( 'Total: %s', 'Total Doing it Wrong occurrences', 'query-monitor' ),
				'<span class="qm-items-number">' . esc_html( number_format_i18n( count( $data->actions ) ) ) . '</span>'
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
		/** @var QM_Data_Doing_It_Wrong */
		$data = $this->collector->get_data();

		if ( ! empty( $data->actions ) ) {
			$class[] = 'qm-notice';
		}

		return $class;

	}

	/**
	 * @param array<string, mixed[]> $menu
	 * @return array<string, mixed[]>
	 */
	public function admin_menu( array $menu ) {
		/** @var QM_Data_Doing_It_Wrong */
		$data = $this->collector->get_data();

		if ( empty( $data->actions ) ) {
			return $menu;
		}

		$type_label = $this->get_type_labels();
		$label = sprintf(
			$type_label['count'],
			number_format_i18n( count( $data->actions ) )
		);

		$args = array(
			'title' => esc_html( $label ),
			'id'    => esc_attr( "query-monitor-{$this->collector->id}" ),
			'href'  => esc_attr( '#' . $this->collector->id() ),
		);

		if ( ! empty( $data->actions ) ) {
			$args['meta']['classname'] = 'qm-notice';
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
function register_qm_output_html_doing_it_wrong( array $output, QM_Collectors $collectors ) {
	$collector = QM_Collectors::get( 'doing_it_wrong' );
	if ( $collector ) {
		$output['doing_it_wrong'] = new QM_Output_Html_Doing_It_Wrong( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_doing_it_wrong', 110, 2 );
