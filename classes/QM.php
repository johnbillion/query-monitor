<?php
/**
 * A convenience class for wrapping certain user-facing functionality.
 *
 * @package query-monitor
 */

class QM {

	public static function emergency( $message, array $context = array() ) {
		do_action( 'qm/emergency', $message, $context );
	}

	public static function alert( $message, array $context = array() ) {
		do_action( 'qm/alert', $message, $context );
	}

	public static function critical( $message, array $context = array() ) {
		do_action( 'qm/critical', $message, $context );
	}

	public static function error( $message, array $context = array() ) {
		do_action( 'qm/error', $message, $context );
	}

	public static function warning( $message, array $context = array() ) {
		do_action( 'qm/warning', $message, $context );
	}

	public static function notice( $message, array $context = array() ) {
		do_action( 'qm/notice', $message, $context );
	}

	public static function info( $message, array $context = array() ) {
		do_action( 'qm/info', $message, $context );
	}

	public static function debug( $message, array $context = array() ) {
		do_action( 'qm/debug', $message, $context );
	}

	public static function log( $level, $message, array $context = array() ) {
		$logger = QM_Collectors::get( 'logger' );
		$logger->log( $level, $message, $context );
	}
}
