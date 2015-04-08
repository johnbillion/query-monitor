<?php
/*
Copyright 2009-2015 John Blackbourn

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
		add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 60 );
	}

	public function output() {

		$data = $this->collector->get_data();

		if ( empty( $data['stylesheet'] ) ) {
			return;
		}

		$child_theme = ( $data['stylesheet'] != $data['template'] );

		echo '<div class="qm qm-half" id="' . esc_attr( $this->collector->id() ) . '">';
		echo '<table cellspacing="0">';
		echo '<tbody>';

		echo '<tr>';
		echo '<td>' . __( 'Template File', 'query-monitor' ) . '</td>';
		if ( $child_theme ) {
			echo '<td>' . self::output_filename( $data['theme_template'], $data['template_path'] ) . '</td>';
		} else {
			echo '<td>' . self::output_filename( $data['template_file'], $data['template_path'] ) . '</td>';
		}
		echo '</tr>';

		echo '<tr>';
		if ( $child_theme ) {
			echo '<td>' . __( 'Child Theme', 'query-monitor' ) . '</td>';
		} else {
			echo '<td>' . __( 'Theme', 'query-monitor' ) . '</td>';
		}
		echo '<td>' . esc_html( $data['stylesheet'] ) . '</td>';
		echo '</tr>';

		if ( $child_theme ) {
			echo '<tr>';
			echo '<td>' . __( 'Parent Theme', 'query-monitor' ) . '</td>';
			echo '<td>' . esc_html( $data['template'] ) . '</td>';
			echo '</tr>';
		}

		if ( !empty( $data['body_class'] ) ) {

			echo '<tr>';
			echo '<td rowspan="' . count( $data['body_class'] ) . '">' . __( 'Body Classes', 'query-monitor' ) . '</td>';
			$first = true;

			foreach ( $data['body_class'] as $class ) {

				if ( !$first ) {
					echo '<tr>';
				}

				echo '<td>' . esc_html( $class ) . '</td>';
				echo '</tr>';

				$first = false;

			}

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

function register_qm_output_html_theme( array $output, QM_Collectors $collectors ) {
	if ( $collector = QM_Collectors::get( 'theme' ) ) {
		$output['theme'] = new QM_Output_Html_Theme( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_theme', 70, 2 );
