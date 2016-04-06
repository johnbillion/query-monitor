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

class QM_Output_Html_Conditionals extends QM_Output_Html {

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 1000 );
	}

	public function output() {

		$data = $this->collector->get_data();

		$cols = 6;
		$i = 0;

		echo '<div class="qm" id="' . esc_attr( $this->collector->id() ) . '">';
		echo '<table cellspacing="0">';
		echo '<thead>';
		echo '<tr>';
		echo '<th colspan="' . absint( $cols ) . '">' . esc_html( $this->collector->name() ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		foreach ( $data['conds']['true'] as $cond ) {
			$i++;
			if ( 1 === $i % $cols ) {
				echo '<tr>';
			}
			echo '<td class="qm-ltr qm-true">' . esc_html( $cond ) . '()&nbsp;&#x2713;</td>';
			if ( 0 === $i % $cols ) {
				echo '</tr>';
			}
		}

		foreach ( $data['conds']['false'] as $cond ) {
			$i++;
			if ( 1 === $i % $cols ) {
				echo '<tr>';
			}
			echo '<td class="qm-ltr qm-false">' . esc_html( $cond ) . '()</td>';
			if ( 0 === $i % $cols ) {
				echo '</tr>';
			}
		}

		$fill = ( $cols - ( $i % $cols ) );
		if ( $fill && ( $fill !== $cols ) ) {
			echo '<td colspan="' . absint( $fill ) . '">&nbsp;</td>';
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
				'title' => esc_html( $cond . '()' ),
				'id'    => 'query-monitor-conditionals-' . esc_attr( $cond ),
				'meta'  => array( 'classname' => 'qm-true qm-ltr' )
			) );
		}

		return $menu;

	}

}

function register_qm_output_html_conditionals( array $output, QM_Collectors $collectors ) {
	if ( $collector = QM_Collectors::get( 'conditionals' ) ) {
		$output['conditionals'] = new QM_Output_Html_Conditionals( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_conditionals', 50, 2 );
