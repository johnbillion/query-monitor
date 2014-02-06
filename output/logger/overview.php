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

class QM_Output_Logger_Overview extends QM_Output_Logger {

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
	}

	public function output() {

		$data = $this->collector->get_data();

		$this->log( sprintf( '===== %s =====', $this->collector->name() ) );

		$db_query_num   = null;
		$db_query_types = array();
		# @TODO: make this less derpy:
		$db_queries     = QueryMonitor::get_collector( 'db_queries' );

		if ( $db_queries ) {
			$db_queries_data = $db_queries->get_data();
			if ( isset( $db_queries_data['types'] ) ) {
				$db_query_num = $db_queries_data['types'];
				$db_stime = number_format_i18n( $db_queries_data['total_time'], 4 );
			}
		}

		$total_stime = number_format_i18n( $data['time'], 4 );

		$memory_usage = sprintf( __( '%1$s%% of %2$s kB limit', 'query-monitor' ), number_format_i18n( $data['memory_usage'], 1 ), number_format_i18n( $data['memory_limit'] / 1024 ) );

		$time_usage = sprintf( __( '%1$s%% of %2$ss limit', 'query-monitor' ), number_format_i18n( $data['time_usage'], 1 ), number_format_i18n( $data['time_limit'] ) );

		$this->log( sprintf( 'Page generation time: %s %s',
			$total_stime,
			$time_usage
		) );
		$this->log( sprintf( 'Peak memory usage: %s %s',
			sprintf( __( '%s kB', 'query-monitor' ), number_format_i18n( $data['memory'] / 1024 ) ),
			$memory_usage
		) );

		if ( isset( $db_query_num ) ) {

			$this->log( sprintf( 'Database query time: %s',
				$db_stime
			) );

			foreach ( $db_query_num as $type_name => $type_count ) {
				$this->log( sprintf( '%1$s: %2$s',
					$type_name,
					number_format_i18n( $type_count )
				) );
			}

		}

	}

}

function register_qm_output_logger_overview( QM_Output $output = null, QM_Collector $collector ) {
	return new QM_Output_Logger_Overview( $collector );
}

add_filter( 'query_monitor_output_logger_overview', 'register_qm_output_logger_overview', 10, 2 );
