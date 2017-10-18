<?php
/*
Copyright 2009-2017 John Blackbourn

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

*/

class QM_Collector_Caps extends QM_Collector {

	public $id = 'caps';

	public function name() {
		return __( 'Capability Checks', 'query-monitor' );
	}

	public function __construct() {
		parent::__construct();
		add_filter( 'user_has_cap', array( $this, 'filter_user_has_cap' ), 9999, 4 );
	}

	public function filter_user_has_cap( array $user_caps, array $caps, array $args, WP_User $user ) {
		$current = current_action();
		$ignore  = array(
			'_admin_menu',
			'admin_menu',
			'admin_bar_init',
			'add_admin_bar_menus',
			'adminmenu',
			'admin_bar_menu',
			'wp_before_admin_bar_render',
			'wp_after_admin_bar_render',
		);

		if ( in_array( $current, $ignore, true ) ) {
			// return $user_caps;
		}

		$trace = new QM_Backtrace;

		$this->data['caps'][] = array(
			'name'  => $args[0],
			'trace' => $trace,
		);

		return $user_caps;
	}

	public function process() {
		$all_parts = array();
		$components = array();

		foreach ( $this->data['caps'] as $i => $cap ) {
			$parts = array_filter( preg_split( '#[_/-]#', $cap['name'] ) );
			$this->data['caps'][ $i ]['parts'] = $parts;
			$all_parts = array_merge( $all_parts, $parts );
			$component = $cap['trace']->get_component();
			$components[$component->name] = $component->name;
		}

		$this->data['parts'] = array_unique( array_filter( $all_parts ) );
		$this->data['components'] = $components;
	}

}

function register_qm_collector_caps( array $collectors, QueryMonitor $qm ) {
	$collectors['caps'] = new QM_Collector_Caps;
	return $collectors;
}

add_filter( 'qm/collectors', 'register_qm_collector_caps', 20, 2 );
