<?php
/*
Plugin Name: Query Monitor
Description: Monitoring of database queries, hooks, conditionals and more.
Version:     2.6.10
Plugin URI:  https://querymonitor.com/
Author:      John Blackbourn
Author URI:  https://johnblackbourn.com/
Text Domain: query-monitor
Domain Path: /languages/
License:     GPL v2 or later

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

defined( 'ABSPATH' ) or die();

if ( defined( 'QM_DISABLED' ) and QM_DISABLED ) {
	return;
}

# No autoloaders for us. See https://github.com/johnbillion/QueryMonitor/issues/7
$qm_dir = dirname( __FILE__ );
foreach ( array( 'Backtrace', 'Collector', 'Plugin', 'Util', 'Dispatcher', 'Output' ) as $qm_class ) {
	require_once "{$qm_dir}/{$qm_class}.php";
}

class QueryMonitor extends QM_Plugin {

	protected $collectors  = array();
	protected $dispatchers = array();

	protected function __construct( $file ) {

		# Actions
		add_action( 'plugins_loaded', array( $this, 'action_plugins_loaded' ) );
		add_action( 'init',           array( $this, 'action_init' ) );
		add_action( 'shutdown',       array( $this, 'action_shutdown' ), 0 );

		# Filters
		add_filter( 'pre_update_option_active_plugins',               array( $this, 'filter_active_plugins' ) );
		add_filter( 'pre_update_site_option_active_sitewide_plugins', array( $this, 'filter_active_sitewide_plugins' ) );

		# [Dea|A]ctivation
		register_activation_hook(   __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

		# Parent setup:
		parent::__construct( $file );

		# Collectors:
		QM_Util::include_files( $this->plugin_path( 'collectors' ) );

		foreach ( apply_filters( 'query_monitor_collectors', array() ) as $collector ) {
			$this->add_collector( $collector );
		}

	}

	public function action_plugins_loaded() {

		# Dispatchers:
		QM_Util::include_files( $this->plugin_path( 'dispatchers' ) );

		foreach ( apply_filters( 'query_monitor_dispatchers', array(), $this ) as $dispatcher ) {
			$this->add_dispatcher( $dispatcher );
		}

	}

	public function add_collector( QM_Collector $collector ) {
		$this->collectors[$collector->id] = $collector;
	}

	public function add_dispatcher( QM_Dispatcher $dispatcher ) {
		$this->dispatchers[$dispatcher->id] = $dispatcher;
	}

	public static function get_collector( $id ) {
		$qm = self::init();
		if ( isset( $qm->collectors[$id] ) ) {
			return $qm->collectors[$id];
		}
		return false;
	}

	public function get_collectors() {
		return $this->collectors;
	}

	public function get_dispatchers() {
		return $this->dispatchers;
	}

	public function activate( $sitewide = false ) {

		if ( $admins = QM_Util::get_admins() ) {
			$admins->add_cap( 'view_query_monitor' );
		}

		if ( ! file_exists( $db = WP_CONTENT_DIR . '/db.php' ) and function_exists( 'symlink' ) ) {
			@symlink( $this->plugin_path( 'wp-content/db.php' ), $db );
		}

		if ( $sitewide ) {
			update_site_option( 'active_sitewide_plugins', get_site_option( 'active_sitewide_plugins'  ) );
		} else {
			update_option( 'active_plugins', get_option( 'active_plugins'  ) );
		}

	}

	public function deactivate() {

		if ( $admins = QM_Util::get_admins() ) {
			$admins->remove_cap( 'view_query_monitor' );
		}

		# Only delete db.php if it belongs to Query Monitor
		if ( class_exists( 'QueryMonitorDB' ) ) {
			unlink( WP_CONTENT_DIR . '/db.php' );
		}

	}

	public function should_process() {

		# Don't process if the minimum required actions haven't fired:

		if ( is_admin() ) {

			if ( ! did_action( 'admin_init' ) ) {
				return false;
			}

		} else {

			if ( ! ( did_action( 'wp' ) or did_action( 'login_init' ) ) ) {
				return false;
			}

		}

		$e = error_get_last();

		# Don't process if a fatal has occurred:
		if ( ! empty( $e ) and ( $e['type'] & ( E_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR ) ) ) {
			return false;
		}

		foreach ( $this->get_dispatchers() as $dispatcher ) {

			# At least one dispatcher is active, so we need to process:
			if ( $dispatcher->is_active() ) {
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
			$collector->tear_down();
			$collector->process();
		}

		foreach ( $this->get_dispatchers() as $dispatcher ) {

			if ( ! $dispatcher->is_active() ) {
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

	public function filter_active_plugins( $plugins ) {

		if ( empty( $plugins ) ) {
			return $plugins;
		}

		$f = preg_quote( basename( $this->plugin_base() ) );

		return array_merge(
			preg_grep( '/' . $f . '$/', $plugins ),
			preg_grep( '/' . $f . '$/', $plugins, PREG_GREP_INVERT )
		);

	}

	public function filter_active_sitewide_plugins( $plugins ) {

		if ( empty( $plugins ) ) {
			return $plugins;
		}

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

	public static function symlink_warning() {
		$db = WP_CONTENT_DIR . '/db.php';
		trigger_error( sprintf( __( 'The symlink at %s is no longer pointing to the correct location. Please remove the symlink, then deactivate and reactivate Query Monitor.', 'query-monitor' ), "<code>{$db}</code>" ), E_USER_WARNING );
	}

	public static function init( $file = null ) {

		static $instance = null;

		if ( ! $instance ) {
			$instance = new QueryMonitor( $file );
		}

		return $instance;

	}

}

QueryMonitor::init( __FILE__ );
