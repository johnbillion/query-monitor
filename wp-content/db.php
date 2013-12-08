<?php
/*
Plugin Name: Query Monitor Database Class

*********************************************************************

Ensure this file is symlinked to your wp-content directory to provide
additional database query information in Query Monitor's output.

*********************************************************************

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
foreach ( array( 'Backtrace', 'Collector', 'Plugin', 'Util' ) as $f ) {
	if ( ! is_readable( $file = dirname( __FILE__ ) . "/../{$f}.php" ) )
		return;
	require_once $file;
}

if ( !defined( 'SAVEQUERIES' ) )
	define( 'SAVEQUERIES', true );

class QueryMonitorDB extends wpdb {

	public $qm_php_vars = array(
		'max_execution_time'  => null,
		'memory_limit'        => null,
		'upload_max_filesize' => null,
		'post_max_size'       => null,
		'display_errors'      => null,
		'log_errors'          => null,
	);

	/**
	 * Class constructor
	 */
	function __construct( $dbuser, $dbpassword, $dbname, $dbhost ) {

		foreach ( $this->qm_php_vars as $setting => &$val )
			$val = ini_get( $setting );

		parent::__construct( $dbuser, $dbpassword, $dbname, $dbhost );

	}

	/**
	 * Perform a MySQL database query, using current database connection.
	 *
	 * More information can be found on the codex page.
	 *
	 * @since 0.71
	 *
	 * @param string $query Database query
	 * @return int|false Number of rows affected/selected or false on error
	 */
	function query( $query ) {
		if ( ! $this->ready )
			return false;

		if ( $this->show_errors )
			$this->hide_errors();

		// some queries are made before the plugins have been loaded, and thus cannot be filtered with this method
		$query = apply_filters( 'query', $query );

		$return_val = 0;
		$this->flush();

		// Log how the function was called
		$this->func_call = "\$db->query(\"$query\")";

		// Keep track of the last query for debug..
		$this->last_query = $query;

		if ( defined( 'SAVEQUERIES' ) && SAVEQUERIES )
			$this->timer_start();

		$this->result = @mysql_query( $query, $this->dbh );
		$this->num_queries++;

		if ( defined( 'SAVEQUERIES' ) && SAVEQUERIES ) {
			$trace = new QM_Backtrace;
			$q = array(
				'sql'    => $query,
				'ltime'  => $this->timer_stop(),
				'stack'  => implode( ', ', array_reverse( $trace->get_stack() ) ),
				'trace'  => $trace,
				'result' => null,
			);
			# Numeric indices are for compatibility for anything else using saved queries
			$q[0] = $q['sql'];
			$q[1] = $q['ltime'];
			$q[2] = $q['stack'];
			$this->queries[$this->num_queries] = $q;
		}

		// If there is an error then take note of it..
		if ( $this->last_error = mysql_error( $this->dbh ) ) {
			if ( defined( 'SAVEQUERIES' ) && SAVEQUERIES )
				$this->queries[$this->num_queries]['result'] = new WP_Error( 'qmdb', $this->last_error );
			// Clear insert_id on a subsequent failed insert.
			if ( $this->insert_id && preg_match( '/^\s*(insert|replace)\s/i', $query ) )
				$this->insert_id = 0;

			$this->print_error();
			return false;
		}

		if ( preg_match( '/^\s*(create|alter|truncate|drop)\s/i', $query ) ) {
			$return_val = $this->result;
		} elseif ( preg_match( '/^\s*(insert|delete|update|replace)\s/i', $query ) ) {
			$this->rows_affected = mysql_affected_rows( $this->dbh );
			// Take note of the insert_id
			if ( preg_match( '/^\s*(insert|replace)\s/i', $query ) ) {
				$this->insert_id = mysql_insert_id($this->dbh);
			}
			// Return number of rows affected
			$return_val = $this->rows_affected;
		} else {
			$num_rows = 0;
			while ( $row = @mysql_fetch_object( $this->result ) ) {
				$this->last_result[$num_rows] = $row;
				$num_rows++;
			}

			// Log number of rows the query returned
			// and return number of rows selected
			$this->num_rows = $num_rows;
			$return_val     = $num_rows;
		}

		if ( defined( 'SAVEQUERIES' ) && SAVEQUERIES )
			$this->queries[$this->num_queries]['result'] = $return_val;

		return $return_val;
	}

}

$wpdb = new QueryMonitorDB( DB_USER, DB_PASSWORD, DB_NAME, DB_HOST );
