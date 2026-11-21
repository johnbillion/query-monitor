<?php
/**
 * Autoloader for Query Monitor's classes, using the map in classmap.php.
 *
 * This is loaded before the PHP version check, so it must not use syntax that's newer than the
 * minimum PHP version supported by WordPress.
 *
 * @package query-monitor
 */

spl_autoload_register(
	/**
	 * @param string $class_name
	 * @return void
	 */
	static function ( $class_name ) {
		static $classmap = null;

		if ( null === $classmap ) {
			$classmap = require __DIR__ . '/classmap.php';
		}

		if ( isset( $classmap[ $class_name ] ) ) {
			require __DIR__ . $classmap[ $class_name ];
		}
	}
);
