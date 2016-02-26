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

class QM_Output_Html_Debug_Bar extends QM_Output_Html {

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 200 );
	}

	public function output() {

		$target = get_class( $this->collector->get_panel() );

		echo '<div class="qm qm-debug-bar" id="' . esc_attr( $this->collector->id() ) . '">';
		echo '<table cellspacing="0">';
		echo '<thead>';
		echo '<tr>';
		echo '<th>' . esc_html( $this->collector->name() ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		echo '<tr>';
		echo '<td>';
		echo '<div id="debug-menu-target-' . esc_attr( $target ) . '" class="debug-menu-target qm-debug-bar-output">';

		$this->collector->render();

		echo '</div>';
		echo '</td>';
		echo '</tr>';

		echo '</tbody>';
		echo '</table>';
		echo '</div>';

	}

}

function register_qm_output_html_debug_bar( array $output, QM_Collectors $collectors ) {
	global $debug_bar;

	if ( empty( $debug_bar ) ) {
		return $output;
	}

	foreach ( $debug_bar->panels as $panel ) {
		$panel_id  = strtolower( get_class( $panel ) );
		$collector = QM_Collectors::get( "debug_bar_{$panel_id}" );

		if ( $collector and $collector->is_visible() ) {
			$output["debug_bar_{$panel_id}"] = new QM_Output_Html_Debug_Bar( $collector );
		}
	}

	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_debug_bar', 200, 2 );
