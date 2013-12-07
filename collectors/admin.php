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

class QM_Collector_Admin extends QM_Collector {

	var $id = 'admin';

	function name() {
		return __( 'Admin', 'query-monitor' );
	}

	function __construct() {
		parent::__construct();
		add_filter( 'current_screen',      array( $this, 'current_screen' ), 99 );
	}

	function current_screen( WP_Screen $screen ) {
		if ( empty( $this->data['admin'] ) )
			$this->data['admin'] = wp_clone( $screen );
		return $screen;
	}

	function process() {

		global $pagenow;

		if ( isset( $_GET['page'] ) )
			$this->data['base'] = get_current_screen()->base;
		else
			$this->data['base'] = $pagenow;

		if ( !isset( $this->data['admin'] ) )
			$this->data['admin'] = __( 'n/a', 'query-monitor' );

		$this->data['pagenow'] = $pagenow;
		$this->data['current_screen'] = get_current_screen();

	}

}

function register_qm_admin( array $qm ) {
	if ( is_admin() )
		$qm['admin'] = new QM_Collector_Admin;
	return $qm;
}

add_filter( 'query_monitor_collectors', 'register_qm_admin', 50 );
