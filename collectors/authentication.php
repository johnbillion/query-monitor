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

class QM_Collector_Authentication extends QM_Collector {

	public $id = 'authentication';

	public function name() {
		return __( 'Authentication', 'query-monitor' );
	}

	public function __construct() {
		parent::__construct();
		add_action( 'plugins_loaded', array( $this, 'action_plugins_loaded' ) );
	}

	public function action_plugins_loaded() {

		if ( !defined( 'QM_COOKIE' ) )
			define( 'QM_COOKIE', 'qm_' . COOKIEHASH );

	}

	public function show_query_monitor() {
		if ( isset( $_COOKIE[QM_COOKIE] ) )
			return $this->verify_nonce( $_COOKIE[QM_COOKIE], 'view_query_monitor' );
		return false;
	}

	public function create_nonce( $action ) {
		# This is just WordPress' nonce implementation minus the user ID
		# check so a nonce can be set in a cookie and used cross-user
		$i = wp_nonce_tick();
		return substr( wp_hash( $i . $action, 'nonce' ), -12, 10 );
	}

	public function verify_nonce( $nonce, $action ) {

		$i = wp_nonce_tick();

		if ( substr( wp_hash( $i . $action, 'nonce' ), -12, 10 ) === $nonce )
			return true;
		if ( substr( wp_hash( ( $i - 1 ) . $action, 'nonce' ), -12, 10 ) === $nonce )
			return true;

		return false;

	}

}

function register_qm_collector_authentication( array $qm ) {
	$qm['authentication'] = new QM_Collector_Authentication;
	return $qm;
}

add_filter( 'query_monitor_collectors', 'register_qm_collector_authentication', 130 );
