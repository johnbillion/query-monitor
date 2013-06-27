<?php
/*
Plugin Name: Query Monitor

********************************************************************

Symlink this file to your wp-content directory to provide additional
database query information in Query Monitor's output.

********************************************************************

© 2013 John Blackbourn

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

if ( !defined( 'SAVEQUERIES' ) )
	define( 'SAVEQUERIES', true );

class QueryMonitorDB extends wpdb {

	public $qm_php_vars = array(
		'max_execution_time'  => null,
		'memory_limit'        => null,
		'upload_max_filesize' => null,
		'post_max_size'       => null
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

		if ( $this->show_errors and class_exists( 'QM_Component_DB_Queries' ) )
			$this->hide_errors();

		// some queries are made before the plugins have been loaded, and thus cannot be filtered with this method
		if ( function_exists( 'apply_filters' ) )
			$query = apply_filters( 'query', $query );

		$return_val = 0;
		$this->flush();

		// Log how the function was called
		$this->func_call = "\$db->query(\"$query\")";

		// Keep track of the last query for debug..
		$this->last_query = $query;

		$this->timer_start();

		$this->result = @mysql_query( $query, $this->dbh );
		$this->num_queries++;

		if ( defined( 'SAVEQUERIES' ) && SAVEQUERIES ) {
			$trace = debug_backtrace( false );
			$this->queries[$this->num_queries] = array( $query, $this->timer_stop(), self::qm_get_caller( $trace ), self::qm_get_stack( $trace ), null );
			unset( $trace );
		}

		// If there is an error then take note of it..
		if ( $this->last_error = mysql_error( $this->dbh ) ) {
			$this->queries[$this->num_queries][4] = new WP_Error( 'qmdb', $this->last_error );
			$this->print_error();
			return false;
		}

		if ( preg_match( "/^\\s*(insert|delete|update|replace|alter) /i", $query ) ) {
			$this->rows_affected = mysql_affected_rows( $this->dbh );
			// Take note of the insert_id
			if ( preg_match( "/^\\s*(insert|replace) /i", $query ) ) {
				$this->insert_id = mysql_insert_id($this->dbh);
			}
			// Return number of rows affected
			$return_val = $this->rows_affected;
		} else {
			$i = 0;
			while ( $i < @mysql_num_fields( $this->result ) ) {
				$this->col_info[$i] = @mysql_fetch_field( $this->result );
				$i++;
			}
			$num_rows = 0;
			while ( $row = @mysql_fetch_object( $this->result ) ) {
				$this->last_result[$num_rows] = $row;
				$num_rows++;
			}

			@mysql_free_result( $this->result );

			// Log number of rows the query returned
			// and return number of rows selected
			$this->num_rows = $num_rows;
			$return_val     = $num_rows;
		}

		$this->queries[$this->num_queries][4] = $return_val;

		return $return_val;
	}

	public static function qm_get_stack( array $_trace ) {

		$stack = array();

		foreach ( $_trace as $t ) {
			if ( isset( $t['file'] ) )
				$stack[] = $t['file'];
		}

		return $stack;

	}

	public static function qm_get_caller( array $_trace ) {

		$trace = array_map( 'QM_Util::filter_trace', $_trace );
		$trace = array_values( array_filter( $trace ) );

		if ( empty( $trace ) )
			$trace[] = str_replace( QM_Util::standard_dir( ABSPATH ), '', QM_Util::standard_dir( $_trace[1]['file'] ) );

		return implode( ', ', array_reverse( $trace ) );

	}

}

require_once dirname( __FILE__ ) . '/../class.qm-util.php';

$wpdb = new QueryMonitorDB( DB_USER, DB_PASSWORD, DB_NAME, DB_HOST );
