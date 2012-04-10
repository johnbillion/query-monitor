<?php
/*
Plugin Name: Query Monitor
Version:     2.2.3b

Move this file into your wp-content directory to provide additional
database query information in Query Monitor's output.

*/

if ( !defined( 'SAVEQUERIES' ) )
	define( 'SAVEQUERIES', true );

# Pre-3.0.something compat
if ( !class_exists( 'wpdb' ) ) {
	$wpdb = true;
	require_once( ABSPATH . WPINC . '/wp-db.php' );
}

class QueryMonitorDB extends wpdb {

	var $qm_ignore_class = array(
		'wpdb',
		'QueryMonitor',
		'QueryMonitorDB',
		'ExtQuery',
		'W3_Db'
	);
	var $qm_ignore_func = array(
		'include_once',
		'require_once',
		'include',
		'require',
		'call_user_func_array',
		'call_user_func'
	);
	var $qm_show_arg = array(
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
	var $qm_filtered = false;
	var $qm_php_vars = array(
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

		if ( $this->show_errors and class_exists( 'QM_DB_Queries' ) )
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

		if ( defined( 'SAVEQUERIES' ) && SAVEQUERIES )
			$this->queries[$this->num_queries] = array( $query, $this->timer_stop(), $this->get_caller(), $this->get_stack(), null );

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

	function get_caller() {
		return implode( ', ', array_reverse( $this->backtrace() ) );
	}

	function get_stack() {

		$_trace = debug_backtrace( false );
		$stack  = array();

		unset( $_trace[0] ); # This file

		foreach ( $_trace as $t ) {
			if ( isset( $t['file'] ) )
				$stack[] = $t['file'];
		}

		return $stack;

	}

	function backtrace() {
		$_trace = debug_backtrace( false );

		if ( !$this->qm_filtered and function_exists( 'did_action' ) and did_action( 'plugins_loaded' ) ) {

			# Only run apply_filters on these once
			$this->qm_ignore_class = apply_filters( 'query_monitor_db_ignore_class', $this->qm_ignore_class );
			$this->qm_ignore_func  = apply_filters( 'query_monitor_db_ignore_func',  $this->qm_ignore_func );
			$this->qm_show_arg     = apply_filters( 'query_monitor_db_show_arg',     $this->qm_show_arg );
			$this->qm_filtered = true;

		}

		$trace = array_map( array( $this, '_filter_trace' ), $_trace );
		$trace = array_values( array_filter( $trace ) );
		if ( empty( $trace ) ) {
			$file = str_replace( '\\', '/', $_trace[3]['file'] );
			$path = str_replace( '\\', '/', ABSPATH );
			$file = str_replace( $path, '', $file );
			$trace[] = $file;
		}
		return $trace;
	}

	function _filter_trace( $trace ) {

		if ( isset( $trace['class'] ) ) {

			if ( in_array( $trace['class'], $this->qm_ignore_class ) )
				return null;
			else if ( 0 === strpos( $trace['class'], 'QM' ) )
				return null;
			else
				return $trace['class'] . $trace['type'] . $trace['function'] . '()';

		} else {

			if ( in_array( $trace['function'], $this->qm_ignore_func ) )
				return null;
			else if ( isset( $trace['args'][0] ) and in_array( $trace['function'], $this->qm_show_arg ) )
				return $trace['function'] . "('{$trace['args'][0]}')";
			else
				return $trace['function'] . '()';

		}

	}

}

if ( !defined( 'ABSPATH' ) )
    die();

$wpdb = new QueryMonitorDB( DB_USER, DB_PASSWORD, DB_NAME, DB_HOST );

?>