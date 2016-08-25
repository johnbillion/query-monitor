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

class QM_Output_Html_Transients extends QM_Output_Html {

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 100 );
	}

	public function output() {

		$data = $this->collector->get_data();

		echo '<div class="qm" id="' . esc_attr( $this->collector->id() ) . '">';
		echo '<table cellspacing="0">';

		if ( !empty( $data['trans'] ) ) {

			echo '<caption class="screen-reader-text">' . esc_html__( 'Transients', 'query-monitor' ) . '</caption>';

			echo '<thead>';
			echo '<tr>';
			echo '<th scope="col">' . esc_html__( 'Transient Set', 'query-monitor' ) . '</th>';
			if ( is_multisite() ) {
				echo '<th>' . esc_html__( 'Type', 'query-monitor' ) . '</th>';
			}
			if ( !empty( $data['trans'] ) and isset( $data['trans'][0]['expiration'] ) ) {
				echo '<th scope="col">' . esc_html__( 'Expiration', 'query-monitor' ) . '</th>';
			}
			echo '<th scope="col">' . esc_html__( 'Call Stack', 'query-monitor' ) . '</th>';
			echo '<th scope="col">' . esc_html__( 'Component', 'query-monitor' ) . '</th>';
			echo '</tr>';
			echo '</thead>';

			echo '<tbody>';

			foreach ( $data['trans'] as $row ) {
				$transient = str_replace( array(
					'_site_transient_',
					'_transient_'
				), '', $row['transient'] );

				$component = $row['trace']->get_component();

				echo '<tr>';
				printf(
					'<td>%s</td>',
					esc_html( $transient )
				);
				if ( is_multisite() ) {
					printf(
						'<td>%s</td>',
						esc_html( $row['type'] )
					);
				}

				if ( isset( $row['expiration'] ) ) {
					if ( 0 === $row['expiration'] ) {
						printf(
							'<td><em>%s</em></td>',
							esc_html__( 'none', 'query-monitor' )
						);
					} else {
						printf(
							'<td>%s</td>',
							esc_html( $row['expiration'] )
						);
					}
				}

				$stack          = array();
				$filtered_trace = $row['trace']->get_filtered_trace();
				array_shift( $filtered_trace );

				foreach ( $filtered_trace as $item ) {
					$stack[] = self::output_filename( $item['display'], $item['calling_file'], $item['calling_line'] );
				}

				printf( // WPCS: XSS ok.
					'<td class="qm-nowrap qm-ltr">%s</td>',
					implode( '<br>', $stack )
				);
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
			echo '<th>' . esc_html__( 'Transients Set', 'query-monitor' ) . '</th>';
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

	public function admin_menu( array $menu ) {

		$data  = $this->collector->get_data();
		$count = isset( $data['trans'] ) ? count( $data['trans'] ) : 0;

		$title = ( empty( $count ) )
			? __( 'Transients Set', 'query-monitor' )
			/* translators: %s: Number of transient values that were set */
			: __( 'Transients Set (%s)', 'query-monitor' );

		$menu[] = $this->menu( array(
			'title' => esc_html( sprintf(
				$title,
				number_format_i18n( $count )
			) ),
		) );
		return $menu;

	}

}

function register_qm_output_html_transients( array $output, QM_Collectors $collectors ) {
	if ( $collector = QM_Collectors::get( 'transients' ) ) {
		$output['transients'] = new QM_Output_Html_Transients( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_transients', 100, 2 );
