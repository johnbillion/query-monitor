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

		echo '<div class="qm qm-half" id="' . esc_attr( $this->collector->id() ) . '">';
		echo '<table cellspacing="0">';
		echo '<caption>' . esc_html( $this->collector->name() ) . '</caption>';
		echo '<thead>';
		echo '<tr class="screen-reader-text">';
		echo '<th scope="col">' . esc_html__( 'Data', 'query-monitor' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'Value', 'query-monitor' ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		echo '<tr>';
		echo '<th scope="row">' . esc_html__( 'Template File', 'query-monitor' ) . '</th>';

		if ( ! empty( $data['template_path'] ) ) {
			if ( $data['is_child_theme'] ) {
				echo '<td class="qm-ltr">' . self::output_filename( $data['theme_template_file'], $data['template_path'] ) . '</td>'; // WPCS: XSS ok.
			} else {
				echo '<td class="qm-ltr">' . self::output_filename( $data['template_file'], $data['template_path'] ) . '</td>'; // WPCS: XSS ok.
			}
		} else {
			echo '<td><em>' . esc_html__( 'Unknown', 'query-monitor' ) . '</em></td>';
		}

		echo '</tr>';

		if ( ! empty( $data['template_hierarchy'] ) ) {

			echo '<tr>';
			echo '<th scope="row">' . esc_html__( 'Template Hierarchy', 'query-monitor' ) . '</th>';
			echo '<td class="qm-ltr qm-wrap"><ol class="qm-numbered"><li>' . implode( '</li><li>', array_map( 'esc_html', $data['template_hierarchy'] ) ) . '</li></ol></td>';
			echo '</tr>';

		}

		echo '<tr>';
		echo '<th scope="row">' . esc_html__( 'Template Parts', 'query-monitor' ) . '</th>';

		if ( ! empty( $data['template_parts'] ) ) {

			if ( $data['is_child_theme'] ) {
				$parts = $data['theme_template_parts'];
			} else {
				$parts = $data['template_parts'];
			}

			echo '<td class="qm-ltr"><ul>';

			foreach ( $parts as $filename => $display ) {
				echo '<li>' . self::output_filename( $display, $filename ) . '</li>'; // WPCS: XSS ok.
			}

			echo '</ul></td>';

		} else {
			echo '<td><em>' . esc_html__( 'None', 'query-monitor' ) . '</em></td>';
		}

		echo '</tr>';

		if ( ! empty( $data['timber_files'] ) ) {
			echo '<tr>';
			echo '<th scope="row">' . esc_html__( 'Timber Files', 'query-monitor' ) . '</th>';
			echo '<td class="qm-ltr"><ul>';

			foreach ( $data['timber_files'] as $filename ) {
				echo '<li>' . esc_html( $filename ) . '</li>'; // WPCS: XSS ok.
			}

			echo '</ul></td>';
			echo '</tr>';
		}

		echo '<tr>';
		if ( $data['is_child_theme'] ) {
			echo '<th scope="row">' . esc_html__( 'Child Theme', 'query-monitor' ) . '</th>';
		} else {
			echo '<th scope="row">' . esc_html__( 'Theme', 'query-monitor' ) . '</th>';
		}
		echo '<td class="qm-ltr">' . esc_html( $data['stylesheet'] ) . '</td>';
		echo '</tr>';

		if ( $data['is_child_theme'] ) {
			echo '<tr>';
			echo '<th scope="row">' . esc_html__( 'Parent Theme', 'query-monitor' ) . '</th>';
			echo '<td class="qm-ltr">' . esc_html( $data['template'] ) . '</td>';
			echo '</tr>';
		}

		if ( ! empty( $data['body_class'] ) ) {

			echo '<tr>';
			echo '<th scope="row">' . esc_html__( 'Body Classes', 'query-monitor' ) . '</th>';
			echo '<td class="qm-ltr"><ul>';

			foreach ( $data['body_class'] as $class ) {
				echo '<li>' . esc_html( $class ) . '</li>';
			}

			echo '</ul></td>';
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
				'title' => esc_html( sprintf(
					/* translators: %s: Template file name */
					__( 'Template: %s', 'query-monitor' ),
					( $data['is_child_theme'] ? $data['theme_template_file'] : $data['template_file'] )
				) ),
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
