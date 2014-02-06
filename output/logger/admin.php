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

class QM_Output_Logger_Admin extends QM_Output_Logger {

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
	}

	public function output() {

		$data = $this->collector->get_data();

		if ( empty( $data ) )
			return;

		$this->log( sprintf( '===== %s =====', $this->collector->name() ) );

		$this->log( sprintf( 'pagenow: %s', $data['pagenow'] ) );

		foreach ( $data['current_screen'] as $key => $value ) {
			$this->log( sprintf( '%1$s: %2$s', $key, $value ) );
		}

	}

}

function register_qm_output_logger_admin( QM_Output $output = null, QM_Collector $collector ) {
	return new QM_Output_Logger_Admin( $collector );
}

add_filter( 'query_monitor_output_logger_admin', 'register_qm_output_logger_admin', 10, 2 );
