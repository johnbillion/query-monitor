<?php declare(strict_types = 1);
/**
 * A convenience class for wrapping certain user-facing functionality.
 *
 * @package query-monitor
 */

class QM {

	/**
	 * @param string $message
	 * @param array<string, mixed> $context
	 * @return void
	 */
	public static function emergency( $message, array $context = array() ) {
		/**
		 * Fires when an `emergency` level message is logged.
		 *
		 * @since 3.1.0
		 *
		 * @param mixed $message The message or data to log.
		 * @param array $context The context passed.
		 */
		do_action( 'qm/emergency', $message, $context );
	}

	/**
	 * @param string $message
	 * @param array<string, mixed> $context
	 * @return void
	 */
	public static function alert( $message, array $context = array() ) {
		/**
		 * Fires when an `alert` level message is logged.
		 *
		 * @since 3.1.0
		 *
		 * @param mixed $message The message or data to log.
		 * @param array $context The context passed.
		 */
		do_action( 'qm/alert', $message, $context );
	}

	/**
	 * @param string $message
	 * @param array<string, mixed> $context
	 * @return void
	 */
	public static function critical( $message, array $context = array() ) {
		/**
		 * Fires when a `critical` level message is logged.
		 *
		 * @since 3.1.0
		 *
		 * @param mixed $message The message or data to log.
		 * @param array $context The context passed.
		 */
		do_action( 'qm/critical', $message, $context );
	}

	/**
	 * @param string $message
	 * @param array<string, mixed> $context
	 * @return void
	 */
	public static function error( $message, array $context = array() ) {
		/**
		 * Fires when an `error` level message is logged.
		 *
		 * @since 3.1.0
		 *
		 * @param mixed $message The message or data to log.
		 * @param array $context The context passed.
		 */
		do_action( 'qm/error', $message, $context );
	}

	/**
	 * @param string $message
	 * @param array<string, mixed> $context
	 * @return void
	 */
	public static function warning( $message, array $context = array() ) {
		/**
		 * Fires when a `warning` level message is logged.
		 *
		 * @since 3.1.0
		 *
		 * @param mixed $message The message or data to log.
		 * @param array $context The context passed.
		 */
		do_action( 'qm/warning', $message, $context );
	}

	/**
	 * @param string $message
	 * @param array<string, mixed> $context
	 * @return void
	 */
	public static function notice( $message, array $context = array() ) {
		/**
		 * Fires when a `notice` level message is logged.
		 *
		 * @since 3.1.0
		 *
		 * @param mixed $message The message or data to log.
		 * @param array $context The context passed.
		 */
		do_action( 'qm/notice', $message, $context );
	}

	/**
	 * @param string $message
	 * @param array<string, mixed> $context
	 * @return void
	 */
	public static function info( $message, array $context = array() ) {
		/**
		 * Fires when an `info` level message is logged.
		 *
		 * @since 3.1.0
		 *
		 * @param mixed $message The message or data to log.
		 * @param array $context The context passed.
		 */
		do_action( 'qm/info', $message, $context );
	}

	/**
	 * @param string $message
	 * @param array<string, mixed> $context
	 * @return void
	 */
	public static function debug( $message, array $context = array() ) {
		/**
		 * Fires when a `debug` level message is logged.
		 *
		 * @since 3.1.0
		 *
		 * @param mixed $message The message or data to log.
		 * @param array $context The context passed.
		 */
		do_action( 'qm/debug', $message, $context );
	}

	/**
	 * @param string $level
	 * @param string $message
	 * @param array<string, mixed> $context
	 * @phpstan-param QM_Collector_Logger::* $level
	 * @return void
	 */
	public static function log( $level, $message, array $context = array() ) {
		/** @var QM_Collector_Logger */
		$logger = QM_Collectors::get( 'logger' );
		$logger->log( $level, $message, $context );
	}
}
