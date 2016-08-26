<?php
/*
Copyright 2009-2016 John Blackbourn

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

*/

class QM_Output_Html_Admin extends QM_Output_Html {

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 60 );
	}

	public function output() {

		$data = $this->collector->get_data();

		if ( empty( $data['current_screen'] ) ) {
			return;
		}

		echo '<div class="qm qm-half" id="' . esc_attr( $this->collector->id() ) . '">';
		echo '<table cellspacing="0">';
		echo '<caption>' . esc_html( $this->collector->name() ) . '</caption>';
		echo '<thead class="screen-reader-text">';
		echo '<tr>';
		echo '<th>' . esc_html__( 'Data', 'query-monitor' ) . '</th>';
		echo '<th>' . esc_html__( 'Property', 'query-monitor' ) . '</th>';
		echo '<th>' . esc_html__( 'Value', 'query-monitor' ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		$first = true;

		foreach ( $data['current_screen'] as $key => $value ) {
			echo '<tr>';

			if ( $first ) {
				echo '<th class="qm-ltr" rowspan="' . count( $data['current_screen'] ) . '">get_current_screen()</th>';
			}

			echo '<th>' . esc_html( $key ) . '</th>';
			echo '<td>' . esc_html( $value ) . '</td>';
			echo '</tr>';

			$first = false;
		}

		echo '<tr>';
		echo '<th class="qm-ltr">$pagenow</th>';
		echo '<td colspan="2">' . esc_html( $data['pagenow'] ) . '</td>';
		echo '</tr>';

		if ( ! empty( $data['list_table'] ) ) {

			echo '<tr>';
			echo '<th rowspan="2">' . esc_html__( 'Column Filters', 'query-monitor' ) . '</th>';
			echo '<td colspan="2">' . $data['list_table_markup']['columns_filter'] . '</td>'; // WPCS: XSS ok;
			echo '</tr>';
			echo '<tr>';
			echo '<td colspan="2">' . $data['list_table_markup']['sortables_filter'] . '</td>'; // WPCS: XSS ok;
			echo '</tr>';

			echo '<tr>';
			echo '<th>' . esc_html__( 'Column Action', 'query-monitor' ) . '</th>';
			echo '<td colspan="2">' . $data['list_table_markup']['column_action'] . '</td>'; // WPCS: XSS ok;
			echo '</tr>';

		}

		echo '</tbody>';
		echo '</table>';
		echo '</div>';

	}

}

function register_qm_output_html_admin( array $output, QM_Collectors $collectors ) {
	if ( $collector = QM_Collectors::get( 'admin' ) ) {
		$output['admin'] = new QM_Output_Html_Admin( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_admin', 70, 2 );
