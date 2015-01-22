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

class QM_Collector_Hooks extends QM_Collector {

	public $id = 'hooks';

	public function name() {
		return __( 'Hooks', 'query-monitor' );
	}

	public function process() {

		global $wp_actions, $wp_filter;

		if ( is_admin() and ( $admin = QueryMonitor::get_collector( 'admin' ) ) ) {
			$this->data['screen'] = $admin->data['base'];
		} else {
			$this->data['screen'] = '';
		}

		$hooks = $all_parts = $components = array();

		$hide_qm = ( defined( 'QM_HIDE_SELF' ) and QM_HIDE_SELF );

		foreach ( $wp_actions as $name => $count ) {

			$actions = array();
			$action_components = array();

			if ( isset( $wp_filter[$name] ) ) {

				# http://core.trac.wordpress.org/ticket/17817
				$action = $wp_filter[$name];

				foreach ( $action as $priority => $callbacks ) {

					foreach ( $callbacks as $callback ) {

						$callback = QM_Util::populate_callback( $callback );

						if ( isset( $callback['component'] ) ) {
							if ( $hide_qm and ( 'query-monitor' === $callback['component']->context ) ) {
								continue;
							}

							$action_components[$callback['component']->name] = $callback['component']->name;
						}

						$actions[] = array(
							'priority'  => $priority,
							'callback'  => $callback,
						);

					}

				}

			}

			$action_parts = array_filter( preg_split( '#[_/-]#', $name ) );
			$all_parts    = array_merge( $all_parts, $action_parts );
			$components   = array_merge( $components, $action_components );

			$hooks[$name] = array(
				'name'    => $name,
				'actions' => $actions,
				'parts'   => $action_parts,
				'components' => $action_components,
			);

		}

		$this->data['hooks'] = $hooks;
		$this->data['parts'] = array_unique( array_filter( $all_parts ) );
		$this->data['components'] = array_unique( array_filter( $components ) );

	}

}

function register_qm_collector_hooks( array $qm ) {
	$qm['hooks'] = new QM_Collector_Hooks;
	return $qm;
}

add_filter( 'query_monitor_collectors', 'register_qm_collector_hooks', 80 );
