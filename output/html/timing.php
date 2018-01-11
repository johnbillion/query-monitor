<?php
/*
Copyright 2009-2017 John Blackbourn

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

*/

class QM_Output_Html_Timing extends QM_Output_Html {

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 100 );
	}

	public function output() {

		$data = $this->collector->get_data();

		echo '<div class="qm" id="' . esc_attr( $this->collector->id() ) . '">';
		echo '<table cellspacing="0">';

		if ( ! empty( $data['timing'] ) ) {

			echo '<caption class="screen-reader-text">' . esc_html__( 'Function Timing', 'query-monitor' ) . '</caption>';

			echo '<thead>';
			echo '<tr>';
			echo '<th scope="col">' . esc_html__( 'Tracked function', 'query-monitor' ) . '</th>';
			echo '<th scope="col">' . esc_html__( 'Time', 'query-monitor' ) . '</th>';
			echo '<th scope="col">' . esc_html__( 'Caller', 'query-monitor' ) . '</th>';
			echo '</tr>';
			echo '</thead>';

			echo '<tbody>';

			foreach ( $data['timing'] as $row ) {

				$component = $row['trace']->get_component();

				echo '<tr>';
				printf(
					'<td class="qm-ltr">%s</td>',
					esc_html( $row['function'] )
				);

				printf(
					'<td>%s</td>',
					esc_html( $row['function_time'] )
				);

				$stack          = array();
				$filtered_trace = $row['trace']->get_display_trace();
				array_pop( $filtered_trace );
				array_pop( $filtered_trace );

				foreach ( $filtered_trace as $item ) {
					$stack[] = self::output_filename( $item['display'], $item['calling_file'], $item['calling_line'] );
				}

				$caller = array_pop( $stack );

				if ( ! empty( $stack ) ) {
					echo $this->build_toggler(); // WPCS: XSS ok;
					echo '<div class="qm-toggled"><li>' . implode( '</li><li>', $stack ) . '</li></div>'; // WPCS: XSS ok.
				}

				echo "<li>{$caller}</li>"; // WPCS: XSS ok.
				echo '</ol></td>';

				printf(
					'<td class="qm-nowrap">%s</td>',
					esc_html( $component->name )
				);

				echo '</tr>';

			}

			echo '</tbody>';

		} else {

			echo '<thead>';
			echo '<tr>';
			echo '<th>' . esc_html__( 'Function timing', 'query-monitor' ) . '</th>';
			echo '</tr>';
			echo '</thead>';

			echo '<tbody>';
			echo '<tr>';
			echo '<td style="text-align:center !important"><em>' . esc_html__( 'none', 'query-monitor' ) . '</em></td>';
			echo '</tr>';
			echo '</tbody>';

		}

		echo '</table>';
		echo '</div>';

	}

}

function register_qm_output_html_timing( array $output, QM_Collectors $collectors ) {
	if ( $collector = QM_Collectors::get( 'timing' ) ) {
		$output['timing'] = new QM_Output_Html_Timing( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_timing', 100, 2 );
