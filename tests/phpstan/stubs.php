<?php declare(strict_types = 1);

// QM constants:

define( 'QM_COOKIE', '' );
define( 'QM_DB_EXPENSIVE', 1 );
define( 'QM_ERROR_FATALS', 1 );

// Third party constants:

define( 'Altis\ROOT_DIR', '' );

// Third party functions:

function members_register_cap_group( string $name, array $args = [] ): void {}

function members_register_cap( string $name, array $args = [] ): void {}

// Third party classes:

class WP_CLI {

	/**
	 * @param string $name
	 * @param callable|object|string $callable
	 * @param array<string, mixed> $args
	 * @return bool
	 */
	public static function add_command( string $name, $callable, array $args = [] ): bool {
		return true;
	}

	public static function success( string $message ): void {}

	/**
	 * @param string|WP_Error|Exception|Throwable $message
	 */
	public static function warning( $message ): void {}

	/**
	 * @param string|WP_Error|Exception|Throwable $message
	 * @param bool|int $exit
	 */
	public static function error( $message, $exit = true ): void {}

}
