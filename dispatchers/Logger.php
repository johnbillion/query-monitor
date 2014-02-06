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

class QM_Dispatcher_Logger extends QM_Dispatcher {

	public $id = 'logger';

	public function __construct( QM_Plugin $qm ) {

		parent::__construct( $qm );

	}

	public function active() {

		# @TODO change this to whatever condition you want, eg.
		# 
		# if ( i_am_simon() )
		#     return true;
		# else
		#     return false;

		return true;

	}

	public function before_output() {

		require_once $this->qm->plugin_path( 'output/Logger.php' );

		foreach ( glob( $this->qm->plugin_path( 'output/logger/*.php' ) ) as $output ) {
			include $output;
		}

	}

	public function get_outputter( QM_Collector $collector ) {
		return new QM_Output_Logger( $collector );
	}

}

function register_qm_dispatcher_logger( array $dispatchers, QM_Plugin $qm ) {
	$dispatchers['logger'] = new QM_Dispatcher_Logger( $qm );
	return $dispatchers;
}

add_filter( 'query_monitor_dispatchers', 'register_qm_dispatcher_logger', 10, 2 );
