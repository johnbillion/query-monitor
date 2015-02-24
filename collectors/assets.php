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

		$this->data['raw_scripts'] = $wp_scripts;
		$this->data['raw_styles']  = $wp_styles;

		$this->data['footer_scripts'] = array_diff( $wp_scripts->done, $this->data['header_scripts'] );
		$this->data['footer_styles']  = array_diff( $wp_styles->done, $this->data['header_styles'] );

	}

	public function process() {
		foreach ( array( 'header_scripts', 'header_styles', 'footer_scripts', 'footer_styles' ) as $data ) {
			if ( empty( $this->data[ $data ] ) ) {
				$this->data[ $data ] = array();
			} else {
				sort( $this->data[ $data ] );
			}
		}
	}

	public function name() {
		return __( 'Scripts & Styles', 'query-monitor' );
	}

}

QM_Collectors::add( new QM_Collector_Assets );
