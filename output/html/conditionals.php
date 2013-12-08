<?php
/*

Copyright 2013 John Blackbourn

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

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'query_monitor_menus', array( $this, 'admin_menu' ), 120 );
	}

	public function output() {

		$data = $this->collector->get_data();

		$cols = 5;
		$i = 0;
		$w = floor( 100 / $cols );

		echo '<div class="qm" id="' . $this->collector->id() . '">';
		echo '<table cellspacing="0">';
		echo '<thead>';
		echo '<tr>';
		echo '<th colspan="' . $cols . '">' . $this->collector->name() . '</th>';
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

		$fill = ( $cols - ( $i % $cols ) );
		if ( $fill and ( $fill != $cols ) ) {
			echo '<td colspan="' . $fill . '">&nbsp;</td>';
			echo '</tr>';
		}

		echo '</tbody>';
		echo '</table>';
		echo '</div>';

	}

	public function admin_menu( array $menu ) {

		$data = $this->collector->get_data();

		foreach ( $data['conds']['true'] as $cond ) {
			$menu[] = $this->menu( array(
				'title' => $cond . '()',
				'id'    => 'query-monitor-' . $cond,
				'meta'  => array( 'classname' => 'qm-true qm-ltr' )
			) );
		}

		return $menu;

	}

}

function register_qm_output_html_conditionals( QM_Output $output = null, QM_Collector $collector ) {
	return new QM_Output_Html_Conditionals( $collector );
}

add_filter( 'query_monitor_output_html_conditionals', 'register_qm_output_html_conditionals', 10, 2 );
