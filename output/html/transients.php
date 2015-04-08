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

class QM_Output_Html_Transients extends QM_Output_Html {

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 100 );
	}

	public function output() {

		$data = $this->collector->get_data();

		echo '<div class="qm" id="' . esc_attr( $this->collector->id() ) . '">';
		echo '<table cellspacing="0">';
		echo '<thead>';
		echo '<tr>';
		echo '<th>' . __( 'Transient Set', 'query-monitor' ) . '</th>';
		if ( is_multisite() ) {
			echo '<th>' . __( 'Type', 'query-monitor' ) . '</th>';
		}
		if ( !empty( $data['trans'] ) and isset( $data['trans'][0]['expiration'] ) ) {
			echo '<th>' . __( 'Expiration', 'query-monitor' ) . '</th>';
		}
		echo '<th>' . __( 'Call Stack', 'query-monitor' ) . '</th>';
		echo '<th>' . __( 'Component', 'query-monitor' ) . '</th>';
		echo '</tr>';
		echo '</thead>';

		if ( !empty( $data['trans'] ) ) {

			echo '<tbody>';

			foreach ( $data['trans'] as $row ) {
				$stack = $row['trace']->get_stack();
				$transient = str_replace( array(
					'_site_transient_',
					'_transient_'
				), '', $row['transient'] );
				$type = ( is_multisite() ) ? "<td valign='top'>{$row['type']}</td>\n" : '';
				if ( 0 === $row['expiration'] ) {
					$row['expiration'] = '<em>' . __( 'none', 'query-monitor' ) . '</em>';
				}
				$expiration = ( isset( $row['expiration'] ) ) ? "<td valign='top'>{$row['expiration']}</td>\n" : '';

				$component = $row['trace']->get_component();

				$stack = implode( '<br>', $stack );
				echo "
					<tr>\n
						<td valign='top'>{$transient}</td>\n
						{$type}
						{$expiration}
						<td valign='top' class='qm-nowrap qm-ltr'>{$stack}</td>\n
						<td valign='top' class='qm-nowrap'>{$component->name}</td>\n
					</tr>\n
				";
			}

			echo '</tbody>';

		} else {

			echo '<tbody>';
			echo '<tr>';
			echo '<td colspan="4" style="text-align:center !important"><em>' . __( 'none', 'query-monitor' ) . '</em></td>';
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
			: __( 'Transients Set (%s)', 'query-monitor' );

		$menu[] = $this->menu( array(
			'title' => sprintf( $title, number_format_i18n( $count ) )
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
