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

if ( ! class_exists( 'QM_Dispatcher' ) ) {
abstract class QM_Dispatcher {

	public function __construct( QM_Plugin $qm ) {
		$this->qm = $qm;

		if ( !defined( 'QM_COOKIE' ) ) {
			define( 'QM_COOKIE', 'query_monitor_' . COOKIEHASH );
		}

		add_action( 'init', array( $this, 'init' ) );

	}

	abstract public function is_active();

	public function should_process() {

		if ( ! $this->qm->should_process() ) {
			return;
		}

		# Don't process if the minimum required actions haven't fired:

		if ( is_admin() ) {

			if ( ! did_action( 'admin_init' ) ) {
				return false;
			}

		} else {

			if ( ! ( did_action( 'wp' ) or did_action( 'login_init' ) ) ) {
				return false;
			}

		}

		# If this dispatcher is active, we need to process:
		return $this->is_active();

	}

	final public function dispatch() {

		if ( ! $this->should_process() ) {
			return;
		}

		$collectors = QM_Collectors::init();
		$collectors->process();

		$this->outputters = apply_filters( "qm/outputter/{$this->id}", array(), $collectors );
		$this->before_output();

		foreach ( $this->outputters as $outputter ) {
			$outputter->output();
		}

		$this->after_output();

	}

	public function init() {
		// nothing
	}

	public function before_output() {
		// nothing
	}

	public function after_output() {
		// nothing
	}

	public function user_can_view() {

		if ( !did_action( 'plugins_loaded' ) ) {
			return false;
		}

		if ( current_user_can( 'view_query_monitor' ) ) {
			return true;
		}

		return $this->user_verified();

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
}
