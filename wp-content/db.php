<?php
/*
Plugin Name: Query Monitor
Version:     2.1.1

Move this file into your wp-content directory to provide additional
database query information in Query Monitor's output.

*/

if ( !defined( 'SAVEQUERIES' ) )
	define( 'SAVEQUERIES', true );

class QueryMonitorDB extends wpdb {

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
			$this->queries[$this->num_queries] = array( $query, $this->timer_stop(), $this->get_caller(), null );

		// If there is an error then take note of it..
		if ( $this->last_error = mysql_error( $this->dbh ) ) {
			$this->queries[$this->num_queries][3] = new WP_Error( 'query_monitor_db_error', $this->last_error );
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

		$this->queries[$this->num_queries][3] = $return_val;

		return $return_val;
	}

	function get_caller() {
		return implode( ', ', array_reverse( $this->backtrace() ) );
	}

	function backtrace() {
		$trace = debug_backtrace( false );
		$trace = array_values( array_filter( array_map( array( $this, '_filter_trace' ), $trace ) ) );
		return $trace;
	}

	function _filter_trace( $trace ) {

		$ignore_class = array(
			'wpdb',
			'QueryMonitor',
			'QueryMonitorDB',
			'W3_Db'
		);
		$ignore_func = array(
			'include_once',
			'require_once',
			'include',
			'require',
			'call_user_func_array',
			'call_user_func'
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
			else if ( 0 === strpos( $trace['class'], 'QM' ) )
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

}

if ( !defined( 'ABSPATH' ) )
    die();

$wpdb = new QueryMonitorDB( DB_USER, DB_PASSWORD, DB_NAME, DB_HOST );

?>