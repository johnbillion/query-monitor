<?php
/*
Copyright 2009-2015 John Blackbourn

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
		add_action( 'plugins_loaded',      array( $this, 'action_plugins_loaded' ) );
		add_action( 'wp_ajax_qm_auth_on',  array( $this, 'ajax_on' ) );
		add_action( 'wp_ajax_qm_auth_off', array( $this, 'ajax_off' ) );
	}

	public function action_plugins_loaded() {

		if ( !defined( 'QM_COOKIE' ) ) {
			define( 'QM_COOKIE', 'query_monitor_' . COOKIEHASH );
		}

	}

	/**
	 * Helper function. Should the authentication cookie be secure?
	 *
	 * @return bool Should the authentication cookie be secure?
	 */
	public static function secure_cookie() {
		return ( is_ssl() and ( 'https' === parse_url( home_url(), PHP_URL_SCHEME ) ) );
	}

	public function ajax_on() {

		if ( ! current_user_can( 'view_query_monitor' ) or ! check_ajax_referer( 'qm-auth-on', 'nonce', false ) ) {
			wp_send_json_error( __( 'Could not set authentication cookie.', 'query-monitor' ) );
		}

		$expiration = time() + 172800; # 48 hours
		$secure     = self::secure_cookie();
		$cookie     = wp_generate_auth_cookie( get_current_user_id(), $expiration, 'logged_in' );

		setcookie( QM_COOKIE, $cookie, $expiration, COOKIEPATH, COOKIE_DOMAIN, $secure, false );

		$text = __( 'Authentication cookie set. You can now view Query Monitor output while logged out or while logged in as a different user.', 'query-monitor' );

		wp_send_json_success( $text );

	}

	public function ajax_off() {

		if ( ! $this->user_verified() or ! check_ajax_referer( 'qm-auth-off', 'nonce', false ) ) {
			wp_send_json_error( __( 'Could not clear authentication cookie.', 'query-monitor' ) );
		}

		$expiration = time() - 31536000;

		setcookie( QM_COOKIE, ' ', $expiration, COOKIEPATH, COOKIE_DOMAIN );

		$text = __( 'Authentication cookie cleared.', 'query-monitor' );

		wp_send_json_success( $text );

	}

	public function user_verified() {
		if ( isset( $_COOKIE[QM_COOKIE] ) ) {
			return $this->verify_cookie( stripslashes( $_COOKIE[QM_COOKIE] ) );
		}
		return false;
	}

	public static function verify_cookie( $value ) {
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
