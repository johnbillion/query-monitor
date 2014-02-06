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

class QM_Output_Logger_HTTP extends QM_Output_Logger {

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
	}

	public function output() {

		$data = $this->collector->get_data();

		if ( empty( $data ) or empty( $data['http'] ) )
			return;

		$this->log( sprintf( '===== %s =====', $this->collector->name() ) );

		foreach ( $data['http'] as $key => $row ) {

			$ltime = $row['ltime'];

			if ( empty( $ltime ) )
				$stime = '';
			else
				$stime = number_format_i18n( $ltime, 4 );

			if ( is_wp_error( $row['response'] ) ) {
				$response = $row['response']->get_error_message();
			} else {
				$response = wp_remote_retrieve_response_code( $row['response'] );
				$msg      = wp_remote_retrieve_response_message( $row['response'] );

				if ( empty( $response ) )
					$response = __( 'n/a', 'query-monitor' );
				else
					$response = $response . ' ' . $msg;

			}

			$method = $row['args']['method'];
			if ( !$row['args']['blocking'] )
				$method .= ' ' . _x( '(non-blocking)', 'non-blocking HTTP transport', 'query-monitor' );

			if ( isset( $row['transport'] ) )
				$transport = $row['transport'];
			else
				$transport = '';

			$component = $row['trace']->get_component();

			$this->log( '---------------------' );
			$this->log( sprintf( 'Request: %s %s',
				$method,
				$row['url']
			) );
			$this->log( sprintf( 'Response: %s %s',
				$response
			) );
			$this->log( sprintf( 'Transport: %s %s',
				$transport
			) );
			$this->log( sprintf( 'Component: %s %s',
				$component->name
			) );
			$this->log( sprintf( 'Timeout: %s %s',
				$row['args']['timeout']
			) );
			$this->log( sprintf( 'Time: %s %s',
				$stime
			) );

		}

	}

}

function register_qm_output_logger_http( QM_Output $output = null, QM_Collector $collector ) {
	return new QM_Output_Logger_HTTP( $collector );
}

add_filter( 'query_monitor_output_logger_http', 'register_qm_output_logger_http', 10, 2 );
