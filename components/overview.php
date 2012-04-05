<?php

class QM_Overview extends QM {

	var $id = 'overview';

	function __construct() {
		parent::__construct();
		add_filter( 'query_monitor_title', array( $this, 'admin_title' ), 10 );
	}

	function admin_title( $title ) {
		$title[] = sprintf( __( '%s<small>S</small>', 'query-monitor' ), number_format_i18n( $this->data['load_time'], 2 ) );
		return $title;
	}

	function output( $args, $data ) {

		$http_time = null;
		$http = $this->get_component( 'http' );

		$db_query_num = null;
		$db_query_types = array();
		$db_queries = $this->get_component( 'db_queries' );

		if ( $http and isset( $http->data['http'] ) ) {
			foreach ( $http->data['http'] as $row ) {
				if ( isset( $row['response'] ) )
					$http_time += ( $row['end'] - $row['start'] );
				else
					$http_time += $row['args']['timeout'];
			}
		}

		if ( $db_queries and isset( $db_queries->data['query_num'] ) )
			$db_query_num = $db_queries->data['types'];

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
		echo '<td>' . __( 'Peak memory usage', 'query-monitor' ) . '</td>';
		echo '<td title="' . esc_attr( sprintf( __( '%s bytes', 'query-monitor' ), number_format_i18n( $data['memory'] ) ) ) . '">' . sprintf( __( '%s kB', 'query-monitor' ), number_format_i18n( $data['memory'] / 1000 ) ) . '</td>';
		echo '</tr>';

		echo '<tr>';
		echo '<td rowspan=" ' . $timespan . '">' . __( 'Page generation time', 'query-monitor' ) . '</td>';
		echo "<td title='{$total_ltime}'>{$total_stime}</td>";
		echo '</tr>';

		if ( isset( $http_time ) ) {
			echo '<tr>';
			echo "<td title='{$excl_ltime}'>" . sprintf( __( '%s w/o HTTP requests', 'query-monitor' ), $excl_stime ) . "</td>";
			echo '</tr>';
		}

		if ( isset( $db_query_num ) ) {
			echo '<tr>';
			echo '<td>' . __( 'Database queries', 'query-monitor' ) . '</td>';
			echo '<td>';

			foreach ( $db_query_num as $type_name => $type_count )
				$db_query_types[] = sprintf( '%1$s: %2$s', $type_name, number_format_i18n( $type_count ) );

			echo implode( '<br />', $db_query_types );

			echo '</td>';
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