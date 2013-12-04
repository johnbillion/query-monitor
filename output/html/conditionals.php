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

class QM_Output_Html_Conditionals extends QM_Output_Html {

	public function output() {

		$data = $this->component->get_data();

		$cols = 5;
		$i = 0;
		$w = floor( 100 / $cols );

		echo '<div class="qm" id="' . $this->component->id() . '">';
		echo '<table cellspacing="0">';
		echo '<thead>';
		echo '<tr>';
		echo '<th colspan="' . $cols . '">' . $this->component->name() . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		foreach ( $data['conds']['true'] as $cond ) {
			$i++;
			if ( 1 === $i%$cols )
				echo '<tr>';
			echo '<td class="qm-ltr qm-true" width="' . $w . '%">' . $cond . '()</td>';
			if ( 0 === $i%$cols )
				echo '</tr>';
		}

		foreach ( $data['conds']['false'] as $cond ) {
			$i++;
			if ( 1 === $i%$cols )
				echo '<tr>';
			echo '<td class="qm-ltr qm-false" width="' . $w . '%">' . $cond . '()</td>';
			if ( 0 === $i%$cols )
				echo '</tr>';
		}
		$fill = ($cols-($i%$cols));
		if ( $fill ) {
			echo '<td colspan="' . $fill . '">&nbsp;</td>';
			echo '</tr>';
		}

		echo '</tbody>';
		echo '</table>';
		echo '</div>';

	}

}

function register_qm_conditionals_output_html( QM_Output $output = null, QM_Component $component ) {
	return new QM_Output_Html_Conditionals( $component );
}

add_filter( 'query_monitor_output_html_conditionals', 'register_qm_conditionals_output_html', 10, 2 );
