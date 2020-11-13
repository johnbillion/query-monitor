<?php
/**
 * WP error output for HTML pages.
 *
 * @package query-monitor
 */

class QM_Output_Html_WP_Errors extends QM_Output_Html {

	/**
	 * Collector instance.
	 *
	 * @var QM_Collector_WP_Errors Collector.
	 */
	protected $collector;

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 10 );
	}

	public function name() {
		return __( 'WP Errors', 'query-monitor' );
	}

	public function output() {

		$data = $this->collector->get_data();

		if ( empty( $data['errors'] ) && empty( $data['checked'] ) ) {
			return;
		}

		$components = array();

		foreach ( $data['errors'] as $error ) {
			$components[] = $error['trace']->get_component()->name;
		}

		$components = array_unique( $components );

		$this->before_tabular_output();

		echo '<thead>';
		echo '<tr>';
		echo '<th scope="col">' . esc_html__( 'Error Code', 'query-monitor' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'Error Message', 'query-monitor' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'Error Source', 'query-monitor' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'Trace', 'query-monitor' ) . '</th>';
		echo '<th scope="col" class="qm-filterable-column">';
		echo $this->build_filter( 'component', $components, __( 'Component', 'query-monitor' ) ); // WPCS: XSS ok.
		echo '</th>';
		echo '</tr>';
		echo '</thead>';

		echo '<tbody>';

		foreach ( $data['errors'] as $id => $error ) {
			/**
			 * @var WP_Error $wp_error
			 */
			$wp_error = $error['error'];

			/**
			 * @var QM_Backtrace $trace
			 */
			$trace = $error['trace'];

			$caller = $trace->get_caller();

			echo '<tr>';
			echo '<td>' . esc_html( $wp_error->get_error_code() ) . '</td>';
			echo '<td>' . esc_html( $wp_error->get_error_message() ) . '</td>';
			echo '<td>';
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo self::output_filename( $caller['display'], $caller['calling_file'], $caller['calling_line'] );
			echo '</td>';
			echo '<td>';

			if ( isset( $data['checked'][ $id ] ) ) {
				echo '<ol>';
				foreach ( $data['checked'][ $id ] as $check ) {
					$check_caller = $check['trace']->get_caller();
					echo '<li>';
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo self::output_filename( $check_caller['display'], $check_caller['calling_file'], $check_caller['calling_line'] );
					echo '</li>';
				}
				echo '</ol>';
			} else {
				echo 'No';
			}

			echo '</td>';
			echo '<td>' . esc_html( $trace->get_component()->name ) . '</td>';
			echo '</tr>';
		}
		echo '</tbody>';

		$this->after_tabular_output();
	}

	public function admin_menu( array $menu ) {
		$title = __( 'WP Errors', 'query-monitor' );

		$menu[ $this->collector->id() ] = $this->menu( array(
			'title' => $title,
		) );
		return $menu;

	}

}

function register_qm_output_html_wp_errors( array $output, QM_Collectors $collectors ) {
	$collector = QM_Collectors::get( 'wp_errors' );
	if ( $collector ) {
		$output['wp_errors'] = new QM_Output_Html_WP_Errors( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_wp_errors', 110, 2 );
