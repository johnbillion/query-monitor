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

class QM_Collector_Redirects extends QM_Collector {

	public $id = 'redirects';

	public function name() {
		return __( 'Redirects', 'query-monitor' );
	}

	public function __construct() {
		parent::__construct();
		add_filter( 'wp_redirect', array( $this, 'filter_wp_redirect' ), 999, 2 );
	}

	public function filter_wp_redirect( $location, $status ) {

		if ( !$location )
			return $location;
		if ( !QueryMonitor::init()->show_query_monitor() )
			return $location;
		if ( headers_sent() )
			return $location;

		$trace = new QM_Backtrace;

		header( sprintf( 'X-QM-Redirect-Trace: %s',
			implode( ', ', $trace->get_stack() )
		) );

		return $location;

	}

}

function register_qm_collector_redirects( array $qm ) {
	$qm['redirects'] = new QM_Collector_Redirects;
	return $qm;
}

add_filter( 'query_monitor_collectors', 'register_qm_collector_redirects', 140 );
