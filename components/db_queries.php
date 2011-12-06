<?php

if ( !defined( 'SAVEQUERIES' ) )
	define( 'SAVEQUERIES', true );
if ( !defined( 'QM_DB_EXPENSIVE' ) )
	define( 'QM_DB_EXPENSIVE', 0.05 );
if ( !defined( 'QM_DB_LONG' ) )
	define( 'QM_DB_LONG', 1000 );
if ( !defined( 'QM_DB_LIMIT' ) )
	define( 'QM_DB_LIMIT', 100 );

# @TODO warnings and shit for long/slow queries

class QM_DB_Queries extends QM {

	var $id = 'db_queries';

	function __construct() {
		parent::__construct();
		add_filter( 'query_monitor_menus', array( $this, 'admin_menu' ), 10 );
		add_filter( 'query_monitor_title', array( $this, 'admin_title' ), 20 );
		add_filter( 'query_monitor_class', array( $this, 'admin_class' ) );
	}

	function admin_title( $title ) {
		$title[] = sprintf( __( '%s<small>Q</small>', 'query_monitor' ), number_format_i18n( $this->data['query_num'] ) );
		return $title;
	}

	function admin_class( $class ) {

		if ( $this->get_errors() )
			$class[] = 'qm-error';
		return $class;

	}

	function admin_menu( $menu ) {

		if ( $errors = $this->get_errors() ) {
			$menu[] = $this->menu( array(
				'id'    => 'query_monitor_errors',
				'title' => sprintf( __( 'Database Errors (%s)', 'query_monitor' ), number_format_i18n( count( $errors ) ) )
			) );
		}
		return $menu;

	}

	function get_errors() {
		if ( !empty( $this->data['errors'] ) )
			return $this->data['errors'];
		return false;
	}

	function process() {

		if ( !SAVEQUERIES )
			return;

		$this->data['query_num']  = 0;
		$this->data['errors']     = array();
		$this->data['db_objects'] = apply_filters( 'query_monitor_db_objects', array(
			'$wpdb' => $GLOBALS['wpdb']
		) );

		foreach ( $this->data['db_objects'] as $name => $db ) {
			if ( $this->is_db_object( $db ) )
				$this->process_db_object( $name, $db );
		}

	}

	function output( $args, $data ) {

		if ( !SAVEQUERIES )
			return;

		foreach ( $data['db_objects'] as $name => $db ) {
			if ( isset( $this->data['dbs'][$name] ) )
				$this->output_queries( $name, $this->data['dbs'][$name] );
		}

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

	function add_func_time( $func, $ltime ) {

		/*
		 * This should really be done in the db_functions component, but it's done here to save
		 * looping over the queries multiple times.
		 */

		if ( !isset( $this->data['times'][$func] ) ) {
			$this->data['times'][$func] = array(
				'func'  => $func,
				'calls' => 0,
				'ltime' => 0
			);
		}

		$this->data['times'][$func]['calls']++;
		$this->data['times'][$func]['ltime'] += $ltime;

	}

	function process_db_object( $id, $db ) {

		$rows         = array();
		$total_time   = 0;
		$total_qs     = 0;
		$ignored_time = 0;
		$has_results  = false;

		foreach ( (array) $db->queries as $query ) {

			/* ********************************************************* */
			#if ( false !== strpos( $query[2], 'wp_admin_bar' ) )
			#	continue;
			/* ********************************************************* */

			$sql         = $query[0];
			$ltime       = $query[1];
			$funcs       = $query[2];
			$has_results = isset( $query[3] );

			if ( $has_results )
				$result = $query[3];
			else
				$result = null;

			if ( strpos( $funcs, 'wp_admin_bar' ) ) {
				$ignored_time += $ltime;
			} else {
				$total_time += $ltime;
				$total_qs++;
			}

			if ( !empty( $funcs ) )
				$func  = reset( array_reverse( explode( ', ', $funcs ) ) );
			else
				$func = '<em class="qm-info">' . __( 'none', 'query_monitor' ) . '</em>';

			if ( strpos( $funcs, 'wp_admin_bar' ) ) {
				if ( isset( $_REQUEST['qm_display_all'] ) )
					$this->add_func_time( $func, $ltime );
			} else {
				$this->add_func_time( $func, $ltime );
			}

			$sql = str_replace( array( "\r\n", "\r", "\n", "\t" ), ' ', $sql );
			$sql = esc_html( trim( $sql ) );

			foreach( array(
				'AND', 'DELETE', 'ELSE', 'END', 'FROM', 'GROUP', 'HAVING', 'INNER', 'INSERT', 'LIMIT',
				'ON', 'OR', 'ORDER', 'SELECT', 'SET', 'THEN', 'UPDATE', 'VALUES', 'WHEN', 'WHERE'
			) as $cmd )
				$sql = trim( str_replace( " $cmd ", "<br/>$cmd ", $sql ) );

			$rows[] = array(
				'func'   => $func,
				'funcs'  => $funcs,
				'sql'    => $sql,
				'ltime'  => $ltime,
				'result' => $result
			);

			if ( is_wp_error( $result ) )
				$this->data['errors'][] = $result;

		}

		# @TODO standardise these var names:
		$this->data['query_num'] += $total_qs;

		# @TODO put errors in here too:
		$this->data['dbs'][$id] = (object) array(
			'rows'        => $rows,
			'has_results' => $has_results,
			'total_time'  => $total_time,
			'total_qs'    => $total_qs,
		);

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

		echo '<table class="qm qm-queries" cellspacing="0" id="qm-queries-' . $id . '">';
		echo '<thead>';
		echo '<tr>';
		echo '<th colspan="' . $span . '" class="qm-ltr">' . $name . '</th>';
		echo '</tr>';

		if ( $max_exceeded ) {
			echo '<tr><td colspan="' . $span . '" class="qm-expensive">' . sprintf( __( '%1$s %2$s queries were performed on this page load. Only the first %3$d are shown below. Total query time and cumulative function times should be accurate.', 'query_monitor' ), number_format_i18n( $total_qs ), $name, number_format_i18n( QM_DB_LIMIT ) ) . '</td></tr>';
		}

		echo '<tr>';
		echo '<th>' . __( 'Query', 'query_monitor' ) . '</th>';
		echo '<th>' . __( 'Function', 'query_monitor' ) . '</th>';

		if ( $has_results )
			echo '<th>' . __( 'Affected Rows', 'query_monitor' ) . '</th>';

		echo '<th>' . __( 'Time', 'query_monitor' ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		if ( isset( $_REQUEST['qm_sort'] ) and ( 'time' == $_REQUEST['qm_sort'] ) )
			usort( $rows, array( $this, '_sort' ) );

		if ( !empty( $rows ) ) {

			foreach ( $rows as $row ) {
				if ( strpos( $row['funcs'], 'wp_admin_bar' ) ) {
					if ( !isset( $_REQUEST['qm_display_all'] ) )
						continue;
					$row_class = 'qm-na';
				} else {
					$row_class = '';
				}
				$select = ( 0 === strpos( strtoupper( $row['sql'] ), 'SELECT' ) );
				$ql = strlen( $row['sql'] );
				$qs = size_format( $ql );
				$stime = number_format_i18n( $row['ltime'], 4 );
				$ltime = number_format_i18n( $row['ltime'], 10 );
				if ( $select and ( $ql > QM_DB_LONG ) )
					$row['qs'] = "<br /><span class='qm-expensive'>({$qs})</span>";
				else
					$row['qs'] = '';
				$td = ( $row['ltime'] > QM_DB_EXPENSIVE ) ? " class='qm-expensive'" : '';
				if ( !$select )
					$row['sql'] = "<span class='qm-nonselectsql'>{$row['sql']}</span>";

				if ( $has_results ) {
					if ( is_wp_error( $row['result'] ) ) {
						$r = $row['result']->get_error_message( 'qmdb_error' );
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
						<td valign='top' class='qm-ltr'>{$row['sql']}{$row['qs']}</td>\n
						<td valign='top' class='qm-ltr' title='{$funcs}'>{$row['func']}</td>\n
						{$results}
						<td valign='top' title='{$ltime}'{$td}>{$stime}</td>\n
					</tr>\n
				";
			}

			$total_stime = number_format_i18n( $total_time, 4 );
			$total_ltime = number_format_i18n( $total_time, 10 );

			echo '<tr>';
			echo '<td valign="top" colspan="' . ( $span - 1 ) . '">' . sprintf( _n( '%s query', '%s queries', $total_qs ), number_format_i18n( $total_qs ) ) . '</td>';
			echo "<td valign='top' title='{$total_ltime}'>{$total_stime}</td>";
			echo '</tr>';

		} else {

			echo '<td colspan="' . $span . '" style="text-align:center !important"><em>' . __( 'none', 'query_monitor' ) . '</em></td>';

		}

		echo '</tbody>';
		echo '</table>';

	}

}

function register_qm_db_queries( $qm ) {
	$qm['db_queries'] = new QM_DB_Queries;
	return $qm;
}

add_filter( 'query_monitor_components', 'register_qm_db_queries', 20 );

?>