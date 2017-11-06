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
		add_filter( 'user_has_cap', array( $this, 'filter_user_has_cap' ), 9999, 3 );
		add_filter( 'map_meta_cap', array( $this, 'filter_map_meta_cap' ), 9999, 4 );
	}

	/**
	 * Logs user capability checks.
	 *
	 * This does not get called for Super Admins. See filter_map_meta_cap() below.
	 *
	 * @param bool[]   $user_caps     Concerned user's capabilities.
	 * @param string[] $required_caps Required primitive capabilities for the requested capability.
	 * @param array    $args {
	 *     Arguments that accompany the requested capability check.
	 *
	 *     @type string    $0 Requested capability.
	 *     @type int       $1 Concerned user ID.
	 *     @type mixed  ...$2 Optional second and further parameters.
	 * }
	 * @return bool[] Concerned user's capabilities.
	 */
	public function filter_user_has_cap( array $user_caps, array $caps, array $args ) {
		$trace  = new QM_Backtrace;
		$result = true;

		foreach ( $caps as $cap ) {
			if ( empty( $user_caps[ $cap ] ) ) {
				$result = false;
				break;
			}
		}

		$this->data['caps'][] = array(
			'args'   => $args,
			'trace'  => $trace,
			'result' => $result,
		);

		return $user_caps;
	}

	/**
	 * Logs user capability checks for Super Admins on Multisite.
	 *
	 * This is needed because the `user_has_cap` filter doesn't fire for Super Admins.
	 *
	 * @param string[] $required_caps Required primitive capabilities for the requested capability.
	 * @param string   $cap           Capability or meta capability being checked.
	 * @param int      $user_id       Concerned user ID.
	 * @param array    $args {
	 *     Arguments that accompany the requested capability check.
	 *
	 *     @type mixed ...$0 Optional second and further parameters.
	 * }
	 * @return string[] Required capabilities for the requested action.
	 */
	public function filter_map_meta_cap( array $required_caps, $cap, $user_id, array $args ) {
		if ( ! is_multisite() ) {
			return $required_caps;
		}

		if ( ! is_super_admin( $user_id ) ) {
			return $required_caps;
		}

		$trace  = new QM_Backtrace;
		$result = ( ! in_array( 'do_not_allow', $required_caps, true ) );

		array_unshift( $args, $user_id );
		array_unshift( $args, $cap );

		$this->data['caps'][] = array(
			'args'   => $args,
			'trace'  => $trace,
			'result' => $result,
		);

		return $required_caps;
	}

	public function process() {
		if ( empty( $this->data['caps'] ) ) {
			return;
		}

		$all_parts = array();
		$all_users = array();
		$components = array();

		$this->data['caps'] = array_filter( $this->data['caps'], array( $this, 'filter_remove_noise' ) );

		if ( self::hide_qm() ) {
			$this->data['caps'] = array_filter( $this->data['caps'], array( $this, 'filter_remove_qm' ) );
		}

		foreach ( $this->data['caps'] as $i => $cap ) {
			$name = $cap['args'][0];
			$parts = array_filter( preg_split( '#[_/-]#', $name ) );
			$this->data['caps'][ $i ]['parts'] = $parts;
			$this->data['caps'][ $i ]['name']  = $name;
			$this->data['caps'][ $i ]['user']  = $cap['args'][1];
			$this->data['caps'][ $i ]['args']  = array_slice( $cap['args'], 2 );
			$all_parts = array_merge( $all_parts, $parts );
			$all_users[] = $cap['args'][1];
			$component = $cap['trace']->get_component();
			$components[ $component->name ] = $component->name;
		}

		$this->data['parts'] = array_unique( array_filter( $all_parts ) );
		$this->data['users'] = array_unique( array_filter( $all_users ) );
		$this->data['components'] = $components;
	}

	public function filter_remove_noise( array $cap ) {
		$trace = $cap['trace']->get_trace();

		$exclude_files = array(
			ABSPATH . 'wp-admin/menu.php',
			ABSPATH . 'wp-admin/includes/menu.php',
		);
		$exclude_functions = array(
			'_wp_menu_output',
			'wp_admin_bar_render',
		);

		foreach ( $trace as $item ) {
			if ( isset( $item['file'] ) && in_array( $item['file'], $exclude_files, true ) ) {
				return false;
			}
			if ( isset( $item['function'] ) && in_array( $item['function'], $exclude_functions, true ) ) {
				return false;
			}
		}

		return true;
	}

}

function register_qm_collector_caps( array $collectors, QueryMonitor $qm ) {
	$collectors['caps'] = new QM_Collector_Caps;
	return $collectors;
}

add_filter( 'qm/collectors', 'register_qm_collector_caps', 20, 2 );
