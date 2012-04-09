<?php

if ( !defined( 'SAVEQUERIES' ) )
	define( 'SAVEQUERIES', true );
if ( !defined( 'QM_DB_EXPENSIVE' ) )
	define( 'QM_DB_EXPENSIVE', 0.05 );
if ( !defined( 'QM_DB_LIMIT' ) )
	define( 'QM_DB_LIMIT', 100 );

class QM_DB_Queries extends QM {

	var $id = 'db_queries';
	var $db_objects = array();

	function __construct() {
		parent::__construct();
		add_filter( 'query_monitor_menus', array( $this, 'admin_menu' ), 10 );
		add_filter( 'query_monitor_title', array( $this, 'admin_title' ), 20 );
		add_filter( 'query_monitor_class', array( $this, 'admin_class' ) );
	}

	function admin_title( $title ) {
		if ( isset( $this->data['dbs'] ) ) {
			foreach ( $this->data['dbs'] as $db )
				$title[] = sprintf( __( '%s<small>Q</small>', 'query-monitor' ), number_format_i18n( $db->total_qs ) );
		}
		return $title;
	}

	function admin_class( $class ) {

		if ( $this->get_errors() )
			$class[] = 'qm-error';
		if ( $this->get_expensive() )
			$class[] = 'qm-expensive';
		return $class;

	}

	function admin_menu( $menu ) {

		if ( $errors = $this->get_errors() ) {
			$menu[] = $this->menu( array(
				'id'    => 'query-monitor-errors',
				'href'  => '#qm-query-errors',
				'title' => sprintf( __( 'Database Errors (%s)', 'query-monitor' ), number_format_i18n( count( $errors ) ) )
			) );
		}
		if ( $expensive = $this->get_expensive() ) {
			$menu[] = $this->menu( array(
				'id'    => 'query-monitor-expensive',
				'href'  => '#qm-query-expensive',
				'title' => sprintf( __( 'Slow Queries (%s)', 'query-monitor' ), number_format_i18n( count( $expensive ) ) )
			) );
		}
		return $menu;

	}

	function get_errors() {
		if ( !empty( $this->data['errors'] ) )
			return $this->data['errors'];
		return false;
	}

	function get_expensive() {
		if ( !empty( $this->data['expensive'] ) )
			return $this->data['expensive'];
		return false;
	}

	function is_expensive( $row ) {
		return $row['ltime'] > QM_DB_EXPENSIVE;
	}

	function process() {

		if ( !SAVEQUERIES )
			return;

		$this->data['query_num']  = 0;
		$this->data['total_time'] = 0;
		$this->data['errors']     = array();

		$this->db_objects = apply_filters( 'query_monitor_db_objects', array(
			'$wpdb' => $GLOBALS['wpdb']
		) );

		foreach ( $this->db_objects as $name => $db ) {
			if ( $this->is_db_object( $db ) )
				$this->process_db_object( $name, $db );
		}

	}

	function output( $args, $data ) {

		if ( empty( $data['dbs'] ) )
			return;

		if ( !empty( $data['errors'] ) ) {

			echo '<div class="qm qm-queries" id="qm-query-errors">';
			echo '<table cellspacing="0">';
			echo '<thead>';
			echo '<tr>';
			echo '<th colspan="3">' . __( 'Database Query Errors', 'query-monitor' ) . '</th>';
			echo '</tr>';
			echo '<tr>';
			echo '<th>' . __( 'Query', 'query-monitor' ) . '</th>';
			echo '<th>' . __( 'Function', 'query-monitor' ) . '</th>';
			echo '<th>' . __( 'Error', 'query-monitor' ) . '</th>';
			echo '</tr>';
			echo '</thead>';
			echo '<tbody>';

			foreach ( $data['errors'] as $row ) {
				echo '<tr class="qm-warn">';
				echo '<td>' . $row['sql'] . '</td>';
				echo '<td title="' . esc_attr( $row['funcs'] ) . '">' . $row['func'] . '</td>';
				echo '<td>' . $row['result']->get_error_message( 'qmdb' ) . '</td>';
				echo '</tr>';
			}

			echo '</tbody>';
			echo '</table>';
			echo '</div>';

		}

		if ( !empty( $data['expensive'] ) ) {

			$dp = strlen( substr( strrchr( QM_DB_EXPENSIVE, '.' ), 1 ) );

			echo '<div class="qm qm-queries" id="qm-query-expensive">';
			echo '<table cellspacing="0">';
			echo '<thead>';
			echo '<tr>';
			echo '<th colspan="4">' . sprintf( __( 'Slow Database Queries (above %ss)', 'query-monitor' ), number_format_i18n( QM_DB_EXPENSIVE, $dp ) ) . '</th>';
			echo '</tr>';
			echo '<tr>';
			echo '<th>' . __( 'Query', 'query-monitor' ) . '</th>';
			echo '<th>' . __( 'Function', 'query-monitor' ) . '</th>';
			echo '<th>' . __( 'Affected Rows', 'query-monitor' ) . '</th>';
			echo '<th>' . __( 'Time', 'query-monitor' ) . '</th>';
			echo '</tr>';
			echo '</thead>';
			echo '<tbody>';

			foreach ( $data['expensive'] as $row ) {

				$stime = number_format_i18n( $row['ltime'], 4 );
				$ltime = number_format_i18n( $row['ltime'], 10 );

				if ( 'SELECT' != $row['type'] )
					$row['sql'] = "<span class='qm-nonselectsql'>{$row['sql']}</span>";

				echo '<tr>';
				echo '<td>' . $row['sql'] . '</td>';
				echo '<td title="' . esc_attr( $row['funcs'] ) . '">' . $row['func'] . '</td>';
				echo '<td>' . $row['result'] . '</td>';
				echo '<td class="qm-expensive" title="' . esc_attr( $ltime ) . '">' . $stime . '</td>';
				echo '</tr>';

			}

			echo '</tbody>';
			echo '</table>';
			echo '</div>';

		}

		foreach ( $data['dbs'] as $name => $db )
			$this->output_queries( $name, $db );

	}

	function is_db_object( $var ) {
		$objs = array(
			'wpdb',
			'dbrc_wpdb'
		);
		foreach ( $objs as $obj ) {
			if ( $var instanceof $obj )
				return true;
		}
		return false;
	}

	function add_func_time( $func, $ltime, $type ) {

		/*
		 * This should really be done in the db_functions component, but it's done here to save
		 * looping over the queries multiple times.
		 */

		if ( !isset( $this->data['times'][$func] ) ) {
			$this->data['times'][$func] = array(
				'func'  => $func,
				'calls' => 0,
				'ltime' => 0,
				'types' => array()
			);
		}

		$this->data['times'][$func]['calls']++;
		$this->data['times'][$func]['ltime'] += $ltime;

		if ( isset( $this->data['times'][$func]['types'][$type] ) )
			$this->data['times'][$func]['types'][$type]++;
		else
			$this->data['times'][$func]['types'][$type] = 1;

		if ( isset( $this->data['types'][$type] ) )
			$this->data['types'][$type]++;
		else
			$this->data['types'][$type] = 1;


	}

	function process_db_object( $id, $db ) {

		$rows        = array();
		$types       = array();
		$total_time  = 0;
		$total_qs    = 0;
		$has_results = false;

		foreach ( (array) $db->queries as $query ) {

			# @TODO: is this needed now?
			if ( false !== strpos( $query[2], 'wp_admin_bar' ) and !isset( $_REQUEST['qm_display_all'] ) )
				continue;

			$sql         = $query[0];
			$ltime       = $query[1];
			$funcs       = $query[2];
			$has_results = isset( $query[3] );

			if ( $has_results )
				$result = $query[3];
			else
				$result = null;

			$total_time += $ltime;
			$total_qs++;

			if ( false !== strpos( $funcs, '.php' ) )
				$func = sprintf( __( '<em>None</em> (%s)', 'query-monitor' ), $funcs );
			else
				$func = reset( array_reverse( explode( ', ', $funcs ) ) );

			$sql = $this->format_sql( $sql );
			$type = strtoupper( substr( $sql, 0, strpos( $sql, ' ' ) ) );

			$this->add_func_time( $func, $ltime, $type );

			if ( !isset( $types[$type]['total'] ) )
				$types[$type]['total'] = 1;
			else
				$types[$type]['total']++;

			if ( !isset( $types[$type]['funcs'][$func] ) )
				$types[$type]['funcs'][$func] = 1;
			else
				$types[$type]['funcs'][$func]++;

			$row = compact( 'func', 'funcs', 'sql', 'ltime', 'result', 'type' );

			if ( is_wp_error( $result ) )
				$this->data['errors'][] = $row;

			if ( $this->is_expensive( $row ) )
				$this->data['expensive'][] = $row;

			$rows[] = $row;

		}

		# @TODO standardise these var names:
		$this->data['query_num'] += $total_qs;
		$this->data['total_time'] += $total_time;

		# @TODO put errors in here too:
		$this->data['dbs'][$id] = (object) compact( 'rows', 'types', 'has_results', 'total_time', 'total_qs' );

	}

	function format_sql( $sql ) {

		$sql = str_replace( array( "\r\n", "\r", "\n", "\t" ), ' ', $sql );
		$sql = esc_html( trim( $sql ) );

		foreach( array(
			'AND', 'DELETE', 'ELSE', 'END', 'FROM', 'GROUP', 'HAVING', 'INNER', 'INSERT', 'LIMIT',
			'ON', 'OR', 'ORDER', 'SELECT', 'SET', 'THEN', 'UPDATE', 'VALUES', 'WHEN', 'WHERE'
		) as $cmd )
			$sql = trim( str_replace( " $cmd ", "<br/>$cmd ", $sql ) );

		return $sql;

	}

	function output_queries( $name, $db ) {

		# @TODO move more of this into process()

		$rows         = $db->rows;
		$has_results  = $db->has_results;
		$total_time   = $db->total_time;
		$total_qs     = $db->total_qs;
		$max_exceeded = $total_qs > QM_DB_LIMIT;

		$id = sanitize_title( $name );
		$span = 3;

		if ( $has_results )
			$span++;

		echo '<div class="qm qm-queries" id="qm-queries-' . $id . '">';
		echo '<table cellspacing="0">';
		echo '<thead>';
		echo '<tr>';
		echo '<th colspan="' . $span . '" class="qm-ltr">' . $name . '</th>';
		echo '</tr>';

		if ( $max_exceeded and !isset( $_REQUEST['qm_display_all'] ) ) {
			echo '<tr>';
			echo '<td colspan="' . $span . '" class="qm-expensive">' . sprintf( __( '%1$s %2$s queries were performed on this page load. Only the first %3$d are shown. Total times shown are for all queries.', 'query-monitor' ),
				number_format_i18n( $total_qs ),
				$name,
				number_format_i18n( QM_DB_LIMIT )
			) . '</td>';
			echo '</tr>';
		}

		echo '<tr>';
		echo '<th>' . __( 'Query', 'query-monitor' ) . '</th>';
		echo '<th>' . __( 'Function', 'query-monitor' ) . '</th>';

		if ( $has_results )
			echo '<th>' . __( 'Affected Rows', 'query-monitor' ) . '</th>';

		echo '<th>' . __( 'Time', 'query-monitor' ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		if ( isset( $_REQUEST['qm_sort'] ) and ( 'time' == $_REQUEST['qm_sort'] ) )
			usort( $rows, array( $this, '_sort' ) );

		if ( !empty( $rows ) ) {

			foreach ( $rows as $i => $row ) {

				if ( ( $i === QM_DB_LIMIT ) and !isset( $_REQUEST['qm_display_all'] ) )
					break;

				$row_class = '';
				$ql = strlen( $row['sql'] );
				$stime = number_format_i18n( $row['ltime'], 4 );
				$ltime = number_format_i18n( $row['ltime'], 10 );
				$td = $this->is_expensive( $row ) ? " class='qm-expensive'" : '';
				if ( 'SELECT' != $row['type'] )
					$row['sql'] = "<span class='qm-nonselectsql'>{$row['sql']}</span>";

				if ( $has_results ) {
					if ( is_wp_error( $row['result'] ) ) {
						$r = $row['result']->get_error_message( 'qmdb' );
						$results = "<td valign='top'>{$r}</td>\n";
						$row_class = 'qm-warn';
					} else {
						$results = "<td valign='top'>{$row['result']}</td>\n";
					}
				} else {
					$results = '';
				}

				$funcs = esc_attr( $row['funcs'] );

				echo "
					<tr class='{$row_class}'>\n
						<td valign='top' class='qm-ltr'>{$row['sql']}</td>\n
						<td valign='top' class='qm-ltr' title='{$funcs}'>{$row['func']}</td>\n
						{$results}
						<td valign='top' title='{$ltime}'{$td}>{$stime}</td>\n
					</tr>\n
				";
			}

			$total_stime = number_format_i18n( $total_time, 4 );
			$total_ltime = number_format_i18n( $total_time, 10 );

			echo '<tr>';
			echo '<td valign="top" colspan="' . ( $span - 1 ) . '">' . sprintf( _n( '%s query', '%s queries', $total_qs, 'query-monitor' ), number_format_i18n( $total_qs ) ) . '</td>';
			echo "<td valign='top' title='{$total_ltime}'>{$total_stime}</td>";
			echo '</tr>';

		} else {

			echo '<td colspan="' . $span . '" style="text-align:center !important"><em>' . __( 'none', 'query-monitor' ) . '</em></td>';

		}

		echo '</tbody>';
		echo '</table>';
		echo '</div>';

	}

}

function register_qm_db_queries( $qm ) {
	$qm['db_queries'] = new QM_DB_Queries;
	return $qm;
}

add_filter( 'query_monitor_components', 'register_qm_db_queries', 20 );

?>