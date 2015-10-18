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

class QM_Dispatcher_Redirect extends QM_Dispatcher {

	public $id = 'redirect';

	public function __construct( QM_Plugin $qm ) {
		parent::__construct( $qm );

		add_filter( 'wp_redirect', array( $this, 'filter_wp_redirect' ), 999, 2 );

	}

	/**
	 *
	 * @param string $location The path to redirect to.
	 * @param int    $status   Status code to use.
	 */
	public function filter_wp_redirect( $location, $status ) {

		if ( ! $this->should_dispatch() ) {
			return $location;
		}

		$this->before_output();

		/* @var QM_Output_Headers[] */
		foreach ( $this->get_outputters( 'headers' ) as $id => $output ) {
			$output->output();
		}

		$this->after_output();

		return $location;

	}

	protected function before_output() {

		require_once $this->qm->plugin_path( 'output/Headers.php' );

		foreach ( glob( $this->qm->plugin_path( 'output/headers/*.php' ) ) as $file ) {
			require_once $file;
		}
	}

	public function is_active() {

		if ( ! $this->user_can_view() ) {
			return false;
		}

		# If the headers have already been sent then we can't do anything about it
		if ( headers_sent() ) {
			return false;
		}

		# Don't process if the minimum required actions haven't fired:
		if ( is_admin() ) {
			if ( ! did_action( 'admin_init' ) ) {
				return false;
			}
		} else {
			if ( ! ( did_action( 'wp' ) || did_action( 'login_init' ) ) ) {
				return false;
			}
		}

		return true;

	}

}

function register_qm_dispatcher_redirect( array $dispatchers, QM_Plugin $qm ) {
	$dispatchers['redirect'] = new QM_Dispatcher_Redirect( $qm );
	return $dispatchers;
}

add_filter( 'qm/dispatchers', 'register_qm_dispatcher_redirect', 10, 2 );
