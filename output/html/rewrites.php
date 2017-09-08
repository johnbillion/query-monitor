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

class QM_Output_Html_Rewrites extends QM_Output_Html {

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 55 );
	}

	public function output() {

		$data = $this->collector->get_data();

		echo '<div class="qm qm-half" id="' . esc_attr( $this->collector->id() ) . '">';
		echo '<table cellspacing="0">';

		echo '<caption>' . esc_html__( 'All Matching Rewrite Rules', 'query-monitor' ) . '</caption>';
		echo '<thead class="screen-reader-text">';
		echo '<tr>';
		echo '<th scope="col">' . esc_html__( 'Rule', 'query-monitor' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'Query', 'query-monitor' ) . '</th>';
		echo '</tr>';
		echo '</thead>';

		echo '<tbody>';

		if ( ! empty( $data['matching'] ) ) {

			foreach ( $data['matching'] as $rule => $query ) {

				$query = str_replace( 'index.php?', '', $query );

				echo '<tr>';
				echo '<td class="qm-ltr">' . esc_html( $rule ) . '</td>';
				echo '<td class="qm-ltr">';
				echo self::format_url( $query ); // WPCS: XSS ok.
				echo '</td>';
				echo '</tr>';

			}

		} else {

			echo '<tr>';
			echo '<td colspan="2"><em>' . esc_html__( 'None', 'query-monitor' ) . '</em></td>';
			echo '</tr>';

		}

		echo '</tbody>';
		echo '</table>';
		echo '</div>';

	}

}

function register_qm_output_html_rewrites( array $output, QM_Collectors $collectors ) {
	if ( $collector = QM_Collectors::get( 'rewrites' ) ) {
		$output['rewrites'] = new QM_Output_Html_Rewrites( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_rewrites', 65, 2 );
