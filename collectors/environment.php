<?php
/*
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

class QM_Collector_Environment extends QM_Collector {

	public $id = 'environment';
	protected $php_vars = array(
		'max_execution_time',
		'memory_limit',
		'upload_max_filesize',
		'post_max_size',
		'display_errors',
		'log_errors',
	);

	public function name() {
		return __( 'Environment', 'query-monitor' );
	}

	public function __construct() {

		global $wpdb;

		parent::__construct();

		# If QueryMonitorDB is in place then we'll use the values which were
		# caught early before any plugins had a chance to alter them

		foreach ( $this->php_vars as $setting ) {
			if ( isset( $wpdb->qm_php_vars ) and isset( $wpdb->qm_php_vars[$setting] ) )
				$val = $wpdb->qm_php_vars[$setting];
			else
				$val = ini_get( $setting );
			$this->data['php']['variables'][$setting]['before'] = $val;
		}

	}

	public static function get_error_levels( $error_reporting ) {

		$levels = array();

		$constants = array(
			'E_ERROR',
			'E_WARNING',
			'E_PARSE',
			'E_NOTICE',
			'E_CORE_ERROR',
			'E_CORE_WARNING',
			'E_COMPILE_ERROR',
			'E_COMPILE_WARNING',
			'E_USER_ERROR',
			'E_USER_WARNING',
			'E_USER_NOTICE',
			'E_STRICT',
			'E_RECOVERABLE_ERROR',
			'E_DEPRECATED',
			'E_USER_DEPRECATED',
			'E_ALL'
		);

		foreach ( $constants as $level ) {
			if ( defined( $level ) ) {
				$c = constant( $level );
				if ( $error_reporting & $c ) 
					$levels[$c] = $level;
			}
		}

		return $levels;

	}

	public function process() {

		global $wp_version, $blog_id;

		$mysql_vars = array(
			'key_buffer_size'    => true,  # Key cache size limit
			'max_allowed_packet' => false, # Individual query size limit
			'max_connections'    => false, # Max number of client connections
			'query_cache_limit'  => true,  # Individual query cache size limit
			'query_cache_size'   => true,  # Total cache size limit
			'query_cache_type'   => 'ON'   # Query cache on or off
		);
		$php_u = '';

		if ( $dbq = QueryMonitor::get_collector( 'db_queries' ) ) {

			foreach ( $dbq->db_objects as $id => $db ) {

				if ( !is_a( $db, 'wpdb' ) )
					continue;

				$variables = $db->get_results( "
					SHOW VARIABLES
					WHERE Variable_name IN ( '" . implode( "', '", array_keys( $mysql_vars ) ) . "' )
				" );

				if ( is_resource( $db->dbh ) ) {
					$version = mysql_get_server_info( $db->dbh );
					$driver  = 'mysql';
				} else if ( is_object( $db->dbh ) and method_exists( $db->dbh, 'db_version' ) ) {
					$version = $db->dbh->db_version();
					$driver  = get_class( $db->dbh );
				} else {
					$version = $driver = '<span class="qm-warn">' . __( 'Unknown', 'query-monitor' ) . '</span>';
				}

				$this->data['db'][$id] = array(
					'version'   => $version,
					'driver'    => $driver,
					'user'      => $db->dbuser,
					'host'      => $db->dbhost,
					'name'      => $db->dbname,
					'vars'      => $mysql_vars,
					'variables' => $variables
				);

			}

		}

		if ( function_exists( 'posix_getpwuid' ) ) {

			$u = posix_getpwuid( posix_getuid() );
			$g = posix_getgrgid( $u['gid'] );
			$php_u = esc_html( $u['name'] . ':' . $g['name'] );

		} else if ( isset( $_SERVER['USER'] ) ) {

			$php_u = esc_html( $_SERVER['USER'] );

		} else if ( function_exists( 'exec' ) ) {

			$php_u = esc_html( exec( 'whoami' ) );

		}

		if ( empty( $php_u ) )
			$php_u = '<em>' . __( 'Unknown', 'query-monitor' ) . '</em>';

		$this->data['php']['version'] = phpversion();
		$this->data['php']['user']    = $php_u;

		foreach ( $this->php_vars as $setting )
			$this->data['php']['variables'][$setting]['after'] = ini_get( $setting );

		$this->data['php']['error_reporting'] = error_reporting();

		# @TODO put WP's other debugging constants in here, eg. SCRIPT_DEBUG
		$this->data['wp'] = array(
			'version'      => $wp_version,
			'WP_DEBUG'     => self::format_bool_constant( 'WP_DEBUG' ),
			'WP_LOCAL_DEV' => self::format_bool_constant( 'WP_LOCAL_DEV' ),
		);

		if ( is_multisite() )
			$this->data['wp']['blog_id'] = $blog_id;

		$server = explode( ' ', $_SERVER['SERVER_SOFTWARE'] );
		$server = explode( '/', reset( $server ) );

		if ( isset( $server[1] ) )
			$server_version = $server[1];
		else
			$server_version = '<em>' . __( 'Unknown', 'query-monitor' ) . '</em>';

		if ( isset( $_SERVER['SERVER_ADDR'] ) )
			$address = $_SERVER['SERVER_ADDR'];
		else
			$address = '<em>' . __( 'Unknown', 'query-monitor' ) . '</em>';

		$this->data['server'] = array(
			'name'    => $server[0],
			'version' => $server_version,
			'address' => $address,
			'host'    => php_uname( 'n' )
		);

	}

}

function register_qm_collector_environment( array $qm ) {
	$qm['environment'] = new QM_Collector_Environment;
	return $qm;
}

add_filter( 'query_monitor_collectors', 'register_qm_collector_environment', 120 );
