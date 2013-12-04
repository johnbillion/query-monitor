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

class QM_Component_Overview extends QM_Component {

	var $id = 'overview';

	function name() {
		return __( 'Overview', 'query-monitor' );
	}

	function __construct() {
		parent::__construct();
		add_filter( 'query_monitor_title', array( $this, 'admin_title' ), 10 );
	}

	function admin_title( array $title ) {
		$title[] = sprintf(
			_x( '%s<small>S</small>', 'page load time', 'query-monitor' ),
			number_format_i18n( $this->data['time'], 2 )
		);
		$title[] = sprintf(
			_x( '%s<small>MB</small>', 'memory usage', 'query-monitor' ),
			number_format_i18n( ( $this->data['memory'] / 1024 / 1024 ), 2 )
		);
		return $title;
	}

	function process() {

		$this->data['time']       = QM_Util::timer_stop_float();
		$this->data['time_limit'] = ini_get( 'max_execution_time' );

		if ( !empty( $this->data['time_limit'] ) )
			$this->data['time_usage'] = ( 100 / $this->data['time_limit'] ) * $this->data['time'];
		else
			$this->data['time_usage'] = 0;

		if ( function_exists( 'memory_get_peak_usage' ) )
			$this->data['memory'] = memory_get_peak_usage();
		else
			$this->data['memory'] = memory_get_usage();

		$this->data['memory_limit'] = QM_Util::convert_hr_to_bytes( ini_get( 'memory_limit' ) );
		$this->data['memory_usage'] = ( 100 / $this->data['memory_limit'] ) * $this->data['memory'];

	}

}

function register_qm_overview( array $qm ) {
	$qm['overview'] = new QM_Component_Overview;
	return $qm;
}

add_filter( 'query_monitor_components', 'register_qm_overview', 10 );
