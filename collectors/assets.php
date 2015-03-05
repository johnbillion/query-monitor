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

class QM_Collector_Assets extends QM_Collector {

	public $id = 'assets';

	public function __construct() {
		parent::__construct();
		add_action( 'admin_print_footer_scripts', array( $this, 'action_print_footer_scripts' ) );
		add_action( 'wp_print_footer_scripts',    array( $this, 'action_print_footer_scripts' ) );
		add_action( 'admin_head',                 array( $this, 'action_head' ), 999 );
		add_action( 'wp_head',                    array( $this, 'action_head' ), 999 );
		add_action( 'login_head',                 array( $this, 'action_head' ), 999 );
	}

	public function action_head() {
		global $wp_scripts, $wp_styles;

		$this->data['header_styles'] = $wp_styles->done;
		$this->data['header_scripts'] = $wp_scripts->done;

	}

	public function action_print_footer_scripts() {
		global $wp_scripts, $wp_styles;

		// @TODO remove the need for these raw scripts & styles to be collected
		$this->data['raw_scripts'] = $wp_scripts;
		$this->data['raw_styles']  = $wp_styles;

		$this->data['footer_scripts'] = array_diff( $wp_scripts->done, $this->data['header_scripts'] );
		$this->data['footer_styles']  = array_diff( $wp_styles->done, $this->data['header_styles'] );

	}

	public function process() {
		foreach ( array( 'scripts', 'styles' ) as $type ) {
			foreach ( array( 'header', 'footer' ) as $position ) {
				if ( empty( $this->data[ "{$position}_{$type}" ] ) ) {
					$this->data[ "{$position}_{$type}" ] = array();
				} else {
					sort( $this->data[ "{$position}_{$type}" ] );
				}
			}
			$broken = array_diff( $this->data[ "raw_{$type}" ]->queue, $this->data[ "raw_{$type}" ]->done );

			foreach ( $broken as $handle ) {
				$item   = $this->data[ "raw_{$type}" ]->query( $handle );
				$broken = array_merge( $broken, $this->get_broken_dependencies( $item, $this->data[ "raw_{$type}" ] ) );
			}

			$this->data[ "broken_{$type}" ] = array_unique( $broken );
			sort( $this->data[ "broken_{$type}" ] );

		}
	}

	protected function get_broken_dependencies( _WP_Dependency $item, WP_Dependencies $dependencies ) {

		$broken = array();

		foreach ( $item->deps as $handle ) {

			if ( $dep = $dependencies->query( $handle ) ) {
				$broken = array_merge( $broken, $this->get_broken_dependencies( $dep, $dependencies ) );
			} else {
				$broken[] = $item->handle;
			}

		}

		return $broken;

	}

	public function name() {
		return __( 'Scripts & Styles', 'query-monitor' );
	}

}

function register_qm_collector_assets( array $collectors, QueryMonitor $qm ) {
	$collectors['assets'] = new QM_Collector_Assets;
	return $collectors;
}

add_filter( 'qm/collectors', 'register_qm_collector_assets', 10, 2 );
