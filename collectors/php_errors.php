<?php
/*
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

# E_DEPRECATED and E_USER_DEPRECATED were introduced in PHP 5.3 so we need to use back-compat constants that work on 5.2.
if ( defined( 'E_DEPRECATED' ) )
	define( 'QM_E_DEPRECATED', E_DEPRECATED );
else
	define( 'QM_E_DEPRECATED', 0 );

if ( defined( 'E_USER_DEPRECATED' ) )
	define( 'QM_E_USER_DEPRECATED', E_USER_DEPRECATED );
else
	define( 'QM_E_USER_DEPRECATED', 0 );

class QM_Collector_PHP_Errors extends QM_Collector {

	public $id = 'php_errors';

	public function name() {
		return __( 'PHP Errors', 'query-monitor' );
	}

	public function __construct() {

		parent::__construct();
		set_error_handler( array( $this, 'error_handler' ) );

	}

	public function error_handler( $errno, $message, $file = null, $line = null ) {

		#if ( !( error_reporting() & $errno ) )
		#	return false;

		switch ( $errno ) {

			case E_WARNING:
			case E_USER_WARNING:
				$type = 'warning';
				break;

			case E_NOTICE:
			case E_USER_NOTICE:
				$type = 'notice';
				break;

			case E_STRICT:
				$type = 'strict';
				break;

			case QM_E_DEPRECATED:
			case QM_E_USER_DEPRECATED:
				$type = 'deprecated';
				break;

			default:
				return false;
				break;

		}

		if ( error_reporting() > 0 ) {

			$trace = new QM_Backtrace;
			$stack = $trace->get_stack();
			$func  = reset( $stack );
			$key   = md5( $message . $file . $line . $func );

			$filename = QM_Util::standard_dir( $file, '' );

			if ( isset( $this->data['errors'][$type][$key] ) ) {
				$this->data['errors'][$type][$key]->calls++;
			} else {
				$this->data['errors'][$type][$key] = (object) array(
					'errno'    => $errno,
					'type'     => $type,
					'message'  => $message,
					'file'     => $file,
					'filename' => $filename,
					'line'     => $line,
					'trace'    => $trace,
					'calls'    => 1
				);
			}

		}

		return apply_filters( 'query_monitor_php_errors_return_value', true );

	}

}

function register_qm_collector_php_errors( array $qm ) {
	$qm['php_errors'] = new QM_Collector_PHP_Errors;
	return $qm;
}

function qm_php_errors_return_value( $return ) {
	if ( QM_Util::is_ajax() )
		return true;
	if ( isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) and 'xmlhttprequest' == strtolower( $_SERVER['HTTP_X_REQUESTED_WITH'] ) )
		return true;
	# If Xdebug is enabled we'll return false so Xdebug's error handler can do its thing.
	if ( function_exists( 'xdebug_is_enabled' ) and xdebug_is_enabled() )
		return false;
	else
		return $return;
}

add_filter( 'query_monitor_collectors', 'register_qm_collector_php_errors', 110 );
add_filter( 'query_monitor_php_errors_return_value', 'qm_php_errors_return_value' );
