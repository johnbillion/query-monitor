<?php
/**
 * PHP version compatibility functionality.
 *
 * @package query-monitor
 */

if ( ! class_exists( 'QM_PHP' ) ) {
class QM_PHP {

	/**
	 * @var string
	 */
	public static $minimum_version = '7.4.0';

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
					/* translators: 1: Query Monitor, 2: Required PHP version number, 3: Current PHP version number, 4: URL of PHP update help page */
					__( 'The %1$s plugin requires PHP version %2$s or higher. This site is running PHP version %3$s. <a href="%4$s">Learn about updating PHP</a>.', 'query-monitor' ),
					'Query Monitor',
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

	/**
	 * @return void
	 */
	public static function vendor_nope() {
		printf(
			'<div id="qm-built-nope" class="notice notice-error"><p>Dependencies for Query Monitor need to be installed. Run <code>composer install --no-dev</code> from the <code>%s</code> directory.</p></div>',
			esc_html( dirname( dirname( __FILE__ ) ) )
		);
	}

}
}
