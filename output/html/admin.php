<?php declare(strict_types = 1);
/**
 * Admin screen output for HTML pages.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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

	/**
	 * @return string
	 */
	public function name() {
		return __( 'Admin Screen', 'query-monitor' );
	}

	/**
	 * @return void
	 */
	public function output() {

		/** @var QM_Data_Admin $data */
		$data = $this->collector->get_data();

		if ( empty( $data->current_screen ) ) {
			return;
		}

		$this->before_non_tabular_output();

		echo '<section>' . "\n";
		echo '<h3>get_current_screen()</h3>' . "\n";

		echo '<table>' . "\n";
		echo '<thead class="qm-screen-reader-text">' . "\n";
		echo '<tr>' . "\n";
		echo '<th scope="col">' . esc_html__( 'Property', 'query-monitor' ) . '</th>' . "\n";
		echo '<th scope="col">' . esc_html__( 'Value', 'query-monitor' ) . '</th>' . "\n";
		echo '</tr>' . "\n";
		echo '</thead>' . "\n";
		echo '<tbody>' . "\n";

		foreach ( get_object_vars( $data->current_screen ) as $key => $value ) {
			echo '<tr>' . "\n";
			echo '<th scope="row">' . esc_html( $key ) . '</th>' . "\n";
			echo '<td>' . esc_html( $value ) . '</td>' . "\n";
			echo '</tr>' . "\n";
		}

		echo '</tbody>' . "\n";
		echo '</table>' . "\n";
		echo '</section>' . "\n";

		echo '<section>' . "\n";
		echo '<h3>' . esc_html__( 'Globals', 'query-monitor' ) . '</h3>' . "\n";
		echo '<table>' . "\n";
		echo '<thead class="qm-screen-reader-text">' . "\n";
		echo '<tr>' . "\n";
		echo '<th scope="col">' . esc_html__( 'Global Variable', 'query-monitor' ) . '</th>' . "\n";
		echo '<th scope="col">' . esc_html__( 'Value', 'query-monitor' ) . '</th>' . "\n";
		echo '</tr>' . "\n";
		echo '</thead>' . "\n";
		echo '<tbody>' . "\n";

		$admin_globals = array(
			'pagenow',
			'typenow',
			'taxnow',
			'hook_suffix',
		);

		foreach ( $admin_globals as $key ) {
			echo '<tr>' . "\n";
			echo '<th scope="row">$' . esc_html( $key ) . '</th>' . "\n";
			echo '<td>' . esc_html( $data->{$key} ) . '</td>' . "\n";
			echo '</tr>' . "\n";
		}

		echo '</tbody>' . "\n";
		echo '</table>' . "\n";
		echo '</section>' . "\n";

		if ( ! empty( $data->list_table ) ) {

			echo '<section>' . "\n";
			echo '<h3>' . esc_html__( 'List Table', 'query-monitor' ) . '</h3>' . "\n";

			if ( ! empty( $data->list_table['class_name'] ) ) {
				echo '<h4>' . esc_html__( 'Class:', 'query-monitor' ) . '</h4>' . "\n";
				echo '<p><code>' . esc_html( $data->list_table['class_name'] ) . '</code></p>' . "\n";
			}

			echo '<h4>' . esc_html__( 'Column Filters:', 'query-monitor' ) . '</h4>' . "\n";
			echo '<p><code>' . esc_html( $data->list_table['columns_filter'] ) . '</code></p>' . "\n";
			echo '<p><code>' . esc_html( $data->list_table['sortables_filter'] ) . '</code></p>' . "\n";
			echo '<h4>' . esc_html__( 'Column Action:', 'query-monitor' ) . '</h4>' . "\n";
			echo '<p><code>' . esc_html( $data->list_table['column_action'] ) . '</code></p>' . "\n";
			echo '</section>' . "\n";

		}

		$this->after_non_tabular_output();
	}

}

/**
 * @param array<string, QM_Output> $output
 * @param QM_Collectors $collectors
 * @return array<string, QM_Output>
 */
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
