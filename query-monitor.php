<?php
/*
Plugin Name: Query Monitor
Description: Monitoring of database queries, hooks, conditionals and much more.
Version:     2.0.3
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

if ( !defined( 'SAVEQUERIES' ) )
	define( 'SAVEQUERIES', true );
if ( !defined( 'QM_EXPENSIVE' ) )
	define( 'QM_EXPENSIVE', 0.05 );
if ( !defined( 'QM_LONG' ) )
	define( 'QM_LONG', 1000 );
if ( !defined( 'QM_DISPLAY_LIMIT' ) )
	define( 'QM_DISPLAY_LIMIT', 200 );

class QueryMonitor {

	var $times        = array();
	var $trans        = array();
	var $http         = array();
	var $templates    = array();
	var $db_objects   = array();
	var $overview     = array();
	var $conds        = array();
	var $paths        = array();
	var $php_errors   = array();
	var $body_class   = array();
	var $admin        = array();
	var $qvars        = array();
	var $plugin_qvars = array();
	var $screen       = '';
	var $db_errors    = 0;
	var $is_multisite = false;

	function __construct() {

		if ( !SAVEQUERIES )
			return;

		add_action( 'init',                   array( $this, 'register_style' ) );
		add_action( 'wp_footer',              array( $this, 'register_output' ), 99 );
		add_action( 'admin_footer',           array( $this, 'register_output' ), 99 );
		add_action( 'setted_site_transient',  array( $this, 'setted_site_transient' ) );
		add_action( 'setted_transient',       array( $this, 'setted_blog_transient' ) );
		add_action( 'http_api_debug',         array( $this, 'http_debug' ), 99, 5 );
		add_action( 'plugins_loaded',         array( $this, 'setup' ) );

		add_filter( 'http_request_args',      array( $this, 'http_request' ), 99, 2 );
		add_filter( 'http_response',          array( $this, 'http_response' ), 99, 3 );
		add_filter( 'body_class',             array( $this, 'body_class' ), 99 );
		add_filter( 'current_screen',         array( $this, 'current_screen' ), 99 );

		register_activation_hook( __FILE__,   array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
		set_error_handler(                    array( $this, 'error_handler' ) );

		$this->is_multisite = ( function_exists( 'is_multisite' ) and is_multisite() );

	}

	function current_screen( $screen ) {
		if ( empty( $this->admin ) )
			$this->admin = wp_clone( $screen );
		return $screen;
	}

	function error_handler( $type, $message, $file = null, $line = null ) {

		switch ( $type ) {

			case E_WARNING:
			case E_USER_WARNING:
				$type = 'warning';
				break;

			case E_NOTICE:
			case E_USER_NOTICE:
				$type = 'notice';
				break;

			default:
				return false;
				break;

		}

		if ( error_reporting() > 0 ) {

			$funcs = $this->backtrace();

			if ( !isset( $funcs[0] ) )
				$funcs[0] = '';

			$key = md5( $message . $file . $line . $funcs[0] );

			if ( isset( $this->php_errors[$type][$key] ) ) {
				$this->php_errors[$type][$key]->calls++;
			} else {
				$this->php_errors[$type][$key] = (object) array(
					'type'    => $type,
					'message' => $message,
					'file'    => $file,
					'line'    => $line,
					'funcs'   => $funcs,
					'calls'   => 1
				);
			}

		}

		return true;

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
		if ( $this->is_multisite )
			return false;
		else
			return get_role( 'administrator' );
	}

	function body_class( $class ) {
		$this->body_class = $class;
		return $class;
	}

	function setup() {
		if ( !defined( 'QUERY_MONITOR_COOKIE' ) )
			define( 'QUERY_MONITOR_COOKIE', 'query_monitor_' . COOKIEHASH );
	}

	function admin_bar_menu() {

		global $wp_admin_bar, $template;

		if ( !is_admin_bar_showing() )
			return;

		if ( isset( $this->php_errors['warning'] ) )
			$link_class = 'qm-warning';
		else if ( isset( $this->php_errors['notice'] ) )
			$link_class = 'qm-notice';
		else
			$link_class = '';

		$template_file = apply_filters( 'query_monitor_template', basename( $template ) );

		if ( empty( $this->db_errors ) ) {
			$title = sprintf(
				_n( '%1$s<small>S</small> / %2$s<small>Q</small>', '%1$s<small>S</small> / %2$s<small>Q</small>', $this->overview['query_num'], 'query_monitor' ),
				$this->overview['load_time'],
				$this->overview['query_num']
			);
		} else {
			$link_class = 'qm-error';
			$title = sprintf(
				_n( '%1$s<small>S</small> / %2$s<small>Q</small> (%3$d error)', '%1$s<small>S</small> / %2$s<small>Q</small> (%3$d errors)', $this->db_errors, 'query_monitor' ),
				$this->overview['load_time'],
				$this->overview['query_num'],
				$this->db_errors
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

		if ( !empty( $this->db_errors ) ) {
			$wp_admin_bar->add_menu( array(
				'parent' => 'query_monitor',
				'id'     => 'query_monitor_errors',
				'title'  => sprintf( __( 'SQL Errors (%s)', 'query_monitor' ), $this->db_errors ),
				'href'   => '#qm-overview'
			) );
		}

		if ( isset( $this->php_errors['warning'] ) ) {
			$wp_admin_bar->add_menu( array(
				'parent' => 'query_monitor',
				'id'     => 'query_monitor_warnings',
				'title'  => sprintf( __( 'PHP Warnings (%s)', 'query_monitor' ), count( $this->php_errors['warning'] ) ),
				'href'   => '#qm-errors'
			) );
		}

		if ( isset( $this->php_errors['notice'] ) ) {
			$wp_admin_bar->add_menu( array(
				'parent' => 'query_monitor',
				'id'     => 'query_monitor_notices',
				'title'  => sprintf( __( 'PHP Notices (%s)', 'query_monitor' ), count( $this->php_errors['notice'] ) ),
				'href'   => '#qm-errors'
			) );
		}

		$wp_admin_bar->add_menu( array(
			'parent' => 'query_monitor',
			'id'     => 'query_monitor_funcs',
			'title'  => __( 'Functions', 'query_monitor' ),
			'href'   => '#qm-funcs'
		) );

		$trans_title = ( empty( $this->trans ) )
			? __( 'Transients Set', 'query_monitor' )
			: _n( 'Transients Set (%d)', 'Transients Set (%d)', count( $this->trans ), 'query_monitor' );

		$wp_admin_bar->add_menu( array(
			'parent' => 'query_monitor',
			'id'     => 'query_monitor_trans',
			'title'  => sprintf( $trans_title, number_format_i18n( count( $this->trans ) ) ),
			'href'   => '#qm-trans'
		) );

		$http_title = ( empty( $this->http ) )
			? __( 'HTTP Requests', 'query_monitor' )
			: _n( 'HTTP Requests (%d)', 'HTTP Requests (%d)', count( $this->http ), 'query_monitor' );

		$wp_admin_bar->add_menu( array(
			'parent' => 'query_monitor',
			'id'     => 'query_monitor_http',
			'title'  => sprintf( $http_title, number_format_i18n( count( $this->http ) ) ),
			'href'   => '#qm-http'
		) );

		$wp_admin_bar->add_menu( array(
			'parent' => 'query_monitor',
			'id'     => 'query_monitor_hooks',
			'title'  => __( 'Hooks', 'query_monitor' ),
			'href'   => '#qm-hooks'
		) );

		$vars_title = ( empty( $this->plugin_qvars ) )
			? __( 'Query Vars', 'query_monitor' )
			: _n( 'Query Vars (+%d)', 'Query Vars (+%d)', count( $this->plugin_qvars ), 'query_monitor' );

		$wp_admin_bar->add_menu( array(
			'parent' => 'query_monitor',
			'id'     => 'query_monitor_qvars',
			'title'  => sprintf( $vars_title, number_format_i18n( count( $this->plugin_qvars ) ) ),
			'href'   => '#qm-qvars'
		) );

		$wp_admin_bar->add_menu( array(
			'parent' => 'query_monitor',
			'id'     => 'query_monitor_env',
			'title'  => __( 'Environment', 'query_monitor' ),
			'href'   => '#qm-env'
		) );

		if ( is_admin() ) {

			$wp_admin_bar->add_menu( array(
				'parent' => 'query_monitor',
				'id'     => 'query_monitor_admin',
				'title'  => sprintf( __( 'Admin Screen: %s', 'query_monitor' ), $this->screen ),
				'href'   => '#qm-admin'
			) );

		} else {

			$wp_admin_bar->add_menu( array(
				'parent' => 'query_monitor',
				'id'     => 'query_monitor_theme',
				'title'  => sprintf( __( 'Template: %s', 'query_monitor' ), $template_file ),
				'href'   => '#qm-theme'
			) );

			foreach ( $this->conds['true'] as $cond ) {
				$wp_admin_bar->add_menu( array(
					'parent' => 'query_monitor',
					'id'     => 'query_monitor_' . $cond,
					'title'  => $cond . '()',
					'href'   => '#qm-conds',
					'meta'   => array(
						'class' => 'qm-true'
					)
				) );
			}

		}

	}

	function setted_site_transient( $transient ) {
		$this->setted_transient( $transient, 'site' );
	}

	function setted_blog_transient( $transient ) {
		$this->setted_transient( $transient, 'blog' );
	}

	function setted_transient( $transient, $type ) {
		$this->trans[] = array(
			'transient' => $transient,
			'trace'     => $this->backtrace(),
			'type'      => $type
		);
	}

	function create_nonce( $action ) {
		# This is just WordPress' nonce implementation minus the user ID
		# check so a nonce can be set in a cookie and used cross-user
		$i = wp_nonce_tick();
		return substr( wp_hash( $i . $action, 'nonce' ), -12, 10 );
	}

	function verify_nonce( $nonce, $action ) {

		$i = wp_nonce_tick();

		if ( substr( wp_hash( $i . $action, 'nonce' ), -12, 10 ) == $nonce )
			return true;
		if ( substr( wp_hash( ( $i - 1 ) . $action, 'nonce' ), -12, 10 ) == $nonce )
			return true;

		return false;

	}

	function show_query_monitor() {

		if ( $this->is_multisite ) {
			if ( current_user_can( 'manage_network_options' ) )
				return true;
		} else if ( current_user_can( 'view_query_monitor' ) ) {
			return true;
		}

		if ( isset( $_COOKIE[QUERY_MONITOR_COOKIE] ) )
			return $this->verify_nonce( $_COOKIE[QUERY_MONITOR_COOKIE], 'view_query_monitor' );

		return false;

	}

	function process_screen() {

		global $current_screen, $pagenow;

		if ( is_admin() ) {

			if ( !isset( $current_screen ) or empty( $current_screen ) ) {

				# Pre-3.0 compat:
				if ( isset( $_GET['page'] ) ) {

					$plugin_page = plugin_basename( stripslashes( $_GET['page'] ) );

					if ( isset( $plugin_page ) ) {
						if ( !$page_hook = get_plugin_page_hook( $plugin_page, $pagenow ) )
							$page_hook = get_plugin_page_hook( $plugin_page, $plugin_page );
						if ( !$page_hook )
							$page_hook = $plugin_page;
					}

				} else {
					$page_hook = $pagenow;
				}

				$this->screen = $page_hook;

			} else {
				if ( isset( $_GET['page'] ) )
					$this->screen = $current_screen->base;
				else
					$this->screen = $pagenow;
			}

		} else {
			$this->screen = null;
		}

	}

	function process_qvars() {

		$query_vars = array_filter( $GLOBALS['wp_query']->query_vars );
		$plugin_qvars = apply_filters( 'query_vars', array() );

		ksort( $query_vars );

		# Array query vars in < 3.0 get smushed to the string 'Array'
		foreach ( $query_vars as $key => $var ) {
			if ( 'Array' === $var ) {
				$query_vars[$key] = 'Array (<span class="qm-warn">!</span>)';
			}
		}

		# First add plugin vars to $this->qvars:
		foreach ( $query_vars as $k => $v ) {
			if ( in_array( $k, $plugin_qvars ) ) {
				$this->qvars[$k] = $v;
				$this->plugin_qvars[] = $k;
			}
		}

		# Now add all other vars to $this->qvars:
		foreach ( $query_vars as $k => $v ) {
			if ( !in_array( $k, $plugin_qvars ) )
				$this->qvars[$k] = $v;
		}

	}

	function register_output() {

		global $wpdb;

		if ( !$this->show_query_monitor() )
			return;

		$this->process_time();
		$this->process_memory();
		$this->process_screen();
		$this->process_conds();
		$this->process_qvars();

		# We're just using $wpdb for now, support for all WPDB objects to come

		$query_num = $query_time = 0;

		foreach ( (array) $wpdb->queries as $query ) {
			if ( strpos( $query[2], 'wp_admin_bar' ) )
				continue;
			$query_num++;
			$query_time += $query[1];
			if ( isset( $query[3] ) and is_wp_error( $query[3] ) )
				$this->db_errors++;
		}

		$this->overview = array(
			'query_num'  => $query_num,
			'query_time' => number_format_i18n( $query_time, 4 ),
			'load_time'  => number_format_i18n( $this->load_time, 2 ),
			'memory'     => number_format_i18n( $this->memory / 1000 )
		);

		add_action( 'shutdown',       array( $this, 'output' ), 0 );
		add_action( 'admin_bar_menu', array( $this, 'admin_bar_menu' ), 99 );

	}

	function register_style() {

		if ( !$this->show_query_monitor() )
			return;

		wp_enqueue_style(
			'query_monitor',
			plugin_dir_url( __FILE__ ) . 'query-monitor.css',
			null,
			filemtime( plugin_dir_path( __FILE__ ) . 'query-monitor.css' )
		);

	}

	function http_request( $args, $url ) {
		$m_start = microtime( true );
		$key = $m_start;
		$this->http[$key] = array(
			'url'   => $url,
			'args'  => $args,
			'start' => $m_start,
			'trace' => $this->backtrace()
		);
		$args['_qm_key'] = $key;
		return $args;
	}

	function http_debug( $param, $action ) {

		switch ( $action ) {

			case 'response':

				$fga = func_get_args();

				list( $response, $action, $class ) = $fga;

				# http://core.trac.wordpress.org/ticket/18732
				if ( isset( $fga[3] ) )
					$args = $fga[3];
				if ( isset( $fga[4] ) )
					$url = $fga[4];
				if ( !isset( $args['_qm_key'] ) )
					return;

				$this->http[$args['_qm_key']]['transport'] = str_replace( 'wp_http_', '', strtolower( $class ) );

				if ( is_wp_error( $response ) )
					$this->http_response( $response, $args, $url );

				break;

			case 'transports_list':
				# Nothing
				break;

		}

	}

	function http_response( $response, $args, $url ) {
		$this->http[$args['_qm_key']]['end']      = microtime( true );
		$this->http[$args['_qm_key']]['response'] = $response;
		return $response;
	}

	function is_allowed_object( $var ) {
		$ignore = array(
			'domain_map'
		);
		foreach ( $ignore as $obj ) {
			if ( $var instanceof $obj )
				return false;
		}
		return is_object( $var );
	}

	function is_db_object( $var ) {
		$objs = array(
			'wpdb',
			'dbrc_wpdb'
		);
		foreach ( $objs as $obj ) {
			if ( $var instanceof $obj )
				return true;
		}
		return false;
	}

	function output() {

		# Flush the output buffer to avoid crashes
		if ( ob_get_length() )
			ob_flush();

		$this->db_objects = array();

		foreach ( $GLOBALS as $key => $value ) {

			if ( $this->is_db_object( $value ) ) {
				$this->db_objects[$key] = $value;
			} else if ( $this->is_allowed_object( $value ) ) {
				foreach ( $value as $k => $v ) {
					if ( $this->is_db_object( $v ) )
						$this->db_objects["{$key}->{$k}"] = $v;
				}
			}

		}

		$this->output_start();
		$this->output_overview();

		foreach ( (array) apply_filters( 'query_monitor_db_objects', $this->db_objects ) as $name => $object ) {
			if ( $this->is_db_object( $object ) )
				$this->output_queries( $object, $name );
		}

		$this->output_funcs();
		$this->output_conds();
		$this->output_admin();
		$this->output_trans();
		$this->output_https();
		$this->output_hooks();
		$this->output_qvars();
		$this->output_theme();
		$this->output_envir();
		$this->output_error();
		$this->output_nonce();
		$this->output_close();

	}

	function output_start() {
		echo '<div id="qm">';
		echo '<p>Query Monitor</p>'; # Plugin name, no localisation
	}

	function output_close() {
		echo '</div>';
	}

	function output_overview() {

		$http_time = 0;

		# @TODO this should go into a process_*() function:
		foreach ( $this->http as $row ) {
			if ( isset( $row['response'] ) )
				$http_time += ( $row['end'] - $row['start'] );
			else
				$http_time += $row['args']['timeout'];
		}

		$total_stime = number_format_i18n( $this->load_time, 4 );
		$total_ltime = number_format_i18n( $this->load_time, 10 );
		$excl_stime  = number_format_i18n( $this->load_time - $http_time, 4 );
		$excl_ltime  = number_format_i18n( $this->load_time - $http_time, 10 );

		if ( empty( $http_time ) )
			$timespan = 1;
		else
			$timespan = 2;

		echo '<table class="qm" cellspacing="0" id="qm-overview">';
		echo '<tbody>';

		echo '<tr>';
		echo '<td>' . __( 'Peak memory usage', 'query_monitor' ) . '</td>';
		echo '<td title="' . esc_attr( sprintf( __( '%s bytes', 'query_monitor' ), number_format_i18n( $this->memory ) ) ) . '">' . sprintf( __( '%s kB', 'query_monitor' ), number_format_i18n( $this->memory / 1000 ) ) . '</td>';
		echo '</tr>';

		echo '<tr>';
		echo '<td rowspan=" ' . $timespan . '">' . __( 'Page generation time', 'query_monitor' ) . '</td>';
		echo "<td title='{$total_ltime}'>{$total_stime}</td>";
		echo '</tr>';

		if ( !empty( $http_time ) ) {
			echo '<tr>';
			echo "<td title='{$excl_ltime}'>" . sprintf( __( '%s w/o HTTP requests', 'query_monitor' ), $excl_stime ) . "</td>";
			echo '</tr>';
		}

		echo '</tbody>';
		echo '</table>';

	}

	function process_memory() {
		if ( function_exists( 'memory_get_peak_usage' ) )
			$this->memory = memory_get_peak_usage();
		else
			$this->memory = memory_get_usage();
		return $this->memory;
	}

	function process_time() {
		return $this->load_time = $this->timer_stop();
	}

	function output_admin() {

		global $current_screen, $typenow, $pagenow;

		if ( !is_admin() )
			return;

		$post_type_warning = '';

		echo '<table class="qm" cellspacing="0" id="qm-admin">';
		echo '<thead>';
		echo '<tr>';
		echo '<th colspan="3">' . __( 'Admin', 'query_monitor' ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		echo '<tr>';
		echo '<td rowspan="3">' . __( 'Variables', 'query_monitor' ) . '</td>';
		echo '<td class="qm-ltr">$current_screen</td>';
		echo '<td>';

		if ( is_object( $this->admin ) ) {
			echo '<table class="qm-inner" cellspacing="0">';
			echo '<tbody>';
			foreach ( $this->admin as $key => $value ) {
				echo '<tr>';
				echo "<td class='qm-var'>{$key}:</td>";
				echo '<td>';
				echo $value;
				if ( !empty( $value ) and ( $current_screen->$key != $value ) )
					echo $post_type_warning = '&nbsp;(<a href="http://core.trac.wordpress.org/ticket/14886" class="qm-warn" title="' . esc_attr__( 'This value may not be as expected. Please see WordPress bug #14886.', 'query_monitor' ) . '" target="_blank">!</a>)';
				echo '</td>';
				echo '</tr>';
			}
			echo '</tbody>';
			echo '</table>';
		} else {
			echo $this->admin;
		}

		echo '</td>';
		echo '</tr>';

		echo '<tr>';
		echo '<td class="qm-ltr">$pagenow</td>';
		echo "<td>{$pagenow}</td>";
		echo '</tr>';

		echo '<tr>';
		echo '<td class="qm-ltr">$typenow</td>';
		echo "<td>{$typenow} {$post_type_warning}</td>";
		echo '</tr>';

		if ( in_array( $current_screen->base, array( 'edit', 'edit-comments', 'edit-tags', 'link-manager', 'plugins', 'plugins-network', 'sites-network', 'themes-network', 'upload', 'users', 'users-network' ) ) ) {

			# And now, WordPress' legendary inconsistency comes into play:

			if ( !empty( $current_screen->taxonomy ) )
				$col = $current_screen->taxonomy;
			else if ( !empty( $current_screen->post_type ) )
				$col = $current_screen->post_type . '_posts';
			else
				$col = $current_screen->base;

			if ( !empty( $current_screen->post_type ) )
				$cols = $current_screen->post_type . '_posts';
			else
				$cols = $current_screen->id;

			if ( 'edit-comments' == $col )
				$col = 'comments';
			else if ( 'upload' == $col )
				$col = 'media';
			else if ( 'link-manager' == $col )
				$col = 'link';

			echo '<tr>';
			echo '<td rowspan="3">' . __( 'Columns', 'query_monitor' ) . '</td>';
			echo "<td colspan='2'>manage_<span class='qm-current'>{$cols}</span>_columns</td>";
			echo '</tr>';
			echo '<tr>';
			echo "<td colspan='2'>manage_<span class='qm-current'>{$col}</span>_custom_column</td>";
			echo '</tr>';
			echo '<tr>';
			echo "<td colspan='2'>manage_<span class='qm-current'>{$current_screen->id}</span>_sortable_columns</td>";
			echo '</tr>';

		}

		echo '</tbody>';
		echo '</table>';

	}

	function output_envir() {

		global $wpdb;

		# We could attempt to calculate optimal values here but there are just too many factors to consider.

		$vars = array(
			'key_buffer_size'    => true,  # Key cache limit
			'max_allowed_packet' => false, # Max individual query size
			'max_connections'    => false, # Max client connections
			'query_cache_limit'  => true,  # Individual query cache limit
			'query_cache_size'   => true,  # Query cache limit
			'query_cache_type'   => 'ON'   # Query cache on or off
		);

		$version = $wpdb->get_row( "
			SHOW VARIABLES
			WHERE Variable_name = 'version'
		" );
		$variables = $wpdb->get_results( "
			SHOW VARIABLES
			WHERE Variable_name IN ( '" . implode( "', '", array_keys( $vars ) ) . "' )
		" );

		echo '<table class="qm" cellspacing="0" id="qm-env">';
		echo '<thead>';
		echo '<tr>';
		echo '<th colspan="3">' . __( 'Environment', 'query_monitor' ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		if ( function_exists( 'posix_getpwuid' ) ) {

			$u = posix_getpwuid( posix_getuid() );
			$g = posix_getgrgid( $u['gid'] );
			$php_u = esc_html( $u['name'] . ':' . $g['name'] );

		} else if ( function_exists( 'exec' ) ) {

			$php_u = esc_html( exec( 'whoami' ) );

		} else {

			$php_u = '<em>' . __( 'Unknown', 'query_monitor' ) . '</em>';

		}

		echo '<tr>';
		echo '<td rowspan="2">PHP</td>';
		echo '<td>version</td>';
		echo '<td>' . phpversion() . '</td>';
		echo '</tr>';
		echo '<tr>';
		echo '<td>user</td>';
		echo "<td>{$php_u}</td>";
		echo '</tr>';

		echo '<tr>';
		echo '<td rowspan="' . ( 2 + count( $variables ) ) . '">MySQL</td>';
		echo '<td>version</td>';
		echo '<td>' . $version->Value . '</td>';
		echo '</tr>';

		echo '<tr>';
		echo '<td>user</td>';
		echo '<td>' . DB_USER . '</td>';
		echo '</tr>';

		echo '<tr>';

		$first  = true;
		$warn   = __( "This value is not optimal. Check the recommended setting for '%s'.", 'query_monitor' );
		$search = __( 'http://www.google.com/search?q=mysql+performance+%s', 'query_monitor' );

		foreach ( $variables as $setting ) {

			$key = $setting->Variable_name;
			$val = $setting->Value;
			$prepend = '';
			$warning = '&nbsp;(<a class="qm-warn" href="' . esc_url( sprintf( $search, $key ) ) . '" target="_blank" title="' . esc_attr( sprintf( $warn, $key ) ) . '">!</a>)';

			if ( ( true === $vars[$key] ) and empty( $val ) )
				$prepend .= $warning;
			else if ( is_string( $vars[$key] ) and ( $val !== $vars[$key] ) )
				$prepend .= $warning;

			if ( is_numeric( $val ) and ( $val >= 1024 ) )
				$prepend .= '<br /><span class="qm-info">~' . size_format( $val ) . '</span>';

			if ( !$first )
				echo '<tr>';

			$key = esc_html( $key );
			$val = esc_html( $val );

			echo "<td>{$key}</td>";
			echo "<td>{$val}{$prepend}</td>";

			echo '</tr>';

			$first = false;

		}

		$wp_debug = ( WP_DEBUG ) ? 'ON' : 'OFF';
		$wp_span = 2;

		if ( $this->is_multisite )
			$wp_span++;

		echo '<tr>';
		echo '<td rowspan="' . $wp_span . '">WP</td>';
		echo '<td>version</td>';
		echo "<td>{$GLOBALS['wp_version']}</td>";
		echo '</tr>';

		if ( $this->is_multisite ) {
			echo '<tr>';
			echo '<td>blog_id</td>';
			echo "<td>{$GLOBALS['blog_id']}</td>";
			echo '</tr>';
		}

		echo '<tr>';
		echo '<td>WP_DEBUG</td>';
		echo "<td>{$wp_debug}</td>";
		echo '</tr>';

		echo '</tbody>';
		echo '</table>';

	}

	function output_error() {

		if ( empty( $this->php_errors ) )
			return;

		echo '<table class="qm" cellspacing="0" id="qm-errors">';
		echo '<thead>';
		echo '<tr>';
		echo '<th colspan="2">' . __( 'PHP Error', 'query_monitor' ) . '</th>';
		echo '<th>' . __( 'File', 'query_monitor' ) . '</th>';
		echo '<th>' . __( 'Line', 'query_monitor' ) . '</th>';
		echo '<th>' . __( 'Function', 'query_monitor' ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		$types = array(
			'warning' => __( 'Warning', 'query_monitor' ),
			'notice'  => __( 'Notice', 'query_monitor' )
		);

		foreach ( $types as $type => $title ) {

			if ( isset( $this->php_errors[$type] ) ) {

				echo '<tr>';
				echo '<td rowspan="' . count( $this->php_errors[$type] ) . '">' . $title . '</td>';
				$first = true;

				foreach ( $this->php_errors[$type] as $error ) {

					if ( !$first )
						echo '<tr>';

					$funca = implode( ', ', array_reverse( $error->funcs ) );

					echo '<td>' . esc_html( $error->message ) . '</td>';
					echo '<td>' . esc_html( $error->file ) . '</td>';
					echo '<td>' . esc_html( $error->line ) . '</td>';
					echo '<td title="' . esc_attr( $funca ) . '" class="qm-ltr">' . esc_html( $error->funcs[0] ) . '</td>';
					echo '</tr>';

					$first = false;

				}

			}

		}

		echo '</tbody>';
		echo '</table>';

	}

	function output_nonce() {

		echo '<table class="qm" cellspacing="0" id="qm-auth">';
		echo '<thead>';
		echo '<tr>';
		echo '<th>' . __( 'Authentication', 'query_monitor' ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		$name   = QUERY_MONITOR_COOKIE;
		$domain = COOKIE_DOMAIN;
		$path   = COOKIEPATH;
		$value  = $this->create_nonce( 'view_query_monitor' );

		if ( !isset( $_COOKIE[$name] ) ) {

			$text = esc_js( __( 'Authentication cookie set. You can now view Query Monitor output while logged out or while logged in as a different user.', 'query_monitor' ) );
			$link = "document.cookie='{$name}={$value}; domain={$domain}; path={$path}'; alert('{$text}'); return false;";

			echo '<tr>';
			echo '<td>' . __( 'You can set an authentication cookie which allows you to view Query Monitor output when you&rsquo;re not logged in.', 'query_monitor' ) . '</td>';
			echo '</tr>';
			echo '<tr>';
			echo '<td><a href="#" onclick="' . $link . '">' . __( 'Set authentication cookie', 'query_monitor' ) . '</a></td>';
			echo '</tr>';

		} else {

			$text = esc_js( __( 'Authentication cookie cleared.', 'query_monitor' ) );
			$link = "document.cookie='{$name}=; expires=' + new Date(0).toUTCString() + '; domain={$domain}; path={$path}'; alert('{$text}'); return false;";

			echo '<tr>';
			echo '<td>' . __( 'You currently have an authentication cookie which allows you to view Query Monitor output.', 'query_monitor' ) . '</td>';
			echo '</tr>';
			echo '<tr>';
			echo '<td><a href="#" onclick="' . $link . '">' . __( 'Clear authentication cookie', 'query_monitor' ) . '</a></td>';
			echo '</tr>';

		}

		echo '</tbody>';
		echo '</table>';

	}

	function output_trans() {

		echo '<table class="qm" cellspacing="0" id="qm-trans">';
		echo '<thead>';
		echo '<tr>';
		echo '<th>' . __( 'Transient Set', 'query_monitor' ) . '</th>';
		if ( $this->is_multisite )
			echo '<th>' . __( 'Type', 'query_monitor' ) . '</th>';
		echo '<th>' . __( 'Function', 'query_monitor' ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		if ( !empty( $this->trans ) ) {

			foreach ( $this->trans as $row ) {
				unset( $row['trace'][0], $row['trace'][1] );
				$func = $row['trace'][2];
				$transient = str_replace( array(
					'_site_transient_',
					'_transient_'
				), '', $row['transient'] );
				$funcs = esc_attr( implode( ', ', array_reverse( $row['trace'] ) ) );
				$type = ( $this->is_multisite ) ? "<td valign='top'>{$row['type']}</td>\n" : '';
				echo "
					<tr>\n
						<td valign='top'>{$transient}</td>\n
						{$type}
						<td valign='top' title='{$funcs}' class='qm-ltr'>{$func}</td>\n
					</tr>\n
				";
			}

		} else {

			echo '<tr>';
			echo '<td colspan="3" style="text-align:center !important"><em>' . __( 'none', 'query_monitor' ) . '</em></td>';
			echo '</tr>';

		}

		echo '</tbody>';
		echo '</table>';

	}

	function output_https() {

		$total_time = 0;

		echo '<table class="qm" cellspacing="0" id="qm-http">';
		echo '<thead>';
		echo '<tr>';
		echo '<th>' . __( 'HTTP Request', 'query_monitor' ) . '</th>';
		echo '<th>' . __( 'Method', 'query_monitor' ) . '</th>';
		echo '<th>' . __( 'Response', 'query_monitor' ) . '</th>';
		echo '<th>' . __( 'Function', 'query_monitor' ) . '</th>';
		echo '<th>' . __( 'Timeout', 'query_monitor' ) . '</th>';
		echo '<th>' . __( 'Time', 'query_monitor' ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		if ( !empty( $this->http ) ) {

			foreach ( $this->http as $row ) {
				$funcs = array();

				if ( isset( $row['response'] ) ) {

					$ltime = ( $row['end'] - $row['start'] );
					$total_time += $ltime;
					$stime = number_format_i18n( $ltime, 4 );
					$ltime = number_format_i18n( $ltime, 10 );

					if ( is_wp_error( $row['response'] ) ) {
						$response = $row['response']->get_error_message();
						$css = 'qm-warn';
					} else {
						$response = wp_remote_retrieve_response_code( $row['response'] );
						$msg = wp_remote_retrieve_response_message( $row['response'] );
						if ( 200 === intval( $response ) )
							$response = esc_html( $response . ' ' . $msg );
						else if ( !empty( $response ) )
							$response = '<span class="qm-warn">' . esc_html( $response . ' ' . $msg ) . '</span>';
						else
							$response = __( 'n/a', 'query_monitor' );
						$css = '';
					}

				} else {

					$ltime = '';
					$total_time += $row['args']['timeout'];
					$stime = number_format_i18n( $row['args']['timeout'], 4 );
					$response = __( 'Request timed out', 'query_monitor' );
					$css = 'qm-warn';

				}

				$method = $row['args']['method'];
				if ( isset( $row['transport'] ) )
					$method .= '<br />' . sprintf( _x( '(using %s)', 'using HTTP transport', 'query_monitor' ), $row['transport'] );
				if ( !$row['args']['blocking'] )
					$method .= '<br />' . _x( '(non-blocking)', 'non-blocking HTTP transport', 'query_monitor' );
				$url = str_replace( array(
					'&',
					'?',
					'=',
				), array(
					'<br /><span>&nbsp;&amp;&nbsp;</span>',
					'<br /><span>&nbsp;?&nbsp;</span>',
					'<span>&nbsp;=&nbsp;</span>',
				), $row['url'] );
				unset( $row['trace'][0], $row['trace'][1], $row['trace'][2], $row['trace'][3] );
				$f = 4;
				$func = $row['trace'][$f];
				if ( 0 === strpos( $func, 'SimplePie' ) )
					$func = $row['trace'][++$f];
				if ( 0 === strpos( $func, 'fetch_feed' ) )
					$func = $row['trace'][++$f];
				$funcs = esc_attr( implode( ', ', array_reverse( $row['trace'] ) ) );
				echo "
					<tr class='{$css}'>\n
						<td valign='top' class='qm-url qm-ltr'>{$url}</td>\n
						<td valign='top'>{$method}</td>\n
						<td valign='top'>{$response}</td>\n
						<td valign='top' title='{$funcs}' class='qm-ltr'>{$func}</td>\n
						<td valign='top'>{$row['args']['timeout']}</td>\n
						<td valign='top' title='{$ltime}'>{$stime}</td>\n
					</tr>\n
				";
			}

			$total_stime = number_format_i18n( $total_time, 4 );
			$total_ltime = number_format_i18n( $total_time, 10 );

			echo '<tr>';
			echo '<td colspan="5">&nbsp;</td>';
			echo "<td title='{$total_ltime}'>{$total_stime}</td>";
			echo '</tr>';

		} else {

			echo '<tr>';
			echo '<td colspan="6" style="text-align:center !important"><em>' . __( 'none', 'query_monitor' ) . '</em></td>';
			echo '</tr>';
	
		}

		echo '</tbody>';
		echo '</table>';

	}

	function backtrace() {
		$trace = debug_backtrace( false );
		$trace = array_values( array_filter( array_map( array( $this, '_filter_trace' ), $trace ) ) );
		return $trace;
	}

	function _filter_trace( $trace ) {

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

	function output_queries( $dbc, $name ) {

		$rows         = array();
		$total_time   = 0;
		$total_qs     = 0;
		$ignored_time = 0;
		$count        = 0;
		$max_exceeded = false;
		$has_results  = false;

		if ( isset( $dbc->queries ) and !empty( $dbc->queries ) ) {

			foreach ( $dbc->queries as $index => $query ) {

				$ltime = $query[1];
				$funcs = $query[2];

				if ( isset( $query[3] ) ) {
					$result = $query[3];
					$has_results = true;
				} else {
					$result = null;
				}

				if ( strpos( $funcs, 'wp_admin_bar' ) ) {
					$ignored_time += $ltime;
				} else {
					$total_time += $ltime;
					$total_qs++;
				}

				if ( !empty( $funcs ) ) {
					$funca = array_reverse( explode( ', ', $funcs ) );
					$func = $funca[0];
				} else {
					$func = '<em class="qm-info">' . __( 'none', 'query_monitor' ) . '</em>';
				}

				if ( strpos( $funcs, 'wp_admin_bar' ) ) {
					if ( isset( $_REQUEST['qm_display_all'] ) )
						$this->add_time( $func, $ltime );
				} else {
					$this->add_time( $func, $ltime );
				}

				if ( ( $total_qs > QM_DISPLAY_LIMIT ) and !isset( $_REQUEST['qm_display_all'] ) ) {
					$max_exceeded = true;
					continue;
				}

				$sql = trim( str_replace( array( "\r\n", "\r", "\n", "\t" ), ' ', $query[0] ) );
				$sql = esc_html( $sql );

				foreach( array(
					'AND', 'DELETE', 'ELSE', 'END', 'FROM', 'GROUP', 'HAVING', 'INNER', 'INSERT', 'LIMIT',
					'ON', 'OR', 'ORDER', 'SELECT', 'SET', 'THEN', 'UPDATE', 'VALUES', 'WHEN', 'WHERE'
				) as $cmd )
					$sql = trim( str_replace( " $cmd ", "<br/>$cmd ", $sql ) );

				$rows[] = array(
					'func'   => $func,
					'funcs'  => $funcs,
					'sql'    => $sql,
					'ltime'  => $ltime,
					'result' => $result
				);

			}

		}

		$id = sanitize_title( $name );
		$span = 3;

		if ( $has_results )
			$span++;

		echo '<table class="qm qm-queries" cellspacing="0" id="qm-queries-' . $id . '">';
		echo '<thead>';
		echo '<tr>';
		echo '<th colspan="' . $span . '" class="qm-ltr">$' . $name . '</th>';
		echo '</tr>';

		if ( $max_exceeded ) {
			echo '<tr><td colspan="' . $span . '" class="qm-expensive">' . sprintf( __( '%1$s $%2$s queries were performed on this page load. Only the first %3$d are shown below. Total query time and cumulative function times should be accurate.', 'query_monitor' ), number_format_i18n( $total_qs ), $name, number_format_i18n( QM_DISPLAY_LIMIT ) ) . '</td></tr>';
		}

		echo '<tr>';
		echo '<th>' . __( 'Query', 'query_monitor' ) . '</th>';
		echo '<th>' . __( 'Function', 'query_monitor' ) . '</th>';

		if ( $has_results )
			echo '<th>' . __( 'Affected Rows', 'query_monitor' ) . '</th>';

		echo '<th>' . __( 'Time', 'query_monitor' ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		if ( isset( $_REQUEST['qm_sort'] ) and ( 'time' == $_REQUEST['qm_sort'] ) )
			usort( $rows, array( $this, '_sort' ) );

		if ( !empty( $rows ) ) {

			foreach ( $rows as $row ) {
				if ( strpos( $row['funcs'], 'wp_admin_bar' ) ) {
					if ( !isset( $_REQUEST['qm_display_all'] ) )
						continue;
					$row_class = 'qm-na';
				} else {
					$row_class = '';
					$count++;
				}
				$select = ( 0 === strpos( strtoupper( $row['sql'] ), 'SELECT' ) );
				$ql = strlen( $row['sql'] );
				$qs = size_format( $ql );
				$stime = number_format_i18n( $row['ltime'], 4 );
				$ltime = number_format_i18n( $row['ltime'], 10 );
				if ( $select and ( $ql > QM_LONG ) )
					$row['qs'] = "<br /><span class='qm-expensive'>({$qs})</span>";
				else
					$row['qs'] = '';
				$td = ( $row['ltime'] > QM_EXPENSIVE ) ? " class='qm-expensive'" : '';
				if ( !$select )
					$row['sql'] = "<span class='qm-nonselectsql'>{$row['sql']}</span>";

				if ( $has_results ) {
					if ( is_wp_error( $row['result'] ) ) {
						$r = $row['result']->get_error_message( 'qmdb_error' );
						$results = "<td valign='top'>{$r}</td>\n";
						$row_class = 'qm-warn';
					} else {
						$results = "<td valign='top'>{$row['result']}</td>\n";
					}
				} else {
					$results = '';
				}

				$funcs = esc_attr( $row['funcs'] );

				echo "
					<tr class='{$row_class}'>\n
						<td valign='top' class='qm-ltr'>{$row['sql']}{$row['qs']}</td>\n
						<td valign='top' class='qm-ltr' title='{$funcs}'>{$row['func']}</td>\n
						{$results}
						<td valign='top' title='{$ltime}'{$td}>{$stime}</td>\n
					</tr>\n
				";
			}

			$total_stime = number_format_i18n( $total_time, 4 );
			$total_ltime = number_format_i18n( $total_time, 10 );

			echo '<tr>';
			echo '<td valign="top" colspan="' . ( $span - 1 ) . '">' . sprintf( _n( '%s query', '%s queries', $total_qs ), number_format_i18n( $total_qs ) ) . '</td>';
			echo "<td valign='top' title='{$total_ltime}'>{$total_stime}</td>";
			echo '</tr>';

		} else {

			echo '<td colspan="' . $span . '" style="text-align:center !important"><em>' . __( 'none', 'query_monitor' ) . '</em></td>';

		}

		echo '</tbody>';
		echo '</table>';

	}

	function add_time( $func, $ltime ) {
		if ( !isset( $this->times[$func] ) ) {
			$this->times[$func] = array(
				'func'  => $func,
				'calls' => 0,
				'ltime' => 0
			);
		}

		$this->times[$func]['calls']++;
		$this->times[$func]['ltime'] += $ltime;
	}

	function output_funcs() {

		$total_time  = 0;
		$total_calls = 0;

		echo '<table class="qm" cellspacing="0" id="qm-funcs">';
		echo '<thead>';
		echo '<tr>';
		echo '<th>' . __( 'Query Function', 'query_monitor' ) . '</th>';
		echo '<th>' . __( 'Queries', 'query_monitor' ) . '</th>';
		echo '<th>' . __( 'Time', 'query_monitor' ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		if ( !empty( $this->times ) ) {

			usort( $this->times, array( $this, '_sort' ) );

			foreach ( $this->times as $func => $row ) {
				$total_time  += $row['ltime'];
				$total_calls += $row['calls'];
				$stime = number_format_i18n( $row['ltime'], 4 );
				$ltime = number_format_i18n( $row['ltime'], 10 );
				echo "
					<tr>\n
						<td valign='top' class='qm-ltr'>{$row['func']}</td>\n
						<td valign='top'>{$row['calls']}</td>\n
						<td valign='top' title='{$ltime}'>{$stime}</td>\n
					</tr>\n
				";
			}

			$total_stime = number_format_i18n( $total_time, 4 );
			$total_ltime = number_format_i18n( $total_time, 10 );

			echo '<tr>';
			echo '<td>&nbsp;</td>';
			echo "<td>{$total_calls}</td>";
			echo "<td title='{$total_ltime}'>{$total_stime}</td>";
			echo '</tr>';

		} else {

			echo '<td colspan="3" style="text-align:center !important"><em>' . __( 'none', 'query_monitor' ) . '</em></td>';

		}

		echo '</tbody>';
		echo '</table>';

	}

	function output_conds() {

		echo '<table class="qm" cellspacing="0" id="qm-conds">';
		echo '<thead>';
		echo '<tr>';
		echo '<th>' . __( 'Conditionals', 'query_monitor' ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		foreach ( $this->conds['true'] as $cond ) {
			echo '<tr class="qm-true">';
			echo '<td class="qm-ltr">' . $cond . '()</td>';
			echo '</tr>';
		}

		foreach ( $this->conds['false'] as $cond ) {
			echo '<tr class="qm-false">';
			echo '<td class="qm-ltr">' . $cond . '()</td>';
			echo '</tr>';
		}

		#foreach ( $this->conds['na'] as $cond )
		#	echo '<tr class="qm-na">';
		#	echo '<td class="qm-ltr">' . $cond . '()</td>';
		#	echo '</tr>';
		#}

		echo '</tbody>';
		echo '</table>';

	}

	function process_conds() {

		$conds = array(
			'is_404', 'is_archive', 'is_admin', 'is_attachment', 'is_author', 'is_blog_admin', 'is_category', 'is_comments_popup',
			'is_date', 'is_day', 'is_feed', 'is_front_page', 'is_home', 'is_main_site', 'is_month', 'is_multitax', /*'is_multi_author',*/
			'is_network_admin', 'is_page', 'is_page_template', 'is_paged', 'is_post_type_archive', 'is_preview', 'is_robots', 'is_rtl',
			'is_search', 'is_single', 'is_singular', 'is_ssl', 'is_sticky', 'is_tag', 'is_tax', 'is_time', 'is_trackback', 'is_year'
		);	

		$true = $false = $na = array();

		foreach ( $conds as $cond ) {
			if ( function_exists( $cond ) ) {

				if ( ( 'is_sticky' == $cond ) and !get_post( $id = null ) ) {
					# Special case for is_sticky to prevent PHP notices
					$false[] = $cond;
				} else if ( ( 'is_main_site' == $cond ) and !$this->is_multisite ) {
					# Special case for is_main_site to prevent it from annoying me on single site installs
					$na[] = $cond;
				} else {
					if ( call_user_func( $cond ) )
						$true[] = $cond;
					else
						$false[] = $cond;
				}

			} else {
				$na[] = $cond;
			}
		}

		return $this->conds = compact( 'true', 'false', 'na' );

	}

	function output_hooks() {

		global $wp_actions;

		echo '<table class="qm" cellspacing="0" id="qm-hooks">';
		echo '<thead>';
		echo '<tr>';
		echo '<th>' . __( 'Hook', 'query_monitor' ) . '</th>';
		echo '<th>' . __( 'Actions', 'query_monitor' ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		if ( is_numeric( current( $wp_actions ) ) )
			$actions = array_keys( $wp_actions ); # wp 3.0+
		else
			$actions = array_values( $wp_actions ); # < wp 3.0

		$qmc = get_class( $this );

		if ( $this->is_multisite and is_network_admin() )
			$screen = preg_replace( '|-network$|', '', $this->screen );
		else
			$screen = $this->screen;

		foreach ( $actions as $action ) {

			$name = $action;

			if ( !empty( $screen ) ) {

				if ( false !== strpos( $name, $screen . '.php' ) )
					$name = str_replace( '-' . $screen . '.php', '-<span class="qm-current">' . $screen . '.php</span>', $name );
				else
					$name = str_replace( '-' . $screen, '-<span class="qm-current">' . $screen . '</span>', $name );

			}

			echo '<tr>';
			echo "<td valign='top'>$name</td>";
			if ( isset( $GLOBALS['wp_filter'][$action] ) ) {
				echo '<td><table class="qm-inner" cellspacing="0">';
				foreach( $GLOBALS['wp_filter'][$action] as $priority => $functions ) {
					foreach ( $functions as $function ) {
						$css = '';
						if ( is_array( $function['function'] ) ) {
							$class = $function['function'][0];
							if ( is_object( $class ) )
								$class = get_class( $class );
							if ( $qmc == $class )
								$css = 'qm-qm';
							$out = $class . '-&gt;' . $function['function'][1] . '()';
						} else {
							$out = $function['function'] . '()';
						}
						echo '<tr class="' . $css . '">';
						echo '<td valign="top" class="qm-priority">' . $priority . '</td>';
						echo '<td valign="top" class="qm-ltr">';
						echo $out;
						echo '</td>';
						echo '</tr>';
					}
				}
				echo '</table></td>';
			} else {
				echo '<td>&nbsp;</td>';
			}
			echo '</tr>';
		}

		echo '</tbody>';
		echo '</table>';

	}

	function output_qvars() {

		echo '<table class="qm" cellspacing="0" id="qm-qvars">';
		echo '<thead>';
		echo '<tr>';
		echo '<th colspan="2">' . __( 'Query Vars', 'query_monitor' ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		if ( empty( $this->qvars ) ) {
			echo '<tr>';
			echo '<td colspan="2" style="text-align:center !important"><em>' . __( 'none', 'query_monitor' ) . '</em></td>';
			echo '</tr>';
			echo '</tbody>';
			echo '</table>';
			return;
		}

		foreach( $this->qvars as $var => $value ) {
			if ( in_array( $var, $this->plugin_qvars ) )
				$var = '<span class="qm-current">' . $var . '</span>';
			echo '<tr>';
			echo "<td valign='top'>{$var}</td>";
			if ( is_array( $value ) ) {
				echo '<td valign="top"><ul>';
				foreach ( $value as $k => $v ) {
					$k = esc_html( $k );
					$v = esc_html( $v );
					echo "<li>{$k} => {$v}</li>";
				}
				echo '</ul></td>';
			} else {
				$value = esc_html( $value );
				echo "<td valign='top'>{$value}</td>";
			}
			echo '</tr>';
		}

		echo '</tbody>';
		echo '</table>';

	}

	function output_theme() {

		global $template;

		if ( is_admin() )
			return;

		# @TODO display parent/child theme info

		$template_file = apply_filters( 'query_monitor_template', basename( $template ) );

		echo '<table class="qm" cellspacing="0" id="qm-theme">';
		echo '<thead>';
		echo '<tr>';
		echo '<th colspan="2">' . __( 'Theme', 'query_monitor' ) . '</th>';
		echo '</tr>';
		echo '</thead>';

		echo '<tbody>';
		echo '<tr>';
		echo '<td>' . __( 'Template', 'query_monitor' ) . '</td>';
		echo "<td>{$template_file}</td>";
		echo '</tr>';

		if ( !empty( $this->body_class ) ) {

			echo '<tr>';
			echo '<td rowspan="' . count( $this->body_class ) . '">' . __( 'Body Classes', 'query_monitor' ) . '</td>';
			$first = true;

			foreach ( $this->body_class as $class ) {

				if ( !$first )
					echo '<tr>';

				echo "<td>{$class}</td>";
				echo '</tr>';

				$first = false;

			}

		}

		echo '</tbody>';
		echo '</table>';

	}

	function timer_stop() {
		global $timestart;
		return microtime( true ) - $timestart;
	}

	function _sort( $a, $b ) {
		if ( $a['ltime'] == $b['ltime'] )
			return 0;
		else
			return ( $a['ltime'] > $b['ltime'] ) ? -1 : 1;
	}

	function wpv() {
		return 'qm-wp-' . ( floatval( $GLOBALS['wp_version'] ) * 10 );
	}

}

$querymonitor = new QueryMonitor();

?>