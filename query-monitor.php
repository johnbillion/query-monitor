<?php
/*
Plugin Name: Query Monitor
Description: Monitoring of database queries, hooks, conditionals and more.
Version:     2.2.4
Author:      John Blackbourn
Author URI:  http://lud.icro.us/
Text Domain: query-monitor
Domain Path: /languages/

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
  * Template file name and body classes
  * Transient update calls


*/

class QueryMonitor {

	function __construct() {

		add_action( 'init',                   array( $this, 'init' ) );
		add_action( 'admin_footer',           array( $this, 'register_output' ), 999 );
		add_action( 'wp_footer',              array( $this, 'register_output' ), 999 );
		add_action( 'admin_bar_menu',         array( $this, 'admin_bar_menu' ), 999 );

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
			'id'    => 'query-monitor',
			'title' => $title,
			'href'  => '#qm-overview',
			'meta'  => array(
				'class' => $class
			)
		) );

		$wp_admin_bar->add_menu( array(
			'parent' => 'query-monitor',
			'id'     => 'query-monitor-placeholder',
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

	function init() {

		global $wp_locale;

		load_plugin_textdomain( 'query-monitor', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

		if ( !$this->show_query_monitor() )
			return;

		if ( !defined( 'DONOTCACHEPAGE' ) )
			define( 'DONOTCACHEPAGE', 1 );

		wp_enqueue_style(
			'query-monitor',
			$this->plugin_url . '/query-monitor.css',
			null,
			filemtime( $this->plugin_dir . '/query-monitor.css' )
		);
		wp_enqueue_script(
			'query-monitor',
			$this->plugin_url . '/query-monitor.js',
			array( 'jquery' ),
			filemtime( $this->plugin_dir . '/query-monitor.js' ),
			true
		);
		wp_localize_script(
			'query-monitor',
			'qm_locale',
			(array) $wp_locale
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
		'ExtQuery',
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
	static $file_components = array();
	static $file_dirs = array();

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

	protected function convert_hr_to_bytes( $size ) {

		# Annoyingly, wp_convert_hr_to_bytes() is defined in a file that's only
		# loaded in the admin area, so we'll use our own version.
		# See http://core.trac.wordpress.org/ticket/17725

		$bytes = (float) $size;

		if ( $bytes ) {
			$last = strtolower( substr( $size, -1 ) );
			$pos = strpos( ' kmg', $last, 1);
			if ( $pos )
				$bytes *= pow( 1024, $pos );
			$bytes = round( $bytes );
		}

		return $bytes;

	}

	public function standard_dir( $dir ) {
		$dir = str_replace( '\\', '/', $dir );
		$dir = preg_replace( '|/+|', '/', $dir );
		return $dir;
	}

	public function get_file_component( $file ) {

		if ( isset( self::$file_components[$file] ) )
			return self::$file_components[$file];

		if ( empty( self::$file_dirs ) ) {
			self::$file_dirs['plugin']     = $this->standard_dir( WP_PLUGIN_DIR );
			self::$file_dirs['muplugin']   = $this->standard_dir( WPMU_PLUGIN_DIR );
			self::$file_dirs['stylesheet'] = $this->standard_dir( get_stylesheet_directory() );
			self::$file_dirs['template']   = $this->standard_dir( get_template_directory() );
			self::$file_dirs['other']      = $this->standard_dir( WP_CONTENT_DIR );
			self::$file_dirs['core']       = $this->standard_dir( ABSPATH );
		}

		foreach ( self::$file_dirs as $component => $dir ) {
			if ( 0 === strpos( $file, $dir ) )
				break;
		}

		return self::$file_components[$file] = $component;

	}

	public function id() {
		return "qm-{$this->id}";
	}

	protected function menu( $args ) {

		return wp_parse_args( $args, array(
			'id'   => "query-monitor-{$this->id}",
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

$GLOBALS['querymonitor'] = new QueryMonitor;

?>