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

class QM_Output_Html_Request extends QM_Output_Html {

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 50 );
	}

	public function output() {

		$data = $this->collector->get_data();

		echo '<div class="qm qm-half" id="' . esc_attr( $this->collector->id() ) . '">';
		echo '<table cellspacing="0">';
		echo '<tbody>';

		foreach ( array(
			'request'       => __( 'Request', 'query-monitor' ),
			'matched_rule'  => __( 'Matched Rule', 'query-monitor' ),
			'matched_query' => __( 'Matched Query', 'query-monitor' ),
			'query_string'  => __( 'Query String', 'query-monitor' ),
		) as $item => $name ) {

			if ( !isset( $data['request'][$item] ) ) {
				continue;
			}

			if ( ! empty( $data['request'][$item] ) ) {
				if ( in_array( $item, array( 'request', 'matched_query', 'query_string' ) ) ) {
					$value = self::format_url( $data['request'][$item] );
				} else {
					$value = esc_html( $data['request'][$item] );
				}
			} else {
				$value = '<em>' . __( 'none', 'query-monitor' ) . '</em>';
			}

			echo '<tr>';
			echo '<td valign="top">' . $name . '</td>';
			echo '<td valign="top" colspan="2">' . $value . '</td>';
			echo '</tr>';
		}

		$rowspan = isset( $data['qvars'] ) ? count( $data['qvars'] ) : 1;

		echo '<tr>';
		echo '<td rowspan="' . $rowspan . '">' . __( 'Query Vars', 'query-monitor' ) . '</td>';

		if ( !empty( $data['qvars'] ) ) {

			$first = true;

			foreach( $data['qvars'] as $var => $value ) {

				if ( !$first ) {
					echo '<tr>';
				}

				if ( isset( $data['plugin_qvars'][$var] ) ) {
					echo "<td valign='top'><span class='qm-current'>{$var}</span></td>";
				} else {
					echo "<td valign='top'>{$var}</td>";
				}

				if ( is_array( $value ) or is_object( $value ) ) {
					echo '<td valign="top"><pre>';
					print_r( $value );
					echo '</pre></td>';
				} else {
					$value = esc_html( $value );
					echo "<td valign='top'>{$value}</td>";
				}

				echo '</tr>';

				$first = false;

			}

		} else {

			echo '<td colspan="2"><em>' . __( 'none', 'query-monitor' ) . '</em></td>';
			echo '</tr>';

		}

		if ( !empty( $data['multisite'] ) ) {

			$rowspan = count( $data['multisite'] );

			echo '<tr>';
			echo '<td rowspan="' . $rowspan . '">' . __( 'Multisite', 'query-monitor' ) . '</td>';

			$first = true;

			foreach( $data['multisite'] as $var => $value ) {

				if ( !$first ) {
					echo '<tr>';
				}

				echo "<td valign='top'>{$var}</td>";

				echo '<td valign="top"><pre>';
				print_r( $value );
				echo '</pre></td>';

				echo '</tr>';

				$first = false;

			}
		}

		if ( !empty( $data['queried_object'] ) ) {

			$vars = get_object_vars( $data['queried_object'] );

			echo '<tr>';
			echo '<td valign="top">' . __( 'Queried Object', 'query-monitor' ) . '</td>';
			echo '<td valign="top" colspan="2" class="qm-has-inner">';
			echo '<div class="qm-inner-toggle">' . $data['queried_object_title'] . ' (' . get_class( $data['queried_object'] ) . ' object) (<a href="#" class="qm-toggle" data-on="' . esc_attr__( 'Show', 'query-monitor' ) . '" data-off="' . esc_attr__( 'Hide', 'query-monitor' ) . '">' . __( 'Show', 'query-monitor' ) . '</a>)</div>';

			echo '<div class="qm-toggled">';
			self::output_inner( $vars );
			echo '</div>';

			echo '</td>';
			echo '</tr>';

		}

		echo '</tbody>';
		echo '</table>';
		echo '</div>';

	}

	public function admin_menu( array $menu ) {

		$data  = $this->collector->get_data();
		$count = isset( $data['plugin_qvars'] ) ? count( $data['plugin_qvars'] ) : 0;

		$title = ( empty( $count ) )
			? __( 'Request', 'query-monitor' )
			: __( 'Request (+%s)', 'query-monitor' );

		$menu[] = $this->menu( array(
			'title' => sprintf( $title, number_format_i18n( $count ) )
		) );
		return $menu;

	}

}

function register_qm_output_html_request( array $output, QM_Collectors $collectors ) {
	if ( $collector = QM_Collectors::get( 'request' ) ) {
		$output['request'] = new QM_Output_Html_Request( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_request', 60, 2 );
