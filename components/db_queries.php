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

if ( !defined( 'SAVEQUERIES' ) )
	define( 'SAVEQUERIES', true );
if ( !defined( 'QM_DB_EXPENSIVE' ) )
	define( 'QM_DB_EXPENSIVE', 0.05 );

# QM_DB_LIMIT used to be a hard limit but proved to be more of an annoyance than anything. It now
# just adds a nag to the top of the query table. I might remove it altogether at some point.
if ( !defined( 'QM_DB_LIMIT' ) )
	define( 'QM_DB_LIMIT', 100 );

class QM_Component_DB_Queries extends QM_Component {

	public $id = 'db_queries';
	public $db_objects = array();

	function __construct() {
		parent::__construct();
		add_filter( 'query_monitor_menus', array( $this, 'admin_menu' ), 20 );
		add_filter( 'query_monitor_title', array( $this, 'admin_title' ), 20 );
		add_filter( 'query_monitor_class', array( $this, 'admin_class' ) );
	}

	function admin_title( array $title ) {
		if ( isset( $this->data['dbs'] ) ) {
			foreach ( $this->data['dbs'] as $db ) {
				$title[] = sprintf(
					_x( '%s<small>S</small>', 'database query time', 'query-monitor' ),
					number_format_i18n( $db->total_time, 4 )
				);
				$title[] = sprintf(
					_x( '%s<small>Q</small>', 'database query number', 'query-monitor' ),
					number_format_i18n( $db->total_qs )
				);
			}
		}
		return $title;
	}

	function admin_class( array $class ) {

		if ( $this->get_errors() )
			$class[] = 'qm-error';
		if ( $this->get_expensive() )
			$class[] = 'qm-expensive';
		return $class;

	}

	function admin_menu( array $menu ) {

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

		if ( count( $this->data['dbs'] ) > 1 ) {
			foreach ( $this->data['dbs'] as $name => $db ) {
				$menu[] = $this->menu( array(
					'title' => sprintf( __( 'Queries (%s)', 'query-monitor' ), esc_html( $name ) ),
					'href'  => sprintf( '#%s-%s', $this->id(), sanitize_title( $name ) ),
				) );
			}
		} else {
			$menu[] = $this->menu( array(
				'title' => __( 'Queries', 'query-monitor' ),
				'href'  => sprintf( '#%s-wpdb', $this->id() ),
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

	protected static function is_expensive( array $row ) {
		return $row['ltime'] > QM_DB_EXPENSIVE;
	}

	function process() {

		if ( !SAVEQUERIES )
			return;

		$this->data['total_qs']   = 0;
		$this->data['total_time'] = 0;
		$this->data['errors']     = array();

		$this->db_objects = apply_filters( 'query_monitor_db_objects', array(
			'$wpdb' => $GLOBALS['wpdb']
		) );

		foreach ( $this->db_objects as $name => $db ) {
			if ( is_a( $db, 'wpdb' ) )
				$this->process_db_object( $name, $db );
		}

	}

	function output_html( array $args, array $data ) {

		if ( empty( $data['dbs'] ) )
			return;

		if ( !empty( $data['errors'] ) ) {

			echo '<div class="qm qm-queries" id="qm-query-errors">';
			echo '<table cellspacing="0">';
			echo '<thead>';
			echo '<tr>';
			echo '<th colspan="4">' . __( 'Database Errors', 'query-monitor' ) . '</th>';
			echo '</tr>';
			echo '<tr>';
			echo '<th>' . __( 'Query', 'query-monitor' ) . '</th>';
			echo '<th>' . __( 'Call Stack', 'query-monitor' ) . '</th>';
			echo '<th>' . __( 'Component', 'query-monitor' ) . '</th>';
			echo '<th>' . __( 'Error', 'query-monitor' ) . '</th>';
			echo '</tr>';
			echo '</thead>';
			echo '<tbody>';

			foreach ( $data['errors'] as $row )
				$this->output_query_row( $row, array( 'sql', 'stack', 'component', 'result' ) );

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
			echo '<th colspan="5" class="qm-expensive">' . sprintf( __( 'Slow Database Queries (above %ss)', 'query-monitor' ), '<span class="qm-expensive">' . number_format_i18n( QM_DB_EXPENSIVE, $dp ) . '</span>' ) . '</th>';
			echo '</tr>';
			echo '<tr>';
			echo '<th scope="col">' . __( 'Query', 'query-monitor' ) . '</th>';
			echo '<th scope="col">' . __( 'Caller', 'query-monitor' ) . '</th>';

			if ( isset( $data['expensive'][0]['component'] ) )
				echo '<th scope="col">' . __( 'Component', 'query-monitor' ) . '</th>';

			if ( isset( $data['expensive'][0]['result'] ) )
				echo '<th scope="col">' . __( 'Affected Rows', 'query-monitor' ) . '</th>';

			echo '<th>' . __( 'Time', 'query-monitor' ) . '</th>';
			echo '</tr>';
			echo '</thead>';
			echo '<tbody>';

			foreach ( $data['expensive'] as $row )
				$this->output_query_row( $row, array( 'sql', 'caller', 'component', 'result', 'time' ) );

			echo '</tbody>';
			echo '</table>';
			echo '</div>';

		}

		foreach ( $data['dbs'] as $name => $db )
			$this->output_queries( $name, $db, $data );

	}

	function log_type( $type ) {

		if ( isset( $this->data['types'][$type] ) )
			$this->data['types'][$type]++;
		else
			$this->data['types'][$type] = 1;

	}

	function log_caller( $caller, $ltime, $type ) {

		if ( !isset( $this->data['times'][$caller] ) ) {
			$this->data['times'][$caller] = array(
				'caller' => $caller,
				'calls' => 0,
				'ltime' => 0,
				'types' => array()
			);
		}

		$this->data['times'][$caller]['calls']++;
		$this->data['times'][$caller]['ltime'] += $ltime;

		if ( isset( $this->data['times'][$caller]['types'][$type] ) )
			$this->data['times'][$caller]['types'][$type]++;
		else
			$this->data['times'][$caller]['types'][$type] = 1;

	}

	function log_component( $component, $ltime, $type ) {

		if ( !isset( $this->data['component_times'][$component->name] ) ) {
			$this->data['component_times'][$component->name] = array(
				'component' => $component->name,
				'calls'     => 0,
				'ltime'     => 0,
				'types'     => array()
			);
		}

		$this->data['component_times'][$component->name]['calls']++;
		$this->data['component_times'][$component->name]['ltime'] += $ltime;

		if ( isset( $this->data['component_times'][$component->name]['types'][$type] ) )
			$this->data['component_times'][$component->name]['types'][$type]++;
		else
			$this->data['component_times'][$component->name]['types'][$type] = 1;

	}

	protected static function query_compat( array & $query ) {

		list( $query['sql'], $query['ltime'], $query['stack'] ) = $query;

	}

	function process_db_object( $id, wpdb $db ) {

		$rows       = array();
		$types      = array();
		$total_time = 0;

		foreach ( (array) $db->queries as $query ) {

			if ( ! isset( $query['sql'] ) )
				self::query_compat( $query );

			if ( false !== strpos( $query['stack'], 'wp_admin_bar' ) and !isset( $_REQUEST['qm_display_admin_bar'] ) )
				continue;

			$sql           = $query['sql'];
			$ltime         = $query['ltime'];
			$stack         = $query['stack'];
			$has_component = isset( $query['trace'] );
			$has_results   = isset( $query['result'] );

			if ( $has_results )
				$result = $query['result'];
			else
				$result = null;

			$total_time += $ltime;

			if ( isset( $query['trace'] ) )
				$component = QM_Util::get_backtrace_component( $query['trace'] );
			else
				$component = null;

			# @TODO we should grab this from the trace instead for increased accuracy in case
			# the caller contains multiple comma separated arguments (see QM_Backtrace::$show_args)
			$callers = explode( ',', $stack );
			$caller  = trim( end( $callers ) );

			if ( false !== strpos( $caller, '(' ) )
				$caller_name = strstr( $caller, '(', true ) . '()';
			else
				$caller_name = $caller;

			# @TODO this formatting should move to JIT when outputting as html
			$sql  = QM_Util::format_sql( $sql );
			$type = preg_split( '/\b/', $sql );
			$type = strtoupper( $type[1] );

			$this->log_type( $type );
			$this->log_caller( $caller_name, $ltime, $type );

			if ( $component )
				$this->log_component( $component, $ltime, $type );

			if ( !isset( $types[$type]['total'] ) )
				$types[$type]['total'] = 1;
			else
				$types[$type]['total']++;

			if ( !isset( $types[$type]['callers'][$caller] ) )
				$types[$type]['callers'][$caller] = 1;
			else
				$types[$type]['callers'][$caller]++;

			$row = compact( 'caller', 'caller_name', 'stack', 'sql', 'ltime', 'result', 'type', 'component' );

			if ( is_wp_error( $result ) )
				$this->data['errors'][] = $row;

			if ( self::is_expensive( $row ) )
				$this->data['expensive'][] = $row;

			$rows[] = $row;

		}

		if ( isset( $_REQUEST['qm_sort'] ) and ( 'time' == $_REQUEST['qm_sort'] ) )
			usort( $rows, 'QM_Util::sort' );

		$total_qs = count( $rows );

		$this->data['total_qs'] += $total_qs;
		$this->data['total_time'] += $total_time;

		# @TODO put errors in here too:
		# @TODO proper class instead of (object)
		$this->data['dbs'][$id] = (object) compact( 'rows', 'types', 'has_results', 'has_component', 'total_time', 'total_qs' );

	}

	function output_queries( $name, stdClass $db, array $data ) {

		$max_exceeded = $db->total_qs > QM_DB_LIMIT;

		$span = 3;

		if ( $db->has_results )
			$span++;
		if ( $db->has_component )
			$span++;

		echo '<div class="qm qm-queries" id="' . $this->id() . '-' . sanitize_title( $name ) . '">';
		echo '<table cellspacing="0">';
		echo '<thead>';
		echo '<tr>';
		echo '<th colspan="' . $span . '" class="qm-ltr">' . $name . '</th>';
		echo '</tr>';

		if ( $max_exceeded ) {
			echo '<tr>';
			echo '<td colspan="' . $span . '" class="qm-expensive">' . sprintf( __( '%1$s %2$s queries were performed on this page load. Crikey!', 'query-monitor' ),
				number_format_i18n( $db->total_qs ),
				$name,
				number_format_i18n( QM_DB_LIMIT )
			) . '</td>';
			echo '</tr>';
		}

		echo '<tr>';
		echo '<th scope="col">' . __( 'Query', 'query-monitor' ) . $this->build_filter( 'type', array_keys( $db->types ) ) . '</th>';
		echo '<th scope="col">' . __( 'Caller', 'query-monitor' ) . $this->build_filter( 'caller', array_keys( $data['times'] ) ) . '</th>';

		if ( $db->has_component )
			echo '<th scope="col">' . __( 'Component', 'query-monitor' ) . $this->build_filter( 'component', array_keys( $data['component_times'] ) ) . '</th>';

		if ( $db->has_results )
			echo '<th scope="col">' . __( 'Affected Rows', 'query-monitor' ) . '</th>';

		echo '<th scope="col">' . __( 'Time', 'query-monitor' ) . '</th>';
		echo '</tr>';
		echo '</thead>';

		if ( !empty( $db->rows ) ) {

			echo '<tbody>';

			foreach ( $db->rows as $i => $row )
				$this->output_query_row( $row, array( 'sql', 'caller', 'component', 'result', 'time' ) );

			echo '</tbody>';
			echo '<tfoot>';

			$total_stime = number_format_i18n( $db->total_time, 4 );
			$total_ltime = number_format_i18n( $db->total_time, 10 );

			echo '<tr class="qm-items-shown qm-hide">';
			echo '<td valign="top" colspan="' . ( $span - 1 ) . '">' . sprintf( __( 'Queries in filter: %s', 'query-monitor' ), '<span class="qm-items-number">' . number_format_i18n( $db->total_qs ) . '</span>' ) . '</td>';
			echo "<td valign='top' class='qm-items-time'>{$total_stime}</td>";
			echo '</tr>';

			echo '<tr>';
			echo '<td valign="top" colspan="' . ( $span - 1 ) . '">' . sprintf( __( 'Total Queries: %s', 'query-monitor' ), number_format_i18n( $db->total_qs ) ) . '</td>';
			echo "<td valign='top' title='{$total_ltime}'>{$total_stime}</td>";
			echo '</tr>';
			echo '</tfoot>';

		} else {

			echo '<tbody>';
			echo '<tr>';
			echo '<td colspan="' . $span . '" style="text-align:center !important"><em>' . __( 'none', 'query-monitor' ) . '</em></td>';
			echo '</tr>';
			echo '</tbody>';

		}

		echo '</table>';
		echo '</div>';

	}

	function output_query_row( array $row, array $cols ) {

		$cols = array_flip( $cols );

		if ( is_null( $row['component'] ) )
			unset( $cols['component'] );
		if ( is_null( $row['result'] ) )
			unset( $cols['result'] );
		if ( is_null( $row['stack'] ) )
			unset( $cols['stack'] );

		$row_attr = array();
		$stime = number_format_i18n( $row['ltime'], 4 );
		$ltime = number_format_i18n( $row['ltime'], 10 );
		$td = self::is_expensive( $row ) ? ' qm-expensive' : '';

		if ( 'SELECT' != $row['type'] )
			$row['sql'] = "<span class='qm-nonselectsql'>{$row['sql']}</span>";

		if ( is_wp_error( $row['result'] ) ) {
			$error  = $row['result']->get_error_message();
			$result = "<td valign='top' class='qm-row-result qm-row-error'>{$error}</td>\n";
			$row_attr['class'] = 'qm-warn';
		} else {
			$result = "<td valign='top' class='qm-row-result'>{$row['result']}</td>\n";
		}

		if ( isset( $cols['sql'] ) )
			$row_attr['data-qm-db_queries-type'] = $row['type'];
		if ( isset( $cols['component'] ) )
			$row_attr['data-qm-db_queries-component'] = $row['component']->name;
		if ( isset( $cols['caller'] ) )
			$row_attr['data-qm-db_queries-caller'] = $row['caller_name'];
		if ( isset( $cols['time'] ) ) {
			$row_attr['data-qm-db_queries-time'] = $row['ltime'];
			$row_attr['data-qm-time'] = $row['ltime'];
		}

		$stack = esc_attr( $row['stack'] );
		$attr = '';

		foreach ( $row_attr as $a => $v )
			$attr .= ' ' . $a . '="' . esc_attr( $v ) . '"';

		echo "<tr{$attr}>";

		if ( isset( $cols['sql'] ) )
			echo "<td valign='top' class='qm-row-sql qm-ltr qm-sql'>{$row['sql']}</td>";

		if ( isset( $cols['caller'] ) )
			echo "<td valign='top' class='qm-row-caller qm-ltr' title='{$stack}'>{$row['caller']}</td>";

		if ( isset( $cols['stack'] ) ) {
			# This isn't optimal...
			$stack = explode( ', ', $row['stack'] );
			$stack = array_reverse( $stack );
			$stack = implode( '<br>', $stack );
			echo "<td valign='top' class='qm-row-caller qm-row-stack qm-ltr'>{$stack}</td>";
		}

		if ( isset( $cols['component'] ) )
			echo "<td valign='top' class='qm-row-component'>{$row['component']->name}</td>\n";

		if ( isset( $cols['result'] ) )
			echo $result;

		if ( isset( $cols['time'] ) )
			echo "<td valign='top' title='{$ltime}' class='qm-row-time{$td}'>{$stime}</td>\n";

		echo '</tr>';

	}

}

function register_qm_db_queries( array $qm ) {
	$qm['db_queries'] = new QM_Component_DB_Queries;
	return $qm;
}

add_filter( 'query_monitor_components', 'register_qm_db_queries', 20 );
