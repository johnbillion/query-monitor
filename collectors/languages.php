<?php declare(strict_types = 1);
/**
 * Language and locale collector.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @extends QM_DataCollector<QM_Data_Languages>
 */
class QM_Collector_Languages extends QM_DataCollector {

	public $id = 'languages';

	public function get_storage(): QM_Data {
		return new QM_Data_Languages();
	}

	/**
	 * @return void
	 */
	public function set_up() {

		parent::set_up();

		add_filter( 'load_textdomain_mofile', array( $this, 'log_file_load' ), 9999, 2 );
		add_filter( 'load_script_translation_file', array( $this, 'log_script_file_load' ), 9999, 3 );
		add_action( 'init', array( $this, 'collect_locale_data' ), 9999 );

	}

	/**
	 * @return void
	 */
	public function tear_down() {
		remove_filter( 'load_textdomain_mofile', array( $this, 'log_file_load' ), 9999 );
		remove_filter( 'load_script_translation_file', array( $this, 'log_script_file_load' ), 9999 );
		remove_action( 'init', array( $this, 'collect_locale_data' ), 9999 );

		parent::tear_down();
	}

	/**
	 * @return void
	 */
	public function collect_locale_data() {
		$this->data->locale = get_locale();
		$this->data->user_locale = function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale();
		$this->data->determined_locale = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();
		$this->data->language_attributes = get_language_attributes();

		if ( function_exists( '\Inpsyde\MultilingualPress\siteLanguageTag' ) ) {
			$this->data->mlp_language = \Inpsyde\MultilingualPress\siteLanguageTag();
		}

		if ( function_exists( 'pll_current_language' ) ) {
			$this->data->pll_language = pll_current_language();
		}
	}

	/**
	 * @return array<int, string>
	 */
	public function get_concerned_actions() {
		return array(
			'load_textdomain',
			'unload_textdomain',
		);
	}

	/**
	 * @return array<int, string>
	 */
	public function get_concerned_filters() {
		return array(
			'determine_locale',
			'gettext',
			'gettext_with_context',
			'language_attributes',
			'load_script_textdomain_relative_path',
			'load_script_translation_file',
			'load_script_translations',
			'load_textdomain_mofile',
			'locale',
			'ngettext',
			'ngettext_with_context',
			'override_load_textdomain',
			'override_unload_textdomain',
			'plugin_locale',
			'pre_determine_locale',
			'pre_load_script_translations',
			'pre_load_textdomain',
			'theme_locale',
		);
	}

	/**
	 * @return array<int, string>
	 */
	public function get_concerned_options() {
		return array(
			'WPLANG',
		);
	}

	/**
	 * @return array<int, string>
	 */
	public function get_concerned_constants() {
		return array(
			'WPLANG',
		);
	}

	/**
	 * @return void
	 */
	public function process() {
		if ( empty( $this->data->languages ) ) {
			return;
		}

		$this->data->total_size = 0;

		ksort( $this->data->languages );

		foreach ( $this->data->languages as & $mofiles ) {
			foreach ( $mofiles as & $mofile ) {
				if ( $mofile['found'] ) {
					$this->data->total_size += $mofile['found'];
				}
			}
		}
	}

	/**
	 * Store log data.
	 *
	 * @param mixed $mofile Should be a string path to the MO file, could be anything.
	 * @param string $domain Text domain.
	 * @return string
	 */
	public function log_file_load( $mofile, $domain ) {
		if ( 'query-monitor' === $domain && self::hide_qm() ) {
			return $mofile;
		}

		if ( is_string( $mofile ) && isset( $this->data->languages[ $domain ][ $mofile ] ) ) {
			return $mofile;
		}

		$trace = new QM_Backtrace( array(
			'ignore_hook' => array(
				current_filter() => true,
			),
			'ignore_func' => array(
				'load_textdomain' => ( 'default' !== $domain ),
				'load_muplugin_textdomain' => true,
				'load_plugin_textdomain' => true,
				'load_theme_textdomain' => true,
				'load_child_theme_textdomain' => true,
				'load_default_textdomain' => true,
			),
		) );

		$found = ( is_string( $mofile ) ) && file_exists( $mofile ) ? filesize( $mofile ) : false;

		if ( ! is_string( $mofile ) ) {
			$mofile = gettype( $mofile );
		}

		$this->data->languages[ $domain ][ $mofile ] = array(
			'caller' => $trace->get_caller(),
			'domain' => $domain,
			'file' => $mofile,
			'found' => $found,
			'handle' => null,
			'type' => 'gettext',
		);

		return $mofile;

	}

	/**
	 * Filters the file path for loading script translations for the given script handle and textdomain.
	 *
	 * @param string|false $file   Path to the translation file to load. False if there isn't one.
	 * @param string       $handle Name of the script to register a translation domain to.
	 * @param string       $domain The textdomain.
	 *
	 * @return string|false Path to the translation file to load. False if there isn't one.
	 */
	public function log_script_file_load( $file, $handle, $domain ) {
		$trace = new QM_Backtrace( array(
			'ignore_hook' => array(
				current_filter() => true,
			),
		) );

		$found = ( $file && file_exists( $file ) ) ? filesize( $file ) : false;
		$key = $file ?: uniqid();

		$this->data->languages[ $domain ][ $key ] = array(
			'caller' => $trace->get_caller(),
			'domain' => $domain,
			'file' => $file,
			'found' => $found,
			'handle' => $handle,
			'type' => 'jed',
		);

		return $file;
	}

}

# Load early to catch early errors
QM_Collectors::add( new QM_Collector_Languages() );
