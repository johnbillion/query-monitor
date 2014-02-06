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

class QM_Output_Logger_DB_Queries extends QM_Output_Logger {

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
	}

	public function output() {

		$data = $this->collector->get_data();

		$this->log( sprintf( '===== %s =====', $this->collector->name() ) );

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

		$this->log( __( 'No database queries were logged because SAVEQUERIES is set to false', 'query-monitor' ) );

	}

	protected function output_error_queries( array $errors ) {

		$this->log( __( '----- Database Errors -----', 'query-monitor' ) );

		foreach ( $errors as $row )
			$this->output_query_row( $row, array( 'sql', 'caller', 'component', 'result' ) );

	}

	protected function output_expensive_queries( array $expensive ) {

		$this->log( sprintf( __( 'Slow Database Queries (above %ss)', 'query-monitor' ), number_format_i18n( QM_DB_EXPENSIVE, $dp ) ) );

		foreach ( $expensive as $row )
			$this->output_query_row( $row, array( 'sql', 'caller', 'component', 'result', 'time' ) );

	}

	protected function output_queries( $name, stdClass $db, array $data ) {

		if ( !empty( $db->rows ) ) {

			foreach ( $db->rows as $row )
				$this->output_query_row( $row, array( 'sql', 'caller', 'component', 'result', 'time' ) );

			$total_stime = number_format_i18n( $db->total_time, 4 );
			$this->log( sprintf( __( 'Total Queries: %s', 'query-monitor' ), number_format_i18n( $db->total_qs ) ) );
			$this->log( sprintf( __( 'Total Time: %s', 'query-monitor' ), $total_stime ) );

		} else {

			$this->log( __( 'None', 'query-monitor' ) );

		}

	}

	protected function output_query_row( array $row, array $cols ) {

		$cols = array_flip( $cols );

		if ( !isset( $row['component'] ) )
			unset( $cols['component'] );
		if ( !isset( $row['result'] ) )
			unset( $cols['result'] );
		if ( !isset( $row['stack'] ) )
			unset( $cols['stack'] );

		$stime = number_format_i18n( $row['ltime'], 4 );

		if ( is_wp_error( $row['result'] ) ) {
			$result = $row['result']->get_error_message();
		} else {
			$result = $row['result'];
		}

		if ( isset( $cols['sql'] ) )
			$this->log( sprintf( 'SQL: %s', $row['sql'] ) );

		if ( isset( $cols['caller'] ) )
			$this->log( sprintf( 'Caller: %s', $row['caller'] ) );

		if ( isset( $cols['component'] ) )
			$this->log( sprintf( 'Component: %s', $row['component']->name ) );

		if ( isset( $cols['result'] ) )
			$this->log( sprintf( 'Result: %s', $result ) );

		if ( isset( $cols['time'] ) )
			$this->log( sprintf( 'Time: %s', $stime ) );

		$this->log( '-----' );

	}

}

function register_qm_output_logger_db_queries( QM_Output $output = null, QM_Collector $collector ) {
	return new QM_Output_Logger_DB_Queries( $collector );
}

add_filter( 'query_monitor_output_logger_db_queries', 'register_qm_output_logger_db_queries', 10, 2 );
