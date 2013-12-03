<?php
/*
Plugin Name: Query Monitor
Description: Monitoring of database queries, hooks, conditionals and more.
Version:     2.5.6
Plugin URI:  https://github.com/johnbillion/QueryMonitor
Author:      John Blackbourn
Author URI:  https://johnblackbourn.com/
Text Domain: query-monitor
Domain Path: /languages/
License:     GPL v2 or later

Copyright 2013 John Blackbourn

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

# No autoloaders for us. See https://github.com/johnbillion/QueryMonitor/issues/7
foreach ( array( 'Backtrace', 'Component', 'Plugin', 'Util' ) as $f )
	require_once dirname( __FILE__ ) . "/{$f}.php";

class QueryMonitor extends QM_Plugin {

	protected $components = array();
	protected $did_footer = false;

	public function __construct( $file ) {

		# Actions
		add_action( 'init',           array( $this, 'action_init' ) );
		add_action( 'admin_footer',   array( $this, 'action_footer' ), 999 );
		add_action( 'wp_footer',      array( $this, 'action_footer' ), 999 );
		add_action( 'login_footer',   array( $this, 'action_footer' ), 999 );
		add_action( 'admin_bar_menu', array( $this, 'action_admin_bar_menu' ), 999 );
		add_action( 'shutdown',       array( $this, 'action_shutdown' ), 0 );

		# Filters
		add_filter( 'pre_update_option_active_plugins',               array( $this, 'filter_active_plugins' ) );
		add_filter( 'pre_update_site_option_active_sitewide_plugins', array( $this, 'filter_active_sitewide_plugins' ) );

		# [Dea|A]ctivation
		register_activation_hook(   __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

		# Parent setup:
		parent::__construct( $file );

		foreach ( glob( $this->plugin_path( 'components/*.php' ) ) as $component )
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

		if ( ! file_exists( $db = WP_CONTENT_DIR . '/db.php' ) )
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
		$title = implode( ' &nbsp; ', apply_filters( 'query_monitor_title', array() ) );

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

		if ( !did_action( 'plugins_loaded' ) )
			return false;

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

		$this->did_footer = true;

	}

	public function action_shutdown() {

		if ( !$this->show_query_monitor() )
			return;

		if ( QM_Util::is_ajax() )
			$this->output_ajax();
		else if ( $this->did_footer )
			$this->output_footer();

	}

	public function action_init() {

		global $wp_locale;

		load_plugin_textdomain( 'query-monitor', false, dirname( $this->plugin_base() ) . '/languages' );

		if ( !$this->show_query_monitor() )
			return;

		if ( QM_Util::is_ajax() )
			ob_start();

		# @todo move into output_html
		if ( !defined( 'DONOTCACHEPAGE' ) )
			define( 'DONOTCACHEPAGE', 1 );

		wp_enqueue_style(
			'query-monitor',
			$this->plugin_url( 'assets/query-monitor.css' ),
			null,
			$this->plugin_ver( 'assets/query-monitor.css' )
		);
		wp_enqueue_script(
			'query-monitor',
			$this->plugin_url( 'assets/query-monitor.js' ),
			array( 'jquery' ),
			$this->plugin_ver( 'assets/query-monitor.js' ),
			true
		);
		wp_localize_script(
			'query-monitor',
			'qm_locale',
			(array) $wp_locale
		);
		wp_localize_script(
			'query-monitor',
			'qm_l10n',
			array(
				'ajax_error' => __( 'PHP Error in AJAX Response', 'query-monitor' ),
			)
		);

	}

	public function output_footer() {

		# @TODO document why this is needed
		# Flush the output buffer to avoid crashes
		if ( !is_feed() ) {
			while ( ob_get_length() )
				ob_end_flush();
		}

		foreach ( $this->get_components() as $component )
			$component->process();

		if ( !function_exists( 'is_admin_bar_showing' ) or !is_admin_bar_showing() )
			$class = 'qm-show';
		else
			$class = '';

		$qm = array(
			'menu'        => $this->js_admin_bar_menu(),
			'ajax_errors' => array() # @TODO move this into the php_errors component
		);

		echo '<script type="text/javascript">' . "\n\n";
		echo 'var qm = ' . json_encode( $qm ) . ';' . "\n\n";
		echo '</script>' . "\n\n";

		echo '<div id="qm" class="' . $class . '">';
		echo '<div id="qm-wrapper">';
		echo '<p>' . __( 'Query Monitor', 'query-monitor' ) . '</p>';

		foreach ( $this->get_components() as $component ) {
			$component->output_html( array(
				'id' => $component->id()
			), $component->get_data() );
		}

		echo '</div>';
		echo '</div>';

	}

	public function output_ajax() {

		# if the headers have already been sent then we can't do anything about it
		if ( headers_sent() )
			return;

		foreach ( $this->get_components() as $component )
			$component->process();

		foreach ( $this->get_components() as $component ) {
			$component->output_headers( array(
				'id' => $component->id()
			), $component->get_data() );
		}

		# flush once, because we're nice
		if ( ob_get_length() )
			ob_flush();

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

}

$GLOBALS['querymonitor'] = new QueryMonitor( __FILE__ );
