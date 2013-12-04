<?php
/*

© 2013 John Blackbourn

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

*/

class QM_Output_Html_Query_Vars extends QM_Output_Html {

	public function output() {

		$data = $this->component->get_data();

		echo '<div class="qm qm-half" id="' . $this->component->id() . '">';
		echo '<table cellspacing="0">';
		echo '<thead>';
		echo '<tr>';
		echo '<th colspan="2">' . $this->component->name() . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		if ( !empty( $data['qvars'] ) ) {

			foreach( $data['qvars'] as $var => $value ) {
				echo '<tr>';
				if ( isset( $data['plugin_qvars'][$var] ) )
					echo "<td valign='top'><span class='qm-current'>{$var}</span></td>";
				else
					echo "<td valign='top'>{$var}</td>";
				if ( is_array( $value ) or is_object( $value ) ) {
					echo '<td valign="top"><pre>';
					print_r( $value );
					echo '</pre></td>';
				} else {
					$value = esc_html( $value );
					echo "<td valign='top'>{$value}</td>";
				}
				echo '</tr>';
			}

		} else {

			echo '<tr>';
			echo '<td colspan="2" style="text-align:center !important"><em>' . __( 'none', 'query-monitor' ) . '</em></td>';
			echo '</tr>';

		}

		echo '</tbody>';
		echo '</table>';
		echo '</div>';

	}

}

function register_qm_query_vars_output_html( QM_Output $output = null, QM_Component $component ) {
	return new QM_Output_Html_Query_Vars( $component );
}

add_filter( 'query_monitor_output_html_query_vars', 'register_qm_query_vars_output_html', 10, 2 );
