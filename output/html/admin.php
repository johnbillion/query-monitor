<?php
/**
 * Admin screen output for HTML pages.
 *
 * @package query-monitor
 */

class QM_Output_Html_Admin extends QM_Output_Html {

	/**
	 * Collector instance.
	 *
	 * @var QM_Collector_Admin Collector.
	 */
	protected $collector;

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 60 );
	}

	public function name() {
		return __( 'Admin Screen', 'query-monitor' );
	}

	public function output() {

		$data = $this->collector->get_data();

		if ( empty( $data['current_screen'] ) ) {
			return;
		}

		$this->before_non_tabular_output();

		echo '<section>';
		echo '<h3>get_current_screen()</h3>';

		echo '<table>';
		echo '<thead class="qm-screen-reader-text">';
		echo '<tr>';
		echo '<th scope="col">' . esc_html__( 'Property', 'query-monitor' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'Value', 'query-monitor' ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		foreach ( $data['current_screen'] as $key => $value ) {
			echo '<tr>';
			echo '<th scope="row">' . esc_html( $key ) . '</th>';
			echo '<td>' . esc_html( $value ) . '</td>';
			echo '</tr>';
		}

		echo '</tbody>';
		echo '</table>';
		echo '</section>';

		echo '<section>';
		echo '<h3>' . esc_html__( 'Globals', 'query-monitor' ) . '</h3>';
		echo '<table>';
		echo '<thead class="qm-screen-reader-text">';
		echo '<tr>';
		echo '<th scope="col">' . esc_html__( 'Global Variable', 'query-monitor' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'Value', 'query-monitor' ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		$admin_globals = array(
			'pagenow',
			'typenow',
			'taxnow',
			'hook_suffix',
		);

		foreach ( $admin_globals as $key ) {
			echo '<tr>';
			echo '<th scope="row">$' . esc_html( $key ) . '</th>';
			echo '<td>' . esc_html( $data[ $key ] ) . '</td>';
			echo '</tr>';
		}

		echo '</tbody>';
		echo '</table>';
		echo '</section>';

		if ( ! empty( $data['list_table'] ) ) {

			echo '<section>';
			echo '<h3>' . esc_html__( 'List Table', 'query-monitor' ) . '</h3>';

			if ( ! empty( $data['list_table']['class_name'] ) ) {
				echo '<h4>' . esc_html__( 'Class:', 'query-monitor' ) . '</h4>';
				echo '<p><code>' . esc_html( $data['list_table']['class_name'] ) . '</code></p>';
			}

			echo '<h4>' . esc_html__( 'Column Filters:', 'query-monitor' ) . '</h4>';
			echo '<p><code>' . esc_html( $data['list_table']['columns_filter'] ) . '</code></p>';
			echo '<p><code>' . esc_html( $data['list_table']['sortables_filter'] ) . '</code></p>';
			echo '<h4>' . esc_html__( 'Column Action:', 'query-monitor' ) . '</h4>';
			echo '<p><code>' . esc_html( $data['list_table']['column_action'] ) . '</code></p>';
			echo '</section>';

		}

		$this->after_non_tabular_output();
	}

}

function register_qm_output_html_admin( array $output, QM_Collectors $collectors ) {
	if ( ! is_admin() ) {
		return $output;
	}
	$collector = QM_Collectors::get( 'response' );
	if ( $collector ) {
		$output['response'] = new QM_Output_Html_Admin( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_admin', 70, 2 );
