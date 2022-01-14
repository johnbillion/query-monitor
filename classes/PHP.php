<?php
/**
 * PHP version compatibility functionality.
 *
 * @package query-monitor
 */

class QM_PHP {

	/**
	 * @var string
	 */
	public static $minimum_version = '5.3.6';

	/**
	 * @return bool
	 */
	public static function version_met() {
		return version_compare( PHP_VERSION, self::$minimum_version, '>=' );
	}

	/**
	 * @return void
	 */
	public static function php_version_nope() {
		printf(
			'<div id="qm-php-nope" class="notice notice-error is-dismissible"><p>%s</p></div>',
			wp_kses(
				sprintf(
					/* translators: 1: Required PHP version number, 2: Current PHP version number, 3: URL of PHP update help page */
					__( 'The Query Monitor plugin requires PHP version %1$s or higher. This site is running PHP version %2$s. <a href="%3$s">Learn about updating PHP</a>.', 'query-monitor' ),
					self::$minimum_version,
					PHP_VERSION,
					'https://wordpress.org/support/update-php/'
				),
				array(
					'a' => array(
						'href' => array(),
					),
				)
			)
		);
	}

}
