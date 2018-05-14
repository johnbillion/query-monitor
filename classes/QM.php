<?php
/**
 * A convenience class for wrapping certain user-facing functionality.
 *
 * @package query-monitor
 */

class QM {

	public static function emergency( $message, array $context = array() ) {
		$logger = QM_Collectors::get( 'logging' );
		$logger->emergency( $message, $context );
	}

	public static function alert( $message, array $context = array() ) {
		$logger = QM_Collectors::get( 'logging' );
		$logger->alert( $message, $context );
	}

	public static function critical( $message, array $context = array() ) {
		$logger = QM_Collectors::get( 'logging' );
		$logger->critical( $message, $context );
	}

	public static function error( $message, array $context = array() ) {
		$logger = QM_Collectors::get( 'logging' );
		$logger->error( $message, $context );
	}

	public static function warning( $message, array $context = array() ) {
		$logger = QM_Collectors::get( 'logging' );
		$logger->warning( $message, $context );
	}

	public static function notice( $message, array $context = array() ) {
		$logger = QM_Collectors::get( 'logging' );
		$logger->notice( $message, $context );
	}

	public static function info( $message, array $context = array() ) {
		$logger = QM_Collectors::get( 'logging' );
		$logger->info( $message, $context );
	}

	public static function debug( $message, array $context = array() ) {
		$logger = QM_Collectors::get( 'logging' );
		$logger->debug( $message, $context );
	}

	public static function log( $level, $message, array $context = array() ) {
		$logger = QM_Collectors::get( 'logging' );
		$logger->log( $level, $message, $context );
	}
}
