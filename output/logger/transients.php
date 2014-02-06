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

class QM_Output_Logger_Transients extends QM_Output_Logger {

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
	}

	public function output() {

		$data = $this->collector->get_data();

		if ( empty( $data['trans'] ) )
			return;

		$this->log( sprintf( '===== %s =====', $this->collector->name() ) );

		foreach ( $data['trans'] as $row ) {
			$transient = str_replace( array(
				'_site_transient_',
				'_transient_'
			), '', $row['transient'] );

			if ( 0 === $row['expiration'] )
				$row['expiration'] = __( 'none', 'query-monitor' );
			$expiration = ( isset( $row['expiration'] ) ) ? $row['expiration'] : '';

			$this->log( sprintf( '%1$s (type: %2$s, expiration: %3$s, component: %4$s)',
				$transient,
				$row['type'],
				$expiration,
				$row['trace']->get_component()
			) );

		}

	}

}

function register_qm_output_logger_transients( QM_Output $output = null, QM_Collector $collector ) {
	return new QM_Output_Logger_Transients( $collector );
}

add_filter( 'query_monitor_output_logger_transients', 'register_qm_output_logger_transients', 10, 2 );
