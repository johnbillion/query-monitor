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

class QM_Collector_Hooks extends QM_Collector {

	public $id = 'hooks';

	public function name() {
		return __( 'Hooks', 'query-monitor' );
	}

	public function __construct() {
		parent::__construct();
	}

	public function process() {

		global $wp_actions, $wp_filter;

		if ( is_admin() and ( $admin = QueryMonitor::get_collector( 'admin' ) ) )
			$this->data['screen'] = $admin->data['base'];
		else
			$this->data['screen'] = '';

		$hooks = $parts = $components = array();

		foreach ( $wp_actions as $name => $count ) {

			$actions = array();
			# @TODO better variable name:
			$c = array();

			if ( isset( $wp_filter[$name] ) ) {

				# http://core.trac.wordpress.org/ticket/17817
				$action = $wp_filter[$name];

				foreach ( $action as $priority => $callbacks ) {

					foreach ( $callbacks as $callback ) {

						$callback = QM_Util::populate_callback( $callback );

						if ( isset( $callback['component'] ) )
							$c[$callback['component']->name] = $callback['component']->name;

						$actions[] = array(
							'priority'  => $priority,
							'callback'  => $callback,
						);

					}

				}

			}

			# @TODO better variable name:
			$p = array_filter( preg_split( '/[_\/-]/', $name ) );
			$parts = array_merge( $parts, $p );
			$components = array_merge( $components, $c );

			$hooks[$name] = array(
				'name'    => $name,
				'actions' => $actions,
				'parts'   => $p,
				'components' => $c,
			);

		}

		$this->data['hooks'] = $hooks;
		$this->data['parts'] = array_unique( array_filter( $parts ) );
		$this->data['components'] = array_unique( array_filter( $components ) );

	}

}

function register_qm_collector_hooks( array $qm ) {
	$qm['hooks'] = new QM_Collector_Hooks;
	return $qm;
}

add_filter( 'query_monitor_collectors', 'register_qm_collector_hooks', 80 );
