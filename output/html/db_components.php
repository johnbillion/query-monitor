<?php
/**
 * Database query calling component output for HTML pages.
 *
 * @package query-monitor
 */

class QM_Output_Html_DB_Components extends QM_Output_Html {

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 40 );
	}

	public function output() {

		$data = $this->collector->get_data();

		if ( empty( $data['types'] ) || empty( $data['times'] ) ) {
			return;
		}

		$total_time  = 0;
		$span = count( $data['types'] ) + 2;

		echo '<div class="qm" id="' . esc_attr( $this->collector->id() ) . '">';
		echo '<table class="qm-sortable">';
		echo '<caption>' . esc_html( $this->collector->name() ) . '</caption>';
		echo '<thead>';

		echo '<tr>';
		echo '<th scope="col">' . esc_html__( 'Component', 'query-monitor' ) . '</th>';

		foreach ( $data['types'] as $type_name => $type_count ) {
			echo '<th scope="col" class="qm-num qm-sortable-column">';
			echo $this->build_sorter( $type_name ); // WPCS: XSS ok;
			echo '</th>';
		}

		echo '<th scope="col" class="qm-num qm-sorted-desc qm-sortable-column">';
		echo $this->build_sorter( __( 'Time', 'query-monitor' ) ); // WPCS: XSS ok;
		echo '</th>';
		echo '</tr>';

		echo '</thead>';

		echo '<tbody>';

		foreach ( $data['times'] as $row ) {
			$total_time  += $row['ltime'];

			echo '<tr>';
			echo '<td><a href="#" class="qm-filter-trigger" data-qm-target="db_queries-wpdb" data-qm-filter="component" data-qm-value="' . esc_attr( $row['component'] ) . '">' . esc_html( $row['component'] ) . '</a></td>';

			foreach ( $data['types'] as $type_name => $type_count ) {
				if ( isset( $row['types'][ $type_name ] ) ) {
					echo '<td class="qm-num">' . esc_html( number_format_i18n( $row['types'][ $type_name ] ) ) . '</td>';
				} else {
					echo '<td class="qm-num">&nbsp;</td>';
				}
			}

			echo '<td class="qm-num" data-qm-sort-weight="' . esc_attr( $row['ltime'] ) . '">' . esc_html( number_format_i18n( $row['ltime'], 4 ) ) . '</td>';
			echo '</tr>';

		}

		echo '</tbody>';
		echo '<tfoot>';

		$total_stime = number_format_i18n( $total_time, 4 );

		echo '<tr>';
		echo '<td>&nbsp;</td>';

		foreach ( $data['types'] as $type_name => $type_count ) {
			echo '<td class="qm-num">' . esc_html( number_format_i18n( $type_count ) ) . '</td>';
		}

		echo '<td class="qm-num">' . esc_html( $total_stime ) . '</td>';
		echo '</tr>';
		echo '</tfoot>';

		echo '</table>';
		echo '</div>';

	}

	public function admin_menu( array $menu ) {
		$data = $this->collector->get_data();

		if ( empty( $data['types'] ) || empty( $data['times'] ) ) {
			return $menu;
		}

		if ( $dbq = QM_Collectors::get( 'db_queries' ) ) {
			$dbq_data = $dbq->get_data();
			if ( isset( $dbq_data['component_times'] ) ) {
				$menu[] = $this->menu( array(
					'title' => esc_html__( 'Queries by Component', 'query-monitor' ),
				) );
			}
		}
		return $menu;

	}

}

function register_qm_output_html_db_components( array $output, QM_Collectors $collectors ) {
	if ( $collector = QM_Collectors::get( 'db_components' ) ) {
		$output['db_components'] = new QM_Output_Html_DB_Components( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_db_components', 40, 2 );
