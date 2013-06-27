<?php

class QM_Component_Overview extends QM_Component {

	var $id = 'overview';

	function __construct() {
		parent::__construct();
		add_filter( 'query_monitor_title', array( $this, 'admin_title' ), 10 );
	}

	function admin_title( array $title ) {
		$title[] = sprintf(
			_x( '%s<small>S</small>', 'page load time', 'query-monitor' ),
			number_format_i18n( $this->data['time'], 2 )
		);
		$title[] = sprintf(
			_x( '%s<small>MB</small>', 'memory usage', 'query-monitor' ),
			number_format_i18n( ( $this->data['memory'] / 1024 / 1024 ), 2 )
		);
		return $title;
	}

	function output_html( array $args, array $data ) {

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

		$memory_usage .= '<br /><span class="qm-info">' . sprintf( __( '%1$s%% of %2$s kB limit', 'query-monitor' ), number_format_i18n( $data['memory_usage'], 1 ), number_format_i18n( $data['memory_limit'] / 1024 ) ) . '</span>';

		$time_usage .= '<br /><span class="qm-info">' . sprintf( __( '%1$s%% of %2$ss limit', 'query-monitor' ), number_format_i18n( $data['time_usage'], 1 ), number_format_i18n( $data['time_limit'] ) ) . '</span>';

		if ( isset( $http_time ) )
			$time_usage .= '<br /><span class="qm-info" title="' . esc_attr( $excl_ltime ) . '">' . sprintf( __( '%s excluding HTTP requests', 'query-monitor' ), $excl_stime ) . '</span>';

		echo '<tr>';
		echo '<th>' . __( 'Page generation time', 'query-monitor' ) . '</th>';
		echo "<td><span title='{$total_ltime}'>{$total_stime}</span>{$time_usage}</td>";
		echo '</tr>';

		echo '<tr>';
		echo '<th>' . __( 'Peak memory usage', 'query-monitor' ) . '</th>';
		echo '<td><span title="' . esc_attr( sprintf( __( '%s bytes', 'query-monitor' ), number_format_i18n( $data['memory'] ) ) ) . '">' . sprintf( __( '%s kB', 'query-monitor' ), number_format_i18n( $data['memory'] / 1024 ) ) . '</span>' . $memory_usage . '</td>';
		echo '</tr>';

		if ( isset( $db_query_num ) ) {
			echo '<tr>';
			echo '<th>' . __( 'Database query time', 'query-monitor' ) . '</th>';
			echo "<td title='{$db_ltime}'>{$db_stime}</td>";
			echo '</tr>';
			echo '<tr>';
			echo '<th>' . __( 'Database queries', 'query-monitor' ) . '</th>';
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

		$this->data['time']       = QM_Util::timer_stop_float();
		$this->data['time_limit'] = ini_get( 'max_execution_time' );

		if ( !empty( $this->data['time_limit'] ) )
			$this->data['time_usage'] = ( 100 / $this->data['time_limit'] ) * $this->data['time'];
		else
			$this->data['time_usage'] = 0;

		if ( function_exists( 'memory_get_peak_usage' ) )
			$this->data['memory'] = memory_get_peak_usage();
		else
			$this->data['memory'] = memory_get_usage();

		$this->data['memory_limit'] = QM_Util::convert_hr_to_bytes( ini_get( 'memory_limit' ) );
		$this->data['memory_usage'] = ( 100 / $this->data['memory_limit'] ) * $this->data['memory'];

	}

}

function register_qm_overview( array $qm ) {
	$qm['overview'] = new QM_Component_Overview;
	return $qm;
}

add_filter( 'query_monitor_components', 'register_qm_overview', 10 );

?>
