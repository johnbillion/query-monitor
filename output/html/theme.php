<?php
/*

Copyright 2014 John Blackbourn

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

*/

class QM_Output_Html_Theme extends QM_Output_Html {

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'query_monitor_menus', array( $this, 'admin_menu' ), 60 );
	}

	public function output() {

		$data = $this->collector->get_data();

		if ( empty( $data ) )
			return;

		echo '<div class="qm qm-half" id="' . $this->collector->id() . '">';
		echo '<table cellspacing="0">';
		echo '<thead>';
		echo '<tr>';
		echo '<th colspan="2">' . $this->collector->name() . '</th>';
		echo '</tr>';
		echo '</thead>';

		echo '<tbody>';
		echo '<tr>';
		echo '<td>' . __( 'Template', 'query-monitor' ) . '</td>';
		echo '<td>' . self::output_filename( $data['template_file'], $data['template_path'] ) . '</td>';
		echo '</tr>';

		if ( !empty( $data['body_class'] ) ) {

			echo '<tr>';
			echo '<td rowspan="' . count( $data['body_class'] ) . '">' . __( 'Body Classes', 'query-monitor' ) . '</td>';
			$first = true;

			foreach ( $data['body_class'] as $class ) {

				if ( !$first )
					echo '<tr>';

				echo "<td>{$class}</td>";
				echo '</tr>';

				$first = false;

			}

		}

		echo '<tr>';
		echo '<td>' . __( 'Theme', 'query-monitor' ) . '</td>';
		echo "<td>{$data['stylesheet']}</td>";
		echo '</tr>';

		if ( $data['stylesheet'] != $data['template'] ) {
			echo '<tr>';
			echo '<td>' . __( 'Parent Theme', 'query-monitor' ) . '</td>';
			echo "<td>{$data['template']}</td>";
			echo '</tr>';
		}

		echo '</tbody>';
		echo '</table>';
		echo '</div>';

	}

	public function admin_menu( array $menu ) {

		$data = $this->collector->get_data();

		if ( isset( $data['template_file'] ) ) {
			$menu[] = $this->menu( array(
				'title' => sprintf( __( 'Template: %s', 'query-monitor' ), $data['template_file'] )
			) );
		}
		return $menu;

	}

}

function register_qm_output_html_theme( QM_Output $output = null, QM_Collector $collector ) {
	return new QM_Output_Html_Theme( $collector );
}

add_filter( 'query_monitor_output_html_theme', 'register_qm_output_html_theme', 10, 2 );
