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

class QM_Output_Logger_Conditionals extends QM_Output_Logger {

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
	}

	public function output() {

		$data = $this->collector->get_data();

		$this->log( sprintf( '===== %s =====', $this->collector->name() ) );

		foreach ( $data['conds']['true'] as $cond ) {
			$this->log( sprintf( '%s: true', $cond ) );
		}

	}

}

function register_qm_output_logger_conditionals( QM_Output $output = null, QM_Collector $collector ) {
	return new QM_Output_Logger_Conditionals( $collector );
}

add_filter( 'query_monitor_output_logger_conditionals', 'register_qm_output_logger_conditionals', 10, 2 );
