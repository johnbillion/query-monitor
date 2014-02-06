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

class QM_Output_Logger_DB_Components extends QM_Output_Logger {

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
	}

	public function output() {

		$data = $this->collector->get_data();

		if ( empty( $data ) )
			return;

		$this->log( sprintf( '===== %s =====', $this->collector->name() ) );

		if ( !empty( $data['times'] ) ) {

			echo '<tbody>';

			usort( $data['times'], 'QM_Util::sort' );

			foreach ( $data['times'] as $component => $row ) {
				$stime = number_format_i18n( $row['ltime'], 4 );
				$ltime = number_format_i18n( $row['ltime'], 10 );

				$this->log( sprintf( 'Component: %s', $row['component'] ) );

				foreach ( $data['types'] as $type_name => $type_count ) {
					if ( isset( $row['types'][$type_name] ) )
						$this->log( sprintf( '%s: %d', $type_name, $row['types'][$type_name] ) );

				}

				$this->log( sprintf( 'Time: %s', $stime ) );
				$this->log( '-----' );

			}

		} else {

			$this->log( 'None' );

		}

	}

}

function register_qm_output_logger_db_components( QM_Output $output = null, QM_Collector $collector ) {
	return new QM_Output_Logger_DB_Components( $collector );
}

add_filter( 'query_monitor_output_logger_db_components', 'register_qm_output_logger_db_components', 10, 2 );
