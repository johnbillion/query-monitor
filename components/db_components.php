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

class QM_Component_DB_Components extends QM_Component {

	var $id = 'db_components';

	function __construct() {
		parent::__construct();
		add_filter( 'query_monitor_menus', array( $this, 'admin_menu' ), 40 );
	}

	function process() {

		if ( $dbq = $this->get_component( 'db_queries' ) ) {
			if ( isset( $dbq->data['component_times'] ) ) {
				$this->data['times'] = $dbq->data['component_times'];
			}
			if ( isset( $dbq->data['types'] ) ) {
				$this->data['types'] = $dbq->data['types'];
			}
		}

	}

	function admin_menu( array $menu ) {

		if ( $dbq = $this->get_component( 'db_queries' ) and isset( $dbq->data['component_times'] ) ) {
			$menu[] = $this->menu( array(
				'title' => __( 'Queries by Component', 'query-monitor' )
			) );
		}
		return $menu;

	}

	function output_html( array $args, array $data ) {

		if ( empty( $data ) )
			return;

		$total_time  = 0;
		$total_calls = 0;

		echo '<div class="qm qm-half" id="' . $args['id'] . '">';
		echo '<table cellspacing="0">';
		echo '<thead>';
		echo '<tr>';
		echo '<th>' . _x( 'Component', 'Query component', 'query-monitor' ) . '</th>';

		if ( !empty( $data['types'] ) ) {
			foreach ( $data['types'] as $type_name => $type_count )
				echo '<th>' . $type_name . '</th>';
		}

		echo '<th>' . __( 'Time', 'query-monitor' ) . '</th>';
		echo '</tr>';
		echo '</thead>';

		if ( !empty( $data['times'] ) ) {

			echo '<tbody>';

			usort( $data['times'], 'QM_Util::sort' );

			foreach ( $data['times'] as $component => $row ) {
				$total_time  += $row['ltime'];
				$total_calls += $row['calls'];
				$stime = number_format_i18n( $row['ltime'], 4 );
				$ltime = number_format_i18n( $row['ltime'], 10 );

				echo '<tr>';
				echo "<td valign='top' class='qm-ltr'>{$row['component']}</td>";

				foreach ( $data['types'] as $type_name => $type_count ) {
					if ( isset( $row['types'][$type_name] ) )
						echo "<td valign='top'>{$row['types'][$type_name]}</td>";
					else
						echo "<td valign='top'>&nbsp;</td>";
				}

				echo "<td valign='top' title='{$ltime}'>{$stime}</td>";
				echo '</tr>';

			}

			echo '</tbody>';
			echo '<tfoot>';

			$total_stime = number_format_i18n( $total_time, 4 );
			$total_ltime = number_format_i18n( $total_time, 10 );

			echo '<tr>';
			echo '<td>&nbsp;</td>';

			foreach ( $data['types'] as $type_name => $type_count )
				echo '<td>' . number_format_i18n( $type_count ) . '</td>';

			echo "<td title='{$total_ltime}'>{$total_stime}</td>";
			echo '</tr>';
			echo '</tfoot>';

		} else {

			echo '<tbody>';
			echo '<tr>';
			echo '<td colspan="3" style="text-align:center !important"><em>' . __( 'none', 'query-monitor' ) . '</em></td>';
			echo '</tr>';
			echo '</tbody>';

		}

		echo '</table>';
		echo '</div>';

	}

}

function register_qm_db_components( array $qm ) {
	$qm['db_components'] = new QM_Component_DB_Components;
	return $qm;
}

add_filter( 'query_monitor_components', 'register_qm_db_components', 35 );
