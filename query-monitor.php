<?php
/*
Plugin Name: Query Monitor
Description: Monitoring of database queries, hooks, conditionals and more.
Version:     2.11.0
Plugin URI:  https://github.com/johnbillion/querymonitor
Author:      John Blackbourn
Author URI:  https://johnblackbourn.com/
Text Domain: query-monitor
Domain Path: /languages/
License:     GPL v2 or later

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

defined( 'ABSPATH' ) or die();

if ( defined( 'QM_DISABLED' ) and QM_DISABLED ) {
	return;
}

if ( 'cli' === php_sapi_name() && ! defined( 'QM_TESTS' ) ) {
	# For the time being, let's not load QM when using the CLI because we've no persistent storage and no means of
	# outputting collected data on the CLI. This will hopefully change in a future version of QM.
	return;
}

# No autoloaders for us. See https://github.com/johnbillion/QueryMonitor/issues/7
$qm_dir = dirname( __FILE__ );
foreach ( array( 'Backtrace', 'Collectors', 'Collector', 'Plugin', 'Util', 'Dispatchers', 'Dispatcher', 'Output' ) as $qm_class ) {
	require_once "{$qm_dir}/classes/{$qm_class}.php";
}

class QueryMonitor extends QM_Plugin {

	protected function __construct( $file ) {

		# Actions
		add_action( 'plugins_loaded', array( $this, 'action_plugins_loaded' ) );
		add_action( 'init',           array( $this, 'action_init' ) );

		# Filters
		add_filter( 'pre_update_option_active_plugins',               array( $this, 'filter_active_plugins' ) );
		add_filter( 'pre_update_site_option_active_sitewide_plugins', array( $this, 'filter_active_sitewide_plugins' ) );

		# [Dea|A]ctivation
		register_activation_hook(   __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

		# Parent setup:
		parent::__construct( $file );

		# Load and register built-in collectors:
		foreach ( glob( $this->plugin_path( 'collectors/*.php' ) ) as $file ) {
			include $file;
		}

	}

	public function action_plugins_loaded() {

		# Register additional collectors:
		foreach ( apply_filters( 'qm/collectors', array(), $this ) as $collector ) {
			QM_Collectors::add( $collector );
		}

		# Load dispatchers:
		foreach ( glob( $this->plugin_path( 'dispatchers/*.php' ) ) as $file ) {
			include $file;
		}

		# Register built-in and additional dispatchers:
		foreach ( apply_filters( 'qm/dispatchers', array(), $this ) as $dispatcher ) {
			QM_Dispatchers::add( $dispatcher );
		}

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
		if ( class_exists( 'QM_DB' ) ) {
			unlink( WP_CONTENT_DIR . '/db.php' );
		}

	}

	public function action_init() {

		load_plugin_textdomain( 'query-monitor', false, dirname( $this->plugin_base() ) . '/languages' );

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
		trigger_error( sprintf(
			esc_html__( 'The symlink at %s is no longer pointing to the correct location. Please remove the symlink, then deactivate and reactivate Query Monitor.', 'query-monitor' ),
			'<code>' . esc_html( $db ) . '</code>'
		), E_USER_WARNING );
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
