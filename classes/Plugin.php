<?php declare(strict_types = 1);
/**
 * Abstract plugin wrapper.
 *
 * @package query-monitor
 */

if ( ! class_exists( 'QM_Plugin' ) ) {
abstract class QM_Plugin {

	/**
	 * @var array<string, string>
	 */
	private $plugin = array();

	/**
	 * @var string
	 */
	public $file = '';

	/**
	 * Class constructor
	 *
	 * @param string $file
	 */
	protected function __construct( $file ) {
		$this->file = $file;
	}

	/**
	 * Returns the URL for for a file/dir within this plugin.
	 *
	 * @param string $file The path within this plugin, e.g. '/js/clever-fx.js'
	 * @return string URL
	 */
	final public function plugin_url( $file = '' ) {
		return $this->_plugin( 'url', $file );
	}

	/**
	 * Returns the filesystem path for a file/dir within this plugin.
	 *
	 * @param string $file The path within this plugin, e.g. '/js/clever-fx.js'
	 * @return string Filesystem path
	 */
	final public function plugin_path( $file = '' ) {
		return $this->_plugin( 'path', $file );
	}

	/**
	 * Returns a version number for the given plugin file.
	 *
	 * @param string $file The path within this plugin, e.g. '/js/clever-fx.js'
	 * @return string Version
	 */
	final public function plugin_ver( $file ) {
		$path = $this->plugin_path( $file );

		if ( file_exists( $path ) ) {
			return (string) filemtime( $path );
		}

		return QM_VERSION;
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
	 *
	 * @param string $item
	 * @param string $file
	 * @return string
	 */
	private function _plugin( $item, $file = '' ) {
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

	/**
	 * @param string $name Icon name.
	 * @return string Icon HTML.
	 */
	public static function icon( $name ) {
		if ( 'blank' === $name ) {
			return '<span class="qm-icon qm-icon-blank"></span>';
		}

		return sprintf(
			'<svg class="qm-icon qm-icon-%1$s" aria-hidden="true" width="20" height="20" viewBox="0 0 20 20"><use href="#qm-icon-%1$s" /></svg>',
			esc_attr( $name )
		);
	}

}
}
