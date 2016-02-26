<?php
/*
Plugin Name: Query Monitor Database Class

*********************************************************************

Ensure this file is symlinked to your wp-content directory to provide
additional database query information in Query Monitor's output.

*********************************************************************

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
$qm_dir = dirname( dirname( __FILE__ ) );
if ( ! is_readable( $backtrace = "{$qm_dir}/classes/Backtrace.php" ) ) {
	return;
}
require_once $backtrace;

if ( !defined( 'SAVEQUERIES' ) ) {
	define( 'SAVEQUERIES', true );
}

class QM_DB extends wpdb {

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

		foreach ( $this->qm_php_vars as $setting => &$val ) {
			$val = ini_get( $setting );
		}

		parent::__construct( $dbuser, $dbpassword, $dbname, $dbhost );

	}

	/**
	 * Perform a MySQL database query, using current database connection.
	 *
	 * @see wpdb::query()
	 *
	 * @param string $query Database query
	 * @return int|false Number of rows affected/selected or false on error
	 */
	function query( $query ) {
		if ( ! $this->ready ) {
			return false;
		}

		if ( $this->show_errors ) {
			$this->hide_errors();
		}

		$result = parent::query( $query );

		if ( ! SAVEQUERIES ) {
			return $result;
		}

		$i = $this->num_queries - 1;
		$this->queries[$i]['trace'] = new QM_Backtrace( array(
			'ignore_items' => 1,
		) );

		if ( $this->last_error ) {
			$this->queries[$i]['result'] = new WP_Error( 'qmdb', $this->last_error );
		} else {
			$this->queries[$i]['result'] = $result;
		}

		return $result;
	}

}

$wpdb = new QM_DB( DB_USER, DB_PASSWORD, DB_NAME, DB_HOST );
