<?php
/*
Copyright 2009-2017 John Blackbourn

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
if ( defined( 'E_DEPRECATED' ) ) {
	define( 'QM_E_DEPRECATED', E_DEPRECATED );
} else {
	define( 'QM_E_DEPRECATED', 0 );
}

if ( defined( 'E_USER_DEPRECATED' ) ) {
	define( 'QM_E_USER_DEPRECATED', E_USER_DEPRECATED );
} else {
	define( 'QM_E_USER_DEPRECATED', 0 );
}

class QM_Collector_PHP_Errors extends QM_Collector {

	public $id = 'php_errors';
	private $error_reporting = null;
	private $display_errors = null;
	private static $unexpected_error;
	private static $wordpress_couldnt;

	public function name() {
		return __( 'PHP Errors', 'query-monitor' );
	}

	public function __construct() {
		if ( defined( 'QM_DISABLE_ERROR_HANDLER' ) and QM_DISABLE_ERROR_HANDLER ) {
			return;
		}

		parent::__construct();
		set_error_handler( array( $this, 'error_handler' ) );
		register_shutdown_function( array( $this, 'shutdown_handler' ) );

		$this->error_reporting = error_reporting();
		$this->display_errors = ini_get( 'display_errors' );
		ini_set( 'display_errors', 0 );

	}

	public function error_handler( $errno, $message, $file = null, $line = null, $context = null ) {

		do_action( 'qm/collect/new_php_error', $errno, $message, $file, $line, $context );

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

		if ( ! class_exists( 'QM_Backtrace' ) ) {
			return false;
		}

		if ( 0 === error_reporting() && 0 !== $this->error_reporting ) {
			// This is most likely an @-suppressed error
			$type .= '-suppressed';
		}

		if ( ! isset( self::$unexpected_error ) ) {
			// These strings are from core. They're passed through `__()` as variables so they get translated at runtime
			// but do not get seen by GlotPress when it populates its database of translatable strings for QM.
			$unexpected_error  = 'An unexpected error occurred. Something may be wrong with WordPress.org or this server&#8217;s configuration. If you continue to have problems, please try the <a href="https://wordpress.org/support/">support forums</a>.';
			$wordpress_couldnt = '(WordPress could not establish a secure connection to WordPress.org. Please contact your server administrator.)';
			self::$unexpected_error  = call_user_func( '__', $unexpected_error );
			self::$wordpress_couldnt = call_user_func( '__', $wordpress_couldnt );
		}

		// Intentionally skip reporting these core warnings. They're a distraction when developing offline.
		// The failed HTTP request will still appear in QM's output so it's not a big problem hiding these warnings.
		if ( self::$unexpected_error === $message ) {
			return false;
		}
		if ( self::$unexpected_error . ' ' . self::$wordpress_couldnt === $message ) {
			return false;
		}

		$trace  = new QM_Backtrace( array(
			'ignore_current_filter' => false,
		) );
		$caller = $trace->get_caller();
		$key    = md5( $message . $file . $line . $caller['id'] );

		$filename = QM_Util::standard_dir( $file, '' );

		if ( isset( $this->data['errors'][ $type ][ $key ] ) ) {
			$this->data['errors'][ $type ][ $key ]->calls++;
		} else {
			$this->data['errors'][ $type ][ $key ] = (object) array(
				'errno'    => $errno,
				'type'     => $type,
				'message'  => $message,
				'file'     => $file,
				'filename' => $filename,
				'line'     => $line,
				'trace'    => $trace,
				'calls'    => 1,
			);
		}

		return apply_filters( 'qm/collect/php_errors_return_value', false );

	}

	public function shutdown_handler() {

		$e = error_get_last();

		if ( empty( $this->display_errors ) ) {
			return;
		}

		if ( empty( $e ) or ! ( $e['type'] & ( E_ERROR | E_PARSE | E_COMPILE_ERROR | E_COMPILE_WARNING | E_USER_ERROR | E_RECOVERABLE_ERROR ) ) ) {
			return;
		}

		if ( $e['type'] & E_RECOVERABLE_ERROR ) {
			$error = 'Catchable fatal error';
		} elseif ( $e['type'] & E_COMPILE_WARNING ) {
			$error = 'Warning';
		} else {
			$error = 'Fatal error';
		}

		if ( function_exists( 'xdebug_print_function_stack' ) ) {

			xdebug_print_function_stack( sprintf( '%1$s: %2$s in %3$s on line %4$d. Output triggered ',
				$error,
				$e['message'],
				$e['file'],
				$e['line']
			) );

		} else {

			printf( // WPCS: XSS ok.
				'<br /><b>%1$s</b>: %2$s in <b>%3$s</b> on line <b>%4$d</b><br />',
				htmlentities( $error ),
				htmlentities( $e['message'] ),
				htmlentities( $e['file'] ),
				intval( $e['line'] )
			);

		}

	}

	public function tear_down() {
		parent::tear_down();
		ini_set( 'display_errors', $this->display_errors );
		restore_error_handler();
	}

}

# Load early to catch early errors
QM_Collectors::add( new QM_Collector_PHP_Errors );
