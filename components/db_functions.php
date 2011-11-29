<?php

class QM_DB_Functions extends QM {

	var $id = 'db_functions';
	var $times = array();

	function __construct() {

		parent::__construct();

	}

	function output() {

		$total_time  = 0;
		$total_calls = 0;
		$dbq = $this->get_component( 'db_queries' );
		$this->times = $dbq->times;

		echo '<table class="qm" cellspacing="0" id="' . $this->id() . '">';
		echo '<thead>';
		echo '<tr>';
		echo '<th>' . __( 'Query Function', 'query_monitor' ) . '</th>';
		echo '<th>' . __( 'Queries', 'query_monitor' ) . '</th>';
		echo '<th>' . __( 'Time', 'query_monitor' ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		if ( !empty( $this->times ) ) {

			usort( $this->times, array( $this, '_sort' ) );

			foreach ( $this->times as $func => $row ) {
				$total_time  += $row['ltime'];
				$total_calls += $row['calls'];
				$stime = number_format_i18n( $row['ltime'], 4 );
				$ltime = number_format_i18n( $row['ltime'], 10 );
				echo "
					<tr>\n
						<td valign='top' class='qm-ltr'>{$row['func']}</td>\n
						<td valign='top'>{$row['calls']}</td>\n
						<td valign='top' title='{$ltime}'>{$stime}</td>\n
					</tr>\n
				";
			}

			$total_stime = number_format_i18n( $total_time, 4 );
			$total_ltime = number_format_i18n( $total_time, 10 );

			echo '<tr>';
			echo '<td>&nbsp;</td>';
			echo "<td>{$total_calls}</td>";
			echo "<td title='{$total_ltime}'>{$total_stime}</td>";
			echo '</tr>';

		} else {

			echo '<td colspan="3" style="text-align:center !important"><em>' . __( 'none', 'query_monitor' ) . '</em></td>';

		}

		echo '</tbody>';
		echo '</table>';

	}

	function admin_menu() {

		global $template;

		return $this->menu( array(
			'title' => __( 'Functions', 'query_monitor' )
		) );

	}

}

function register_qm_db_functions( $qm ) {
	$qm['db_functions'] = new QM_DB_Functions;
	return $qm;
}

add_filter( 'qm', 'register_qm_db_functions' );

?>