<?php
/*
Plugin Name: Query Monitor
Description: Monitoring of database queries, hooks, conditionals and much more.
Version:     2.1.3
Author:      John Blackbourn
Author URI:  http://lud.icro.us/


This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.


Query Monitor outputs info on:

  * Admin screen variables
  * Hooks and associated actions
  * HTTP API requests and responses
  * Database queries
  * Memory usage and page build time
  * Names of custom columns on admin screens
  * PHP warnings and notices
  * Query variables
  * Selected MySQL and PHP configuration
  * Selected WordPress variables
  * Template conditionals
  * Template file and body classes
  * Transient update calls


*/

class QueryMonitor {

	function __construct() {

		add_action( 'init',                   array( $this, 'enqueue_style' ) );
		add_action( 'admin_footer',           array( $this, 'register_output' ), 999 );
		add_action( 'wp_footer',              array( $this, 'register_output' ), 999 );
		register_activation_hook( __FILE__,   array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

		$this->plugin_dir = plugin_dir_path( __FILE__ );
		$this->plugin_url = plugin_dir_url( __FILE__ );

		foreach ( glob( "{$this->plugin_dir}/components/*.php" ) as $component )
			include( $component );

		foreach ( apply_filters( 'query_monitor_components', array() ) as $component )
			$this->add_component( $component );

	}

	function add_component( $component ) {
		$this->components->{$component->id} = $component;
	}

	function get_component( $id ) {
		if ( isset( $this->components->$id ) )
			return $this->components->$id;
		return false;
	}

	function get_components() {
		return $this->components;
	}

	function activate() {

		if ( $admins = $this->get_admins() )
			$admins->add_cap( 'view_query_monitor' );

		if ( !file_exists( WP_CONTENT_DIR . '/db.php' ) and function_exists( 'symlink' ) )
			@symlink( $this->plugin_dir . 'wp-content/db.php', WP_CONTENT_DIR . '/db.php' );

	}

	function deactivate() {

		if ( $admins = $this->get_admins() )
			$admins->remove_cap( 'view_query_monitor' );

		# Only delete db.php if it belongs to Query Monitor
		if ( class_exists( 'QueryMonitorDB' ) )
			@unlink( WP_CONTENT_DIR . '/db.php' );

	}

	function get_admins() {
		# @TODO this should use a cap not a role
		if ( is_multisite() )
			return false;
		else
			return get_role( 'administrator' );
	}

	function admin_bar_menu() {

		global $wp_admin_bar;

		$class = implode( ' ', apply_filters( 'query_monitor_class', array( $this->wpv() ) ) );
		$title = implode( ' / ', apply_filters( 'query_monitor_title', array() ) );

		if ( empty( $title ) )
			$title = 'Query Monitor';

		$wp_admin_bar->add_menu( array(
			'id'    => 'query_monitor',
			'title' => $title,
			'href'  => '#qm-overview',
			'meta'  => array(
				'class' => $class
			)
		) );

		foreach ( apply_filters( 'query_monitor_menus', array() ) as $menu )
			$wp_admin_bar->add_menu( $menu );

	}

	function show_query_monitor() {

		if ( is_multisite() ) {
			if ( current_user_can( 'manage_network_options' ) )
				return true;
		} else if ( current_user_can( 'view_query_monitor' ) ) {
			return true;
		}

		$auth = $this->get_component( 'authentication' );

		return $auth ? $auth->show_query_monitor() : false;

	}

	function register_output() {

		if ( !$this->show_query_monitor() )
			return;

		foreach ( $this->get_components() as $component )
			$component->process();

		add_action( 'admin_bar_menu', array( $this, 'admin_bar_menu' ), 99 );
		add_action( 'shutdown',       array( $this, 'output' ), 0 );

	}

	function enqueue_style() {

		if ( !$this->show_query_monitor() )
			return;

		wp_enqueue_style(
			'query_monitor',
			$this->plugin_url . 'query-monitor.css',
			null,
			filemtime( $this->plugin_dir . 'query-monitor.css' )
		);

	}

	function output() {

		# Flush the output buffer to avoid crashes
		while ( ob_get_length() )
			ob_flush();

		foreach ( $this->get_components() as $component )
			$component->process_late();

		$this->output_start();

		foreach ( $this->get_components() as $component ) {
			$component->output( array(
				'id' => $component->id()
			), $component->data );
		}

		$this->output_end();

	}

	function output_start() {
		echo '<div id="qm">';
		echo '<p>Query Monitor</p>';
	}

	function output_end() {
		echo '</div>';
	}

	function wpv() {
		return 'qm-wp-' . ( floatval( $GLOBALS['wp_version'] ) * 10 );
	}

}

class QM {

	var $data = array();

	function __construct() {
	}

	protected function _filter_trace( $trace ) {

		$ignore_class = array(
			'wpdb',
			'QueryMonitor',
			'QueryMonitorDB',
			'W3_Db',
			'Debug_Bar_PHP'
		);
		$ignore_func = array(
			'include_once',
			'require_once',
			'include',
			'require',
			'call_user_func_array',
			'call_user_func',
			'trigger_error',
			'_doing_it_wrong',
			'_deprecated_argument',
			'_deprecated_file',
			'_deprecated_function'
		);
		$show_arg = array(
			'do_action',
			'apply_filters',
			'do_action_ref_array',
			'apply_filters_ref_array',
			'get_template_part',
			'section_template',
			'get_header',
			'get_sidebar',
			'get_footer'
		);

		if ( isset( $trace['class'] ) ) {

			if ( in_array( $trace['class'], $ignore_class ) )
				return null;
			else
				return $trace['class'] . $trace['type'] . $trace['function'] . '()';

		} else {

			if ( in_array( $trace['function'], $ignore_func ) )
				return null;
			else if ( isset( $trace['args'] ) and in_array( $trace['function'], $show_arg ) )
				return $trace['function'] . "('{$trace['args'][0]}')";
			else
				return $trace['function'] . '()';

		}

	}

	protected function backtrace() {
		$trace = debug_backtrace( false );
		$trace = array_map( array( $this, '_filter_trace' ), $trace );
		$trace = array_values( array_filter( $trace ) );
		return $trace;
	}

	protected function timer_stop_float() {
		global $timestart;
		return microtime( true ) - $timestart;
	}

	protected function _sort( $a, $b ) {
		if ( $a['ltime'] == $b['ltime'] )
			return 0;
		else
			return ( $a['ltime'] > $b['ltime'] ) ? -1 : 1;
	}

	public function id() {
		return "qm-{$this->id}";
	}

	protected function menu( $args ) {

		return wp_parse_args( $args, array(
			'parent' => 'query_monitor',
			'id'     => "query_monitor_{$this->id}",
			'href'   => '#' . $this->id()
		) );

	}

	protected function get_component( $id ) {
		global $querymonitor;
		return $querymonitor->get_component( $id );
	}

	public function process() {
		return false;
	}

	public function process_late() {
		return false;
	}

	public function output() {
		return false;
	}

}

if ( !defined( 'ABSPATH' ) )
    die();

$querymonitor = new QueryMonitor;

?>