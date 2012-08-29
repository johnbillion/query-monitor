<?php

class QM_DB_Callers extends QM {

	var $id = 'db_callers';

	function __construct() {
		parent::__construct();
		add_filter( 'query_monitor_menus', array( $this, 'admin_menu' ), 30 );
	}

	function process() {

		if ( $dbq = $this->get_component( 'db_queries' ) and isset( $dbq->data['times'] ) ) {
			$this->data['times'] = $dbq->data['times'];
			$this->data['types'] = $dbq->data['types'];
		}

	}

	function admin_menu( $menu ) {

		if ( $dbq = $this->get_component( 'db_queries' ) and isset( $dbq->data['times'] ) ) {
			$menu[] = $this->menu( array(
				'title' => __( 'Query Callers', 'query-monitor' )
			) );
		}
		return $menu;

	}

	function output( $args, $data ) {

		if ( empty( $data ) )
			return;

		$total_time  = 0;
		$total_calls = 0;

		echo '<div class="qm" id="' . $args['id'] . '">';
		echo '<table cellspacing="0">';
		echo '<thead>';
		echo '<tr>';
		echo '<th>' . _x( 'Caller', 'Query caller', 'query-monitor' ) . '</th>';

		if ( !empty( $data['types'] ) ) {
			foreach ( $data['types'] as $type_name => $type_count )
				echo '<th>' . $type_name . '</th>';
		}

		echo '<th>' . __( 'Time', 'query-monitor' ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		if ( !empty( $data['times'] ) ) {

			usort( $data['times'], array( $this, '_sort' ) );

			foreach ( $data['times'] as $func => $row ) {
				$total_time  += $row['ltime'];
				$total_calls += $row['calls'];
				$stime = number_format_i18n( $row['ltime'], 4 );
				$ltime = number_format_i18n( $row['ltime'], 10 );

				echo '<tr>';
				echo "<td valign='top' class='qm-ltr'>{$row['func']}</td>";

				foreach ( $data['types'] as $type_name => $type_count ) {
					if ( isset( $row['types'][$type_name] ) )
						echo "<td valign='top'>{$row['types'][$type_name]}</td>";
					else
						echo "<td valign='top'>&nbsp;</td>";
				}

				echo "<td valign='top' title='{$ltime}'>{$stime}</td>";
				echo '</tr>';

			}

			$total_stime = number_format_i18n( $total_time, 4 );
			$total_ltime = number_format_i18n( $total_time, 10 );

			echo '<tr>';
			echo '<td>&nbsp;</td>';

			foreach ( $data['types'] as $type_name => $type_count )
				echo '<td>' . number_format_i18n( $type_count ) . '</td>';

			echo "<td title='{$total_ltime}'>{$total_stime}</td>";
			echo '</tr>';

		} else {

			echo '<td colspan="3" style="text-align:center !important"><em>' . __( 'none', 'query-monitor' ) . '</em></td>';

		}

		echo '</tbody>';
		echo '</table>';
		echo '</div>';

	}

}

function register_qm_db_callers( $qm ) {
	$qm['db_callers'] = new QM_DB_Callers;
	return $qm;
}

add_filter( 'query_monitor_components', 'register_qm_db_callers', 30 );

?>