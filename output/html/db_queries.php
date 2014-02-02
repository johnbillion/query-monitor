<?php
/*

Copyright 2014 John Blackbourn

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

*/

class QM_Output_Html_DB_Queries extends QM_Output_Html {

	public $query_row = 0;

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'query_monitor_menus', array( $this, 'admin_menu' ), 20 );
		add_filter( 'query_monitor_title', array( $this, 'admin_title' ), 20 );
		add_filter( 'query_monitor_class', array( $this, 'admin_class' ) );
	}

	public function output() {

		$data = $this->collector->get_data();

		if ( empty( $data['dbs'] ) ) {
			$this->output_empty_queries();
			return;
		}

		if ( !empty( $data['errors'] ) ) {
			$this->output_error_queries( $data['errors'] );
		}

		if ( !empty( $data['expensive'] ) ) {
			$this->output_expensive_queries( $data['expensive'] );
		}

		foreach ( $data['dbs'] as $name => $db ) {
			$this->output_queries( $name, $db, $data );
		}

	}

	protected function output_empty_queries() {

		echo '<div class="qm qm-queries" id="' . $this->collector->id() . '-wpdb">';
		echo '<table cellspacing="0">';
		echo '<thead>';
		echo '<tr>';
		echo '<th>' . __( 'Database Queries', 'query-monitor' ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';
		echo '<tr>';
		echo '<td class="qm-warn">';
		_e( 'No database queries were logged because <code>SAVEQUERIES</code> is set to <code>false</code>', 'query-monitor' );
		echo '</td>';
		echo '</tr>';
		echo '</tbody>';
		echo '</table>';
		echo '</div>';

	}

	protected function output_error_queries( array $errors ) {

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

		foreach ( $errors as $row )
			$this->output_query_row( $row, array( 'sql', 'stack', 'component', 'result' ) );

		echo '</tbody>';
		echo '</table>';
		echo '</div>';

	}

	protected function output_expensive_queries( array $expensive ) {

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

		if ( isset( $expensive[0]['component'] ) )
			echo '<th scope="col">' . __( 'Component', 'query-monitor' ) . '</th>';

		if ( isset( $expensive[0]['result'] ) )
			echo '<th scope="col">' . __( 'Affected Rows', 'query-monitor' ) . '</th>';

		echo '<th class="qm-num">' . __( 'Time', 'query-monitor' ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		foreach ( $expensive as $row )
			$this->output_query_row( $row, array( 'sql', 'caller', 'component', 'result', 'time' ) );

		echo '</tbody>';
		echo '</table>';
		echo '</div>';

	}

	protected function output_queries( $name, stdClass $db, array $data ) {

		$max_exceeded = $db->total_qs > QM_DB_LIMIT;

		$span = 4;

		if ( $db->has_results )
			$span++;
		if ( $db->has_component )
			$span++;

		echo '<div class="qm qm-queries" id="' . $this->collector->id() . '-' . sanitize_title( $name ) . '">';
		echo '<table cellspacing="0" class="qm-sortable">';
		echo '<thead>';
		echo '<tr>';
		echo '<th colspan="' . $span . '">' . sprintf( __( '%s Queries', 'query-monitor' ), $name ) . '</th>';
		echo '</tr>';

		if ( ! $db->has_component ) {
			echo '<tr>';
			echo '<td colspan="' . $span . '" class="qm-warn">' . sprintf( __( 'Extended query information such as the component and affected rows is not available. Query Monitor was unable to symlink its <code>db.php</code> file into place. <a href="%s" target="_blank">See this wiki page for more information.</a>', 'query-monitor' ),
				'https://github.com/johnbillion/query-monitor/wiki/db.php-Symlink'
			) . '</td>';
			echo '</tr>';
		}

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
		echo '<th scope="col" class="qm-sorted-asc">&nbsp;' . $this->build_sorter() . '</th>';
		echo '<th scope="col">' . __( 'Query', 'query-monitor' ) . $this->build_filter( 'type', array_keys( $db->types ) ) . '</th>';
		echo '<th scope="col">' . __( 'Caller', 'query-monitor' ) . $this->build_filter( 'caller', array_keys( $data['times'] ) ) . '</th>';

		if ( $db->has_component )
			echo '<th scope="col">' . __( 'Component', 'query-monitor' ) . $this->build_filter( 'component', array_keys( $data['component_times'] ) ) . '</th>';

		if ( $db->has_results )
			echo '<th scope="col">' . __( 'Rows', 'query-monitor' ) . $this->build_sorter() . '</th>';

		echo '<th scope="col" class="qm-num">' . __( 'Time', 'query-monitor' ) . $this->build_sorter() . '</th>';
		echo '</tr>';
		echo '</thead>';

		if ( !empty( $db->rows ) ) {

			echo '<tbody>';

			foreach ( $db->rows as $row )
				$this->output_query_row( $row, array( 'sql', 'caller', 'component', 'result', 'time' ) );

			echo '</tbody>';
			echo '<tfoot>';

			$total_stime = number_format_i18n( $db->total_time, 4 );

			echo '<tr class="qm-items-shown qm-hide">';
			echo '<td valign="top" colspan="' . ( $span - 1 ) . '">' . sprintf( __( 'Queries in filter: %s', 'query-monitor' ), '<span class="qm-items-number">' . number_format_i18n( $db->total_qs ) . '</span>' ) . '</td>';
			echo "<td valign='top' class='qm-items-time'>{$total_stime}</td>";
			echo '</tr>';

			echo '<tr>';
			echo '<td valign="top" colspan="' . ( $span - 1 ) . '">' . sprintf( __( 'Total Queries: %s', 'query-monitor' ), number_format_i18n( $db->total_qs ) ) . '</td>';
			echo "<td valign='top'>{$total_stime}</td>";
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

	protected function output_query_row( array $row, array $cols ) {

		$cols = array_flip( $cols );

		if ( !isset( $row['component'] ) )
			unset( $cols['component'] );
		if ( !isset( $row['result'] ) )
			unset( $cols['result'] );
		if ( !isset( $row['stack'] ) )
			unset( $cols['stack'] );

		$row_attr = array();
		$stime = number_format_i18n( $row['ltime'], 4 );
		$td = $this->collector->is_expensive( $row ) ? ' qm-expensive' : '';

		$sql = self::format_sql( $row['sql'] );

		if ( 'SELECT' != $row['type'] )
			$sql = "<span class='qm-nonselectsql'>{$sql}</span>";

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

		$attr = '';

		foreach ( $row_attr as $a => $v )
			$attr .= ' ' . $a . '="' . esc_attr( $v ) . '"';

		echo "<tr{$attr}>";

		echo "<td valign='top' class='qm-'>" . ++$this->query_row . "</td>";

		if ( isset( $cols['sql'] ) )
			echo "<td valign='top' class='qm-row-sql qm-ltr qm-sql'>{$sql}</td>";

		if ( isset( $cols['caller'] ) ) {
			echo "<td valign='top' class='qm-row-caller qm-ltr qm-has-toggle'>";

			$caller_name = $row['caller'];

			if ( isset( $row['trace'] ) ) {
				$caller      = $row['trace']->get_caller();
				$caller_name = self::output_filename( $row['caller'], $caller['calling_file'], $caller['calling_line'] );
			}

			echo $caller_name;

			# This isn't optimal...
			# @TODO convert this to use our new filtered trace array when present
			$stack = explode( ', ', $row['stack'] );
			$stack = array_reverse( $stack );
			array_shift( $stack );
			$stack = implode( '<br>', $stack );

			if ( !empty( $stack ) ) {
				echo '<a href="#" class="qm-toggle" data-on="+" data-off="-">+</a>';
				echo '<div class="qm-toggled">' . $stack . '</div>';
			}

			echo "</td>";
		}

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
			echo "<td valign='top' class='qm-row-time{$td}'>{$stime}</td>\n";

		echo '</tr>';

	}

	public function admin_title( array $title ) {

		$data = $this->collector->get_data();

		if ( isset( $data['dbs'] ) ) {
			foreach ( $data['dbs'] as $db ) {
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

	public function admin_class( array $class ) {

		if ( $this->collector->get_errors() )
			$class[] = 'qm-error';
		if ( $this->collector->get_expensive() )
			$class[] = 'qm-expensive';
		return $class;

	}

	public function admin_menu( array $menu ) {

		$data = $this->collector->get_data();

		if ( $errors = $this->collector->get_errors() ) {
			$menu[] = $this->menu( array(
				'id'    => 'query-monitor-errors',
				'href'  => '#qm-query-errors',
				'title' => sprintf( __( 'Database Errors (%s)', 'query-monitor' ), number_format_i18n( count( $errors ) ) )
			) );
		}
		if ( $expensive = $this->collector->get_expensive() ) {
			$menu[] = $this->menu( array(
				'id'    => 'query-monitor-expensive',
				'href'  => '#qm-query-expensive',
				'title' => sprintf( __( 'Slow Queries (%s)', 'query-monitor' ), number_format_i18n( count( $expensive ) ) )
			) );
		}

		if ( isset( $data['dbs'] ) and count( $data['dbs'] ) > 1 ) {
			foreach ( $data['dbs'] as $name => $db ) {
				$menu[] = $this->menu( array(
					'title' => sprintf( __( 'Queries (%s)', 'query-monitor' ), esc_html( $name ) ),
					'href'  => sprintf( '#%s-%s', $this->collector->id(), sanitize_title( $name ) ),
				) );
			}
		} else {
			$menu[] = $this->menu( array(
				'title' => __( 'Queries', 'query-monitor' ),
				'href'  => sprintf( '#%s-wpdb', $this->collector->id() ),
			) );
		}

		return $menu;

	}

}

function register_qm_output_html_db_queries( QM_Output $output = null, QM_Collector $collector ) {
	return new QM_Output_Html_DB_Queries( $collector );
}

add_filter( 'query_monitor_output_html_db_queries', 'register_qm_output_html_db_queries', 10, 2 );
