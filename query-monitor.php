<?php
/*
Plugin Name: Query Monitor
Description: Monitoring of database queries, hooks, conditionals and more.
Version:     2.6.4
Plugin URI:  https://github.com/johnbillion/query-monitor
Author:      John Blackbourn
Author URI:  https://johnblackbourn.com/
Text Domain: query-monitor
Domain Path: /languages/
License:     GPL v2 or later

Copyright 2014 John Blackbourn

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

*/

defined( 'ABSPATH' ) or die();

if ( defined( 'QM_DISABLED' ) and QM_DISABLED )
	return;

# No autoloaders for us. See https://github.com/johnbillion/QueryMonitor/issues/7
$qm_dir = dirname( __FILE__ );
foreach ( array( 'Backtrace', 'Collector', 'Plugin', 'Util', 'Dispatcher', 'Output' ) as $qm_class )
	require_once "{$qm_dir}/{$qm_class}.php";

class QueryMonitor extends QM_Plugin {

	protected $collectors  = array();
	protected $dispatchers = array();

	protected function __construct( $file ) {

		# Actions
		add_action( 'init',     array( $this, 'action_init' ) );
		add_action( 'shutdown', array( $this, 'action_shutdown' ), 0 );

		# Filters
		add_filter( 'pre_update_option_active_plugins',               array( $this, 'filter_active_plugins' ) );
		add_filter( 'pre_update_site_option_active_sitewide_plugins', array( $this, 'filter_active_sitewide_plugins' ) );

		# [Dea|A]ctivation
		register_activation_hook(   __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

		# Parent setup:
		parent::__construct( $file );

		# Collectors:
		foreach ( glob( $this->plugin_path( 'collectors/*.php' ) ) as $collector )
			include $collector;

		foreach ( apply_filters( 'query_monitor_collectors', array() ) as $collector )
			$this->add_collector( $collector );

		# Dispatchers:
		foreach ( glob( $this->plugin_path( 'dispatchers/*.php' ) ) as $dispatcher )
			include $dispatcher;

		foreach ( apply_filters( 'query_monitor_dispatchers', array(), $this ) as $dispatcher )
			$this->add_dispatcher( $dispatcher );

	}

	public function add_collector( QM_Collector $collector ) {
		$this->collectors[$collector->id] = $collector;
	}

	public function add_dispatcher( QM_Dispatcher $dispatcher ) {
		$this->dispatchers[$dispatcher->id] = $dispatcher;
	}

	public static function get_collector( $id ) {
		$qm = self::init();
		if ( isset( $qm->collectors[$id] ) )
			return $qm->collectors[$id];
		return false;
	}

	public function get_collectors() {
		return $this->collectors;
	}

	public function get_dispatchers() {
		return $this->dispatchers;
	}

	public function activate( $sitewide = false ) {

		if ( $admins = QM_Util::get_admins() )
			$admins->add_cap( 'view_query_monitor' );

		if ( ! file_exists( $db = WP_CONTENT_DIR . '/db.php' ) and function_exists( 'symlink' ) )
			@symlink( $this->plugin_path( 'wp-content/db.php' ), $db );

		if ( $sitewide )
			update_site_option( 'active_sitewide_plugins', get_site_option( 'active_sitewide_plugins'  ) );
		else
			update_option( 'active_plugins', get_option( 'active_plugins'  ) );

	}

	public function deactivate() {

		if ( $admins = QM_Util::get_admins() )
			$admins->remove_cap( 'view_query_monitor' );

		# Only delete db.php if it belongs to Query Monitor
		if ( class_exists( 'QueryMonitorDB' ) )
			unlink( WP_CONTENT_DIR . '/db.php' );

	}

	public function user_can_view() {

		if ( !did_action( 'plugins_loaded' ) )
			return false;

		if ( is_multisite() ) {
			if ( current_user_can( 'manage_network_options' ) )
				return true;
		} else if ( current_user_can( 'view_query_monitor' ) ) {
			return true;
		}

		if ( $auth = self::get_collector( 'authentication' ) )
			return $auth->user_can_view();

		return false;

	}

	public function should_process() {

		# Don't process if the minimum required actions haven't fired:

		if ( is_admin() ) {

			if ( ! did_action( 'admin_init' ) ) {
				return false;
			}

		} else {

			if ( ! did_action( 'wp' ) ) {
				return false;
			}

		}

		$e = error_get_last();

		# Don't process if a fatal has occurred:
		if ( ! empty( $e ) and ( 1 === $e['type'] ) ) {
			return false;
		}

		foreach ( $this->get_dispatchers() as $dispatcher ) {

			# At least one dispatcher is active, so we need to process:
			if ( $dispatcher->active() ) {
				return true;
			}

		}

		return false;

	}

	public function action_shutdown() {

		if ( ! $this->should_process() ) {
			return;
		}

		foreach ( $this->get_collectors() as $collector ) {
			$collector->process();
		}

		foreach ( $this->get_dispatchers() as $dispatcher ) {

			if ( ! $dispatcher->active() ) {
				continue;
			}

			$dispatcher->before_output();

			foreach ( $this->get_collectors() as $collector ) {
				$dispatcher->output( $collector );
			}

			$dispatcher->after_output();

		}

	}

	public function action_init() {

		load_plugin_textdomain( 'query-monitor', false, dirname( $this->plugin_base() ) . '/languages' );

		foreach ( $this->get_dispatchers() as $dispatcher ) {
			$dispatcher->init();
		}

	}

	public function filter_active_plugins( array $plugins ) {

		$f = preg_quote( basename( $this->plugin_base() ) );

		return array_merge(
			preg_grep( '/' . $f . '$/', $plugins ),
			preg_grep( '/' . $f . '$/', $plugins, PREG_GREP_INVERT )
		);

	}

	public function filter_active_sitewide_plugins( array $plugins ) {

		$f = $this->plugin_base();

		if ( isset( $plugins[$f] ) ) {

			unset( $plugins[$f] );

			return array_merge( array(
				$f => time(),
			), $plugins );

		} else {
			return $plugins;
		}

	}

	public static function init( $file = null ) {

		static $instance = null;

		if ( ! $instance )
			$instance = new QueryMonitor( $file );

		return $instance;

	}

}

QueryMonitor::init( __FILE__ );
