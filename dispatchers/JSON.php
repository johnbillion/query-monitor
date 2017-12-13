<?php
/*
Copyright 2009-2016 John Blackbourn

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

*/

class QM_Dispatcher_JSON extends QM_Dispatcher_Html {

	public $id = 'json';

	public function action_admin_bar_menu( WP_Admin_Bar $wp_admin_bar ) {
		return;
	}

	public function init() {
		return;
	}

	public function dispatch() {
		if ( ! $this->should_dispatch() ) {
			return;
		}

		$collectors = QM_Collectors::init();
		$collectors->process();

		require_once $this->qm->plugin_path( 'output/JSON.php' );

		$out = array();

		foreach ( $collectors as $id => $collector ) {
			$outputter = new QM_Output_JSON( $collector );
			$out[ $id ] = $outputter->get_output();
		}

		echo '<script>var qm_json = ' . wp_json_encode( $out ) . ';</script>';
	}

}

function register_qm_dispatcher_json( array $dispatchers, QM_Plugin $qm ) {
	$dispatchers['json'] = new QM_Dispatcher_JSON( $qm );
	return $dispatchers;
}

add_filter( 'qm/dispatchers', 'register_qm_dispatcher_json', 5, 2 );
