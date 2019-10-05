<?php
/**
 * Abstract plugin wrapper.
 *
 * @package query-monitor
 */

if ( ! class_exists( 'QM_Plugin' ) ) {
abstract class QM_Plugin {

	private $plugin = array();
	public static $minimum_php_version = '5.3.6';

	/**
	 * Class constructor
	 */
	protected function __construct( $file ) {
		$this->file = $file;
	}

	/**
	 * Returns the URL for for a file/dir within this plugin.
	 *
	 * @param $file string The path within this plugin, e.g. '/js/clever-fx.js'
	 * @return string URL
	 */
	final public function plugin_url( $file = '' ) {
		return $this->_plugin( 'url', $file );
	}

	/**
	 * Returns the filesystem path for a file/dir within this plugin.
	 *
	 * @param $file string The path within this plugin, e.g. '/js/clever-fx.js'
	 * @return string Filesystem path
	 */
	final public function plugin_path( $file = '' ) {
		return $this->_plugin( 'path', $file );
	}

	/**
	 * Returns a version number for the given plugin file.
	 *
	 * @param $file string The path within this plugin, e.g. '/js/clever-fx.js'
	 * @return string Version
	 */
	final public function plugin_ver( $file ) {
		return filemtime( $this->plugin_path( $file ) );
	}

	/**
	 * Returns the current plugin's basename, eg. 'my_plugin/my_plugin.php'.
	 *
	 * @return string Basename
	 */
	final public function plugin_base() {
		return $this->_plugin( 'base' );
	}

	/**
	 * Populates and returns the current plugin info.
	 */
	final private function _plugin( $item, $file = '' ) {
		if ( ! array_key_exists( $item, $this->plugin ) ) {
			switch ( $item ) {
				case 'url':
					$this->plugin[ $item ] = plugin_dir_url( $this->file );
					break;
				case 'path':
					$this->plugin[ $item ] = plugin_dir_path( $this->file );
					break;
				case 'base':
					$this->plugin[ $item ] = plugin_basename( $this->file );
					break;
			}
		}
		return $this->plugin[ $item ] . ltrim( $file, '/' );
	}

	public static function php_version_met() {
		static $met = null;

		if ( null === $met ) {
			$met = version_compare( PHP_VERSION, self::$minimum_php_version, '>=' );
		}

		return $met;
	}

	public static function php_version_nope() {
		printf(
			'<div id="qm-php-nope" class="notice notice-error is-dismissible"><p>%s</p></div>',
			wp_kses(
				sprintf(
					/* translators: 1: Required PHP version number, 2: Current PHP version number, 3: URL of PHP update help page */
					__( 'The Query Monitor plugin requires PHP version %1$s or higher. This site is running PHP version %2$s. <a href="%3$s">Learn about updating PHP</a>.', 'query-monitor' ),
					self::$minimum_php_version,
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
}
