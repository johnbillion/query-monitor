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

class QM_Output_Html_Caps extends QM_Output_Html {

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 100 );
	}

	public function output() {

		$data = $this->collector->get_data();

		echo '<div class="qm" id="' . esc_attr( $this->collector->id() ) . '">';
		echo '<table cellspacing="0">';

		echo '<caption class="screen-reader-text">' . esc_html( $this->collector->name() ) . '</caption>';

		echo '<thead>';
		echo '<tr>';
		echo '<th scope="col">';
		echo $this->build_filter( 'name', $data['parts'], __( 'Capability Check', 'query-monitor' ) ); // WPCS: XSS ok;
		echo '</th>';
		echo '<th scope="col">' . esc_html__( 'Caller', 'query-monitor' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'Call Stack', 'query-monitor' ) . '</th>';
		echo '<th scope="col">';
		echo $this->build_filter( 'component', $data['components'], __( 'Component', 'query-monitor' ) ); // WPCS: XSS ok.
		echo '</th>';
		echo '</tr>';
		echo '</thead>';

		echo '<tbody>';

		foreach ( $data['caps'] as $row ) {
			$component = $row['trace']->get_component();

			$row_attr = array();
			$row_attr['data-qm-name']      = implode( ' ', $row['parts'] );
			$row_attr['data-qm-component'] = $component->name;

			$attr = '';

			foreach ( $row_attr as $a => $v ) {
				$attr .= ' ' . $a . '="' . esc_attr( $v ) . '"';
			}

			printf( // WPCS: XSS ok.
				'<tr %s>',
				$attr
			);

			printf(
				'<td class="qm-ltr">%s</td>',
				esc_html( $row['name'] )
			);

			$stack          = array();
			$filtered_trace = $row['trace']->get_display_trace();
			array_pop( $filtered_trace );

			$pure = $row['trace']->get_trace();
			$purest = count( $filtered_trace ) - 2;

			if ( isset( $filtered_trace[ $purest ]['display'] ) ) {
				$pure_name = $filtered_trace[ $purest ]['display'];
			} else {
				$pure_name = QM_Util::standard_dir( $pure[1]['file'], '' ) . ':' . $pure[1]['line'];
			}

			printf( // WPCS: XSS ok.
				'<td class="qm-nowrap qm-ltr">%s</td>',
				self::output_filename( $pure_name, $pure[1]['file'], $pure[1]['line'] )
			);

			foreach ( $filtered_trace as $item ) {
				$stack[] = self::output_filename( $item['display'], $item['calling_file'], $item['calling_line'] );
			}

			printf( // WPCS: XSS ok.
				'<td class="qm-nowrap qm-ltr"><ol class="qm-numbered"><li>%s</li></ol></td>',
				implode( '</li><li>', $stack )
			);
			printf(
				'<td class="qm-nowrap">%s</td>',
				esc_html( $component->name )
			);

			echo '</tr>';

		}

		echo '</tbody>';

		echo '</table>';
		echo '</div>';

	}

	public function admin_menu( array $menu ) {
		$menu[] = $this->menu( array(
			'title' => $this->collector->name(),
		) );
		return $menu;

	}

}

function register_qm_output_html_caps( array $output, QM_Collectors $collectors ) {
	if ( $collector = QM_Collectors::get( 'caps' ) ) {
		$output['caps'] = new QM_Output_Html_Caps( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_caps', 100, 2 );
