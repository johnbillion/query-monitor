<?php
/*
Plugin Name: Query Monitor
Description: Monitoring of database queries, hooks, conditionals and much more.
Version:     2.1
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

	var $overview = array();

	function __construct() {

		add_action( 'plugins_loaded',         array( $this, 'setup' ), 1 );
		add_action( 'init',                   array( $this, 'register_style' ) );
		add_action( 'wp_footer',              array( $this, 'register_output' ), 99 );
		add_action( 'admin_footer',           array( $this, 'register_output' ), 99 );

		register_activation_hook( __FILE__,   array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

		$this->plugin_dir = plugin_dir_path( __FILE__ );
		$this->plugin_url = plugin_dir_url( __FILE__ );

		foreach ( array(
			'overview', 'php_errors', 'db_queries', 'db_functions', 'transients', 'http', 'hooks',
			'query_vars', 'environment', 'admin', 'theme', 'conditionals', 'authentication'
		) as $component )
			include( "{$this->plugin_dir}/components/{$component}.php" );

		# These components are instantiated immediately, all others are on plugins_loaded
		foreach ( array(
			'QM_PHP_Errors'
		) as $class )
			$this->add_component( new $class );

	}

	function add_component( $component ) {
		$component->setup();
		$id = $component->id;
		$this->components->$id = $component;
	}

	function activate() {

		if ( $admins = $this->get_admins() )
			$admins->add_cap( 'view_query_monitor' );

		if ( !file_exists( WP_CONTENT_DIR . '/db.php' ) and function_exists( 'symlink' ) )
			@symlink( plugin_dir_path(  __FILE__ ) . 'wp-content/db.php', WP_CONTENT_DIR . '/db.php' );

	}

	function deactivate() {

		# We don't delete wp-content/db.php on deactivation in case it doesn't belong to Query Monitor

		if ( $admins = $this->get_admins() )
			$admins->remove_cap( 'view_query_monitor' );

	}

	function get_admins() {
		# @TODO this should use a cap not a role
		if ( is_multisite() )
			return false;
		else
			return get_role( 'administrator' );
	}

	function setup() {
		foreach ( apply_filters( 'qm', array() ) as $component )
			$this->add_component( $component );
	}

	function admin_bar_menu() {

		global $wp_admin_bar;

		if ( !is_admin_bar_showing() )
			return;

		$db  = $this->components->db_queries;
		$php = $this->components->php_errors;

		if ( isset( $php->php_errors['warning'] ) )
			$link_class = 'qm-warning';
		else if ( isset( $php->php_errors['notice'] ) )
			$link_class = 'qm-notice';
		else
			$link_class = '';

		# @TODO support not loading the DB monitoring class
		# @TODO support multiple WPDB classes

		if ( empty( $db->errors ) ) {
			$title = sprintf(
				_n( '%1$s<small>S</small> / %2$s<small>Q</small>', '%1$s<small>S</small> / %2$s<small>Q</small>', $this->overview['query_num'], 'query_monitor' ),
				$this->overview['load_time'],
				$this->overview['query_num']
			);
		} else {
			$link_class = 'qm-error';
			$title = sprintf(
				_n( '%1$s<small>S</small> / %2$s<small>Q</small> (%3$d error)', '%1$s<small>S</small> / %2$s<small>Q</small> (%3$d errors)', $db->errors, 'query_monitor' ),
				$this->overview['load_time'],
				$this->overview['query_num'],
				$db->errors
			);
		}

		$wp_admin_bar->add_menu( array(
			'id'    => 'query_monitor',
			'title' => $title,
			'href'  => '#qm-overview',
			'meta'  => array(
				'class' => $link_class . ' ' . $this->wpv()
			)
		) );

		if ( !empty( $db->errors ) ) {
			$wp_admin_bar->add_menu( array(
				'parent' => 'query_monitor',
				'id'     => 'query_monitor_errors',
				'title'  => sprintf( __( 'Database Errors (%s)', 'query_monitor' ), $db->errors ),
				'href'   => '#qm-overview'
			) );
		}

		foreach ( $this->components as $component ) {
			if ( $menus = $component->admin_menus() ) {
				foreach ( $menus as $menu )
					$wp_admin_bar->add_menu( $menu );
			}
		}

	}

	function show_query_monitor() {

		if ( is_multisite() ) {
			if ( current_user_can( 'manage_network_options' ) )
				return true;
		} else if ( current_user_can( 'view_query_monitor' ) ) {
			return true;
		}

		return $this->components->authentication->show_query_monitor();

	}

	function register_output() {

		if ( !$this->show_query_monitor() )
			return;

		foreach ( $this->components as $component )
			$component->process();

		$this->overview = array(
			'query_num'  => $this->components->db_queries->query_num,
			'query_time' => number_format_i18n( $this->components->db_queries->query_time, 4 ),
			'load_time'  => number_format_i18n( $this->components->overview->load_time, 2 ),
			'memory'     => number_format_i18n( $this->components->overview->memory / 1000 )
		);

		add_action( 'admin_bar_menu', array( $this, 'admin_bar_menu' ), 99 );
		add_action( 'shutdown',       array( $this, 'output' ), 0 );

	}

	function register_style() {

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

		$this->output_start();

		foreach ( $this->components as $component )
			$component->output();

		$this->output_close();

	}

	function output_start() {
		echo '<div id="qm">';
		echo '<p>Query Monitor</p>';
	}

	function output_close() {
		echo '</div>';
	}

	function wpv() {
		return 'qm-wp-' . ( floatval( $GLOBALS['wp_version'] ) * 10 );
	}

}

class QM {

	function __construct() {
	}

	protected function _filter_trace( $trace ) {

		$ignore_class = array(
			'W3_Db',
			'QueryMonitor',
			'QueryMonitorDB',
			'wpdb',
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
			'section_template'
		);

		if ( isset( $trace['class'] ) ) {

			if ( in_array( $trace['class'], $ignore_class ) )
				return null;
			else if ( 0 === strpos( $trace['class'], 'QM' ) )
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
		$trace = array_values( array_filter( array_map( array( $this, '_filter_trace' ), $trace ) ) );
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

	protected function id() {
		return "qm-{$this->id}";
	}

	protected function menu( $args ) {

		return wp_parse_args( $args, array(
			'parent' => 'query_monitor',
			'id'     => "query_monitor_{$this->id}",
			'href'   => "#qm-{$this->id}"
		) );

	}

	public function admin_menus() {
		if ( $menu = $this->admin_menu() ) {
			return array(
				$menu
			);
		} else {
			return false;
		}
	}

	protected function get_component( $component ) {
		global $querymonitor;
		# @TODO sanity check
		return $querymonitor->components->$component;
	}

	public function admin_menu() {
		return false;
	}

	public function process() {
		return false;
	}

	public function output() {
		return false;
	}

	public function setup() {
		return false;
	}

}

$querymonitor = new QueryMonitor;

?>