<?php
/*
Plugin Name: Query Monitor
Description: Monitoring of database queries, hooks, conditionals and more.
Version:     2.4b
Author:      John Blackbourn
Author URI:  http://johnblackbourn.com/
Text Domain: query-monitor
Domain Path: /languages/
License:     GPL v2 or later

© 2013 John Blackbourn

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

@ TODO:

 * Log and display queries from page loads before wp_redirect()
 * Display queries from AJAX calls
 * Show queried object info
 * Show hooks attached to some selected filters, eg request, parse_request
 * Add 'Component' filter to PHP errors list
 * Correctly show theme template used when using BuddyPress
 * Ignore dbDelta() in caller list

*/

defined( 'ABSPATH' ) or die();

class QueryMonitor {

	protected $components = array();

	public function __construct() {

		# Actions
		add_action( 'init',           array( $this, 'action_init' ) );
		add_action( 'admin_footer',   array( $this, 'action_footer' ), 999 );
		add_action( 'wp_footer',      array( $this, 'action_footer' ), 999 );
		add_action( 'login_footer',   array( $this, 'action_footer' ), 999 );
		add_action( 'admin_bar_menu', array( $this, 'action_admin_bar_menu' ), 999 );

		# Filters
		add_filter( 'pre_update_option_active_plugins',               array( $this, 'filter_active_plugins' ) );
		add_filter( 'pre_update_site_option_active_sitewide_plugins', array( $this, 'filter_active_sitewide_plugins' ) );

		register_activation_hook(   QM_Util::file( __FILE__ ), array( $this, 'activate' ) );
		register_deactivation_hook( QM_Util::file( __FILE__ ), array( $this, 'deactivate' ) );

		$this->plugin_dir = untrailingslashit( plugin_dir_path( __FILE__ ) );
		$this->plugin_url = untrailingslashit( plugin_dir_url( __FILE__ ) );

		foreach ( glob( "{$this->plugin_dir}/components/*.php" ) as $component )
			include $component;

		foreach ( apply_filters( 'query_monitor_components', array() ) as $component )
			$this->add_component( $component );

	}

	public function add_component( QM_Component $component ) {
		$this->components[$component->id] = $component;
	}

	public function get_component( $id ) {
		if ( isset( $this->components[$id] ) )
			return $this->components[$id];
		return false;
	}

	public function get_components() {
		return $this->components;
	}

	public function activate( $sitewide = false ) {

		if ( $admins = QM_Util::get_admins() )
			$admins->add_cap( 'view_query_monitor' );

		if ( !file_exists( WP_CONTENT_DIR . '/db.php' ) and function_exists( 'symlink' ) )
			@symlink( $this->plugin_dir . '/wp-content/db.php', WP_CONTENT_DIR . '/db.php' );

		if ( $sitewide )
			update_site_option( 'active_sitewide_plugins', $this->load_first_sitewide( get_site_option( 'active_sitewide_plugins'  ) ) );
		else
			update_option( 'active_plugins', $this->load_first( get_option( 'active_plugins'  ) ) );

	}

	public function deactivate() {

		if ( $admins = QM_Util::get_admins() )
			$admins->remove_cap( 'view_query_monitor' );

		# Only delete db.php if it belongs to Query Monitor
		if ( class_exists( 'QueryMonitorDB' ) )
			unlink( WP_CONTENT_DIR . '/db.php' );

	}

	public function action_admin_bar_menu( WP_Admin_Bar $wp_admin_bar ) {

		if ( !$this->show_query_monitor() )
			return;

		$class = implode( ' ', array( 'hide-if-js', QM_Util::wpv() ) );
		$title = __( 'Query Monitor', 'query-monitor' );

		$wp_admin_bar->add_menu( array(
			'id'    => 'query-monitor',
			'title' => $title,
			'href'  => '#qm-overview',
			'meta'  => array(
				'classname' => $class
			)
		) );

		$wp_admin_bar->add_menu( array(
			'parent' => 'query-monitor',
			'id'     => 'query-monitor-placeholder',
			'title'  => $title,
			'href'   => '#qm-overview'
		) );

	}

	public function js_admin_bar_menu() {

		$class = implode( ' ', apply_filters( 'query_monitor_class', array( QM_Util::wpv() ) ) );
		$title = implode( ' / ', apply_filters( 'query_monitor_title', array() ) );

		if ( empty( $title ) )
			$title = __( 'Query Monitor', 'query-monitor' );

		$admin_bar_menu = array(
			'top' => array(
				'title'     => sprintf( '<span class="ab-icon">QM</span><span class="ab-label">%s</span>', $title ),
				'classname' => $class
			),
			'sub' => array()
		);

		foreach ( apply_filters( 'query_monitor_menus', array() ) as $menu )
			$admin_bar_menu['sub'][] = $menu;

		return $admin_bar_menu;

	}

	public function show_query_monitor() {

		if ( isset( $this->show_query_monitor ) )
			return $this->show_query_monitor;

		if ( isset( $_REQUEST['wp_customize'] ) and 'on' == $_REQUEST['wp_customize'] )
			return $this->show_query_monitor = false;

		if ( is_multisite() ) {
			if ( current_user_can( 'manage_network_options' ) )
				return $this->show_query_monitor = true;
		} else if ( current_user_can( 'view_query_monitor' ) ) {
			return $this->show_query_monitor = true;
		}

		if ( $auth = $this->get_component( 'authentication' ) )
			return $this->show_query_monitor = $auth->show_query_monitor();

		return $this->show_query_monitor = false;

	}

	public function action_footer() {

		if ( !$this->show_query_monitor() )
			return;

		foreach ( $this->get_components() as $component )
			$component->process();

		add_action( 'shutdown', array( $this, 'output' ), 0 );

	}

	public function action_init() {

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

	public function output() {

		if ( function_exists( 'wp_is_mobile' ) and wp_is_mobile() )
			$qm_class = 'qm-mobile';
		else if ( $GLOBALS['is_iphone'] )
			$qm_class = 'qm-mobile';
		else
			$qm_class = '';

		# Flush the output buffer to avoid crashes
		if ( !is_feed() ) {
			while ( ob_get_length() )
				ob_flush();
		}

		foreach ( $this->get_components() as $component )
			$component->process_late();

		$qm = array(
			'menu'        => $this->js_admin_bar_menu(),
			'ajax_errors' => array() # @TODO move this into the php_errors component
		);

		echo '<script type="text/javascript">' . "\n\n";
		echo 'var qm = ' . json_encode( $qm ) . ';' . "\n\n";
		echo '</script>' . "\n\n";

		echo '<div id="qm" class="' . $qm_class . '">';
		echo '<p>' . __( 'Query Monitor', 'query-monitor' ) . '</p>';

		foreach ( $this->get_components() as $component ) {
			$component->output( array(
				'id' => $component->id()
			), $component->data );
		}

		echo '</div>';

	}

	public function filter_active_plugins( array $plugins ) {

		$f = preg_quote( basename( __FILE__ ) );

		return array_merge(
			preg_grep( '/' . $f . '$/', $plugins ),
			preg_grep( '/' . $f . '$/', $plugins, PREG_GREP_INVERT )
		);

	}

	public function filter_active_sitewide_plugins( array $plugins ) {

		$f = plugin_basename( __FILE__ );

		if ( isset( $plugins[$f] ) ) {

			unset( $plugins[$f] );

			return array_merge( array(
				$f => time(),
			), $plugins );

		} else {
			return $plugins;
		}

	}

}

require_once dirname( __FILE__ ) . '/class.qm-util.php';
require_once dirname( __FILE__ ) . '/class.qm-component.php';

$GLOBALS['querymonitor'] = new QueryMonitor;
