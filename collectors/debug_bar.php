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

final class QM_Collector_Debug_Bar extends QM_Collector {

	public $id     = 'debug_bar';
	private $panel = null;

	public function __construct() {
		parent::__construct();
	}

	public function name() {
		$title = $this->get_panel()->title();
		/* translators: %s: Name of a Debug Bar panel */
		return sprintf( __( 'Debug Bar: %s', 'query-monitor' ), $title );
	}

	public function set_panel( Debug_Bar_Panel $panel ) {
		$this->panel = $panel;
	}

	public function get_panel() {
		return $this->panel;
	}

	public function process() {
		$this->get_panel()->prerender();
	}

	public function is_visible() {
		return $this->get_panel()->is_visible();
	}

	public function render() {
		return $this->get_panel()->render();
	}

}

function register_qm_collectors_debug_bar() {

	global $debug_bar;

	if ( class_exists( 'Debug_Bar' ) || qm_debug_bar_being_activated() ) {
		return;
	}

	$collectors = QM_Collectors::init();
	$qm = QueryMonitor::init();

	require_once $qm->plugin_path( 'classes/debug_bar.php' );

	$debug_bar = new Debug_Bar;
	$redundant = array(
		'debug_bar_actions_addon_panel',
		'debug_bar_remote_requests_panel',
		'debug_bar_screen_info_panel',
		'ps_listdeps_debug_bar_panel',
	);

	foreach ( $debug_bar->panels as $panel ) {
		$panel_id = strtolower( get_class( $panel ) );

		if ( in_array( $panel_id, $redundant ) ) {
			continue;
		}

		$collector = new QM_Collector_Debug_Bar;
		$collector->set_id( "debug_bar_{$panel_id}" );
		$collector->set_panel( $panel );

		$collectors->add( $collector );
	}

}

function qm_debug_bar_being_activated() {

	if ( ! is_admin() ) {
		return false;
	}

	if ( ! isset( $_REQUEST['action'] ) ) {
		return false;
	}

	if ( isset( $_GET['action'] ) ) {

		if ( ! isset( $_GET['plugin'] ) || ! isset( $_GET['_wpnonce'] ) ) {
			return false;
		}

		if ( 'activate' === $_GET['action'] && false !== strpos( $_GET['plugin'], 'debug-bar.php' ) ) {
			return true;
		}

	} elseif ( isset( $_POST['action'] ) ) {

		if ( ! isset( $_POST['checked'] ) || ! is_array( $_POST['checked'] ) || ! isset( $_POST['_wpnonce'] ) ) {
			return false;
		}

		if ( 'activate-selected' === $_POST['action'] && in_array( 'debug-bar/debug-bar.php', $_POST['checked'] ) ) {
			return true;
		}

	}

	return false;

}

add_action( 'init', 'register_qm_collectors_debug_bar' );
