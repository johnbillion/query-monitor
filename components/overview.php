<?php

class QM_Overview extends QM {

	var $id = 'overview';

	function __construct() {
		parent::__construct();
		add_filter( 'query_monitor_title', array( $this, 'admin_title' ), 10 );
	}

	function admin_title( $title ) {
		$title[] = sprintf( __( '%s<small>S</small>', 'query-monitor' ), number_format_i18n( $this->data['time'], 2 ) );
		$title[] = sprintf( __( '%s<small>MB</small>', 'query-monitor' ), number_format_i18n( ( $this->data['memory'] / 1024 / 1024 ), 1 ) );
		return $title;
	}

	function output( $args, $data ) {

		$http_time      = null;
		$db_query_num   = null;
		$db_query_types = array();
		$http           = $this->get_component( 'http' );
		$db_queries     = $this->get_component( 'db_queries' );
		$time_usage     = '';
		$memory_usage   = '';

		if ( $http and isset( $http->data['http'] ) ) {
			foreach ( $http->data['http'] as $row ) {
				if ( isset( $row['response'] ) )
					$http_time += ( $row['end'] - $row['start'] );
				else
					$http_time += $row['args']['timeout'];
			}
		}

		if ( $db_queries and isset( $db_queries->data['types'] ) ) {
			$db_query_num = $db_queries->data['types'];
			$db_stime = number_format_i18n( $db_queries->data['total_time'], 4 );
			$db_ltime = number_format_i18n( $db_queries->data['total_time'], 10 );
		}

		$total_stime = number_format_i18n( $data['time'], 4 );
		$total_ltime = number_format_i18n( $data['time'], 10 );
		$excl_stime  = number_format_i18n( $data['time'] - $http_time, 4 );
		$excl_ltime  = number_format_i18n( $data['time'] - $http_time, 10 );

		echo '<div class="qm" id="' . $this->id() . '">';
		echo '<table cellspacing="0">';
		echo '<tbody>';

		$memory_usage .= '<br /><span class="qm-info">' . sprintf( __( '%1$s%% of %2$s kB', 'query-monitor' ), number_format_i18n( $data['memory_usage'], 1 ), number_format_i18n( $data['memory_limit'] / 1024 ) ) . '</span>';

		echo '<tr>';
		echo '<th>' . __( 'Peak memory usage', 'query-monitor' ) . '</th>';
		echo '<td title="' . esc_attr( sprintf( __( '%s bytes', 'query-monitor' ), number_format_i18n( $data['memory'] ) ) ) . '">' . sprintf( __( '%s kB', 'query-monitor' ), number_format_i18n( $data['memory'] / 1024 ) ) . $memory_usage . '</td>';
		echo '</tr>';

		if ( isset( $http_time ) )
			$time_usage .= '<br /><span class="qm-info">' . sprintf( __( '%s without HTTP requests', 'query-monitor' ), $excl_stime ) . '</span>';

		if ( $data['time_usage'] > 25 ) /* Only bother with generation time if it's above 25%: */
			$time_usage .= '<br /><span class="qm-info">' . sprintf( __( '%1$s%% of %2$ss limit', 'query-monitor' ), number_format_i18n( $data['time_usage'], 1 ), number_format_i18n( $data['time_limit'] ) ) . '</span>';

		echo '<tr>';
		echo '<th>' . __( 'Page generation time', 'query-monitor' ) . '</th>';
		echo "<td title='{$total_ltime}'>{$total_stime}{$time_usage}</td>";
		echo '</tr>';

		if ( isset( $db_query_num ) ) {
			echo '<tr>';
			echo '<th>' . __( 'Database query time', 'query-monitor' ) . '</th>';
			echo "<td title='{$db_ltime}'>{$db_stime}</td>";
			echo '</tr>';
			echo '<tr>';
			echo '<th>' . __( 'Database queries', 'query-monitor' ) . '</th>';
			echo '<td>';

			# @TODO i18n
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

		$this->data['time']       = $this->timer_stop_float();
		$this->data['time_limit'] = ini_get( 'max_execution_time' );
		$this->data['time_usage'] = ( 100 / $this->data['time_limit'] ) * $this->data['time'];

		if ( function_exists( 'memory_get_peak_usage' ) )
			$this->data['memory'] = memory_get_peak_usage();
		else
			$this->data['memory'] = memory_get_usage();

		$this->data['memory_limit'] = $this->convert_hr_to_bytes( ini_get( 'memory_limit' ) );
		$this->data['memory_usage'] = ( 100 / $this->data['memory_limit'] ) * $this->data['memory'];

	}

}

function register_qm_overview( $qm ) {
	$qm['overview'] = new QM_Overview;
	return $qm;
}

add_filter( 'query_monitor_components', 'register_qm_overview', 10 );

?>