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
			define( 'QM_COOKIE', 'query_monitor_' . COOKIEHASH );

	}

	public function user_verified() {
		if ( isset( $_COOKIE[QM_COOKIE] ) )
			return $this->verify_cookie( stripslashes( $_COOKIE[QM_COOKIE] ) );
		return false;
	}

	public function get_cookie_attributes() {

		return array(
			'name'   => QM_COOKIE,
			'path'   => COOKIEPATH,
			'domain' => COOKIE_DOMAIN,
		);

	}

	public function get_cookie_content() {

		$expires = time() + 172800; # 48 hours
		$value   = wp_generate_auth_cookie( get_current_user_id(), $expires, 'logged_in' );
		$secure  = apply_filters( 'secure_logged_in_cookie', false, get_current_user_id(), is_ssl() );

		return compact( 'expires', 'value', 'secure' );

	}

	public function verify_cookie( $value ) {

		if ( $old_user_id = wp_validate_auth_cookie( $value, 'logged_in' ) ) {
			return user_can( $old_user_id, 'view_query_monitor' );
		}

		return false;

	}

}

function register_qm_collector_authentication( array $qm ) {
	$qm['authentication'] = new QM_Collector_Authentication;
	return $qm;
}

add_filter( 'query_monitor_collectors', 'register_qm_collector_authentication', 130 );
