<?php

class QM_Overview extends QM {

	var $id = 'overview';

	function __construct() {
		parent::__construct();
		add_filter( 'query_monitor_title', array( $this, 'admin_title' ), 10 );
	}

	function admin_title( $title ) {
		$title[] = sprintf( __( '%s<small>S</small>', 'query_monitor' ), number_format_i18n( $this->data['load_time'], 2 ) );
		return $title;
	}

	function output( $args, $data ) {

		$http_time = 0;
		$http = $this->get_component( 'http' );

		# @TODO this should go into a process_*() function:

		if ( isset( $http->data['http'] ) ) {
			foreach ( $http->data['http'] as $row ) {
				if ( isset( $row['response'] ) )
					$http_time += ( $row['end'] - $row['start'] );
				else
					$http_time += $row['args']['timeout'];
			}
		}

		$total_stime = number_format_i18n( $data['load_time'], 4 );
		$total_ltime = number_format_i18n( $data['load_time'], 10 );
		$excl_stime  = number_format_i18n( $data['load_time'] - $http_time, 4 );
		$excl_ltime  = number_format_i18n( $data['load_time'] - $http_time, 10 );

		if ( empty( $http_time ) )
			$timespan = 1;
		else
			$timespan = 2;

		echo '<div class="qm" id="' . $this->id() . '">';
		echo '<table cellspacing="0">';
		echo '<tbody>';

		echo '<tr>';
		echo '<td>' . __( 'Peak memory usage', 'query_monitor' ) . '</td>';
		echo '<td title="' . esc_attr( sprintf( __( '%s bytes', 'query_monitor' ), number_format_i18n( $data['memory'] ) ) ) . '">' . sprintf( __( '%s kB', 'query_monitor' ), number_format_i18n( $data['memory'] / 1000 ) ) . '</td>';
		echo '</tr>';

		echo '<tr>';
		echo '<td rowspan=" ' . $timespan . '">' . __( 'Page generation time', 'query_monitor' ) . '</td>';
		echo "<td title='{$total_ltime}'>{$total_stime}</td>";
		echo '</tr>';

		if ( !empty( $http_time ) ) {
			echo '<tr>';
			echo "<td title='{$excl_ltime}'>" . sprintf( __( '%s w/o HTTP requests', 'query_monitor' ), $excl_stime ) . "</td>";
			echo '</tr>';
		}

		echo '</tbody>';
		echo '</table>';
		echo '</div>';

	}

	function process() {

		if ( function_exists( 'memory_get_peak_usage' ) )
			$this->data['memory'] = memory_get_peak_usage();
		else
			$this->data['memory'] = memory_get_usage();

		$this->data['load_time'] = $this->timer_stop_float();

	}

}

function register_qm_overview( $qm ) {
	$qm['overview'] = new QM_Overview;
	return $qm;
}

add_filter( 'query_monitor_components', 'register_qm_overview', 10 );

?>