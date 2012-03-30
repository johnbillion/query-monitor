<?php
/*
Plugin Name: Query Monitor
Description: Monitoring of database queries, hooks, conditionals and much more.
Version:     2.1.7
Author:      John Blackbourn
Author URI:  http://lud.icro.us/

© 2012 John Blackbourn

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

		add_action( 'init',                   array( $this, 'enqueue_stuff' ) );
		add_action( 'admin_footer',           array( $this, 'register_output' ), 999 );
		add_action( 'wp_footer',              array( $this, 'register_output' ), 999 );
		add_action( 'admin_bar_menu',         array( $this, 'admin_bar_menu' ), 999 );
		add_filter( 'wp_redirect',            array( $this, 'redirect' ), 999 );
		register_activation_hook( __FILE__,   array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

		$this->plugin_dir   = untrailingslashit( plugin_dir_path( __FILE__ ) );
		$this->plugin_url   = untrailingslashit( plugin_dir_url( __FILE__ ) );
		$this->is_multisite = ( function_exists( 'is_multisite' ) and is_multisite() );

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

	function redirect( $location ) {

		if ( !QM_LOG_REDIRECT_DATA )
			return $location;
		if ( false === strpos( $location, $_SERVER['HTTP_HOST'] ) )
			return $location;
		if ( !$this->show_query_monitor() )
			return $location;

		# @TODO we're only doing this for logged-in users at the moment because I can't decide how
		# best to generate and retrieve a key for non-logged-in users who have a QM auth cookie.

		if ( !is_user_logged_in() )
			return $location;

		$current = ( is_ssl() )
			? 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']
			: 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

		$data = array(
			'url' => $current
		);

		foreach ( $this->get_components() as $component )
			$component->process();

		foreach ( $this->get_components() as $component )
			$component->process_late();

		foreach ( $this->get_components() as $component )
			$data['components'][$component->id] = $component->get_data();

		$key = 'qm_redirect_data_' . get_current_user_id();

		set_transient( $key, $data, 3600 );

		return $location;

	}

	function get_redirect_data() {

		if ( !wp_get_referer() )
			return null;
		if ( !is_user_logged_in() )
			return null;

		$key  = 'qm_redirect_data_' . get_current_user_id();
		$data = get_transient( $key );

		if ( empty( $data ) )
			return null;

		delete_transient( $key );

		return $data;

	}

	function activate() {

		if ( $admins = $this->get_admins() )
			$admins->add_cap( 'view_query_monitor' );

		if ( !file_exists( WP_CONTENT_DIR . '/db.php' ) and function_exists( 'symlink' ) )
			@symlink( $this->plugin_dir . '/wp-content/db.php', WP_CONTENT_DIR . '/db.php' );

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
		if ( $this->is_multisite )
			return false;
		else
			return get_role( 'administrator' );
	}

	function admin_bar_menu( $wp_admin_bar ) {

		if ( !$this->show_query_monitor() )
			return;

		$class = implode( ' ', array( 'hide-if-js', $this->wpv() ) );
		$title = 'Query Monitor';

		$wp_admin_bar->add_menu( array(
			'id'    => 'query_monitor',
			'title' => $title,
			'href'  => '#qm-overview',
			'meta'  => array(
				'class' => $class
			)
		) );

		$wp_admin_bar->add_menu( array(
			'parent' => 'query_monitor',
			'id'     => 'query_monitor_placeholder',
			'title'  => $title,
			'href'   => '#qm-overview'
		) );

	}

	function js_admin_bar_menu() {

		$class = implode( ' ', apply_filters( 'query_monitor_class', array( $this->wpv() ) ) );
		$title = implode( ' / ', apply_filters( 'query_monitor_title', array() ) );

		if ( empty( $title ) )
			$title = 'Query Monitor';

		$admin_bar_menu = array(
			'top' => array(
				'title' => $title,
				'class' => $class
			),
			'sub' => array()
		);

		foreach ( apply_filters( 'query_monitor_menus', array() ) as $menu )
			$admin_bar_menu['sub'][] = $menu;

		return $admin_bar_menu;

	}

	function show_query_monitor() {

		if ( $this->is_multisite ) {
			if ( current_user_can( 'manage_network_options' ) )
				return true;
		} else if ( current_user_can( 'view_query_monitor' ) ) {
			return true;
		}

		if ( $auth = $this->get_component( 'authentication' ) )
			return $auth->show_query_monitor();

		return false;

	}

	function register_output() {

		if ( !$this->show_query_monitor() )
			return;

		foreach ( $this->get_components() as $component )
			$component->process();

		add_action( 'shutdown', array( $this, 'output' ), 0 );

	}

	function enqueue_stuff() {

		if ( !$this->show_query_monitor() )
			return;

		wp_enqueue_style(
			'query_monitor',
			$this->plugin_url . '/query-monitor.css',
			null,
			filemtime( $this->plugin_dir . '/query-monitor.css' )
		);
		wp_enqueue_script(
			'query_monitor',
			$this->plugin_url . '/query-monitor.js',
			array( 'jquery' ),
			filemtime( $this->plugin_dir . '/query-monitor.js' ),
			true
		);

	}

	function output() {

		# Flush the output buffer to avoid crashes
		while ( ob_get_length() )
			ob_flush();

		foreach ( $this->get_components() as $component )
			$component->process_late();

		echo '<script type="text/javascript">' . "\n\n";
		echo 'var qm = ' . json_encode( $this->js_admin_bar_menu() ) . ';' . "\n\n";
		echo '</script>' . "\n\n";

		echo '<div id="qm">';
		echo '<p>Query Monitor</p>';

		foreach ( $this->get_components() as $component ) {
			$component->output( array(
				'id' => $component->id()
			), $component->data );
		}

		echo '</div>';

	}

	function wpv() {
		return 'qm-wp-' . ( floatval( $GLOBALS['wp_version'] ) * 10 );
	}

}

class QM {

	var $data = array();

	static $ignore_class = array(
		'wpdb',
		'QueryMonitor',
		'QueryMonitorDB',
		'W3_Db',
		'Debug_Bar_PHP'
	);
	static $ignore_func = array(
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
	static $show_arg = array(
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
	static $filtered = false;

	function __construct() {

		if ( !self::$filtered ) {

			# Only run apply_filters on these once
			self::$ignore_class = apply_filters( 'query_monitor_ignore_class', self::$ignore_class );
			self::$ignore_func  = apply_filters( 'query_monitor_ignore_func',  self::$ignore_func );
			self::$show_arg     = apply_filters( 'query_monitor_show_arg',     self::$show_arg );
			self::$filtered = true;

		}

		$this->is_multisite = ( function_exists( 'is_multisite' ) and is_multisite() );
	}

	protected function _filter_trace( $trace ) {

		if ( isset( $trace['class'] ) ) {

			if ( in_array( $trace['class'], self::$ignore_class ) )
				return null;
			else
				return $trace['class'] . $trace['type'] . $trace['function'] . '()';

		} else {

			if ( in_array( $trace['function'], self::$ignore_func ) )
				return null;
			else if ( isset( $trace['args'][0] ) and in_array( $trace['function'], self::$show_arg ) )
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
			'id'   => "query_monitor_{$this->id}",
			'href' => '#' . $this->id()
		) );

	}

	protected function get_component( $id ) {
		global $querymonitor;
		return $querymonitor->get_component( $id );
	}

	public function get_data() {
		if ( isset( $this->data ) )
			return $this->data;
		return null;
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

if ( !defined( 'QM_LOG_REDIRECT_DATA' ) )
	define( 'QM_LOG_REDIRECT_DATA', false );

$GLOBALS['querymonitor'] = new QueryMonitor;

?>