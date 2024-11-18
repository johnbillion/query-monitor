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

		add_filter( 'load_textdomain_mofile', array( $this, 'log_mo_file_load' ), 9999, 2 );
		add_filter( 'load_translation_file', array( $this, 'log_translation_file_load' ), 9999, 3 );
		add_filter( 'load_script_translation_file', array( $this, 'log_script_file_load' ), 9999, 3 );
		add_action( 'init', array( $this, 'collect_locale_data' ), 9999 );

	}

	/**
	 * @return void
	 */
	public function tear_down() {
		remove_filter( 'load_textdomain_mofile', array( $this, 'log_mo_file_load' ), 9999 );
		remove_filter( 'load_translation_file', array( $this, 'log_translation_file_load' ), 9999 );
		remove_filter( 'load_script_translation_file', array( $this, 'log_script_file_load' ), 9999 );
		remove_action( 'init', array( $this, 'collect_locale_data' ), 9999 );

		parent::tear_down();
	}

	/**
	 * @return void
	 */
	public function collect_locale_data() {
		$this->data->locale = get_locale();
		$this->data->user_locale = get_user_locale();
		$this->data->determined_locale = determine_locale();
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
			'lang_dir_for_domain',
			'language_attributes',
			'load_script_textdomain_relative_path',
			'load_script_translation_file',
			'load_script_translations',
			'load_textdomain_mofile',
			'load_translation_file',
			'locale',
			'ngettext',
			'ngettext_with_context',
			'override_load_textdomain',
			'override_unload_textdomain',
			'plugin_locale',
			'pre_determine_locale',
			'pre_get_language_files_from_path',
			'pre_load_script_translations',
			'pre_load_textdomain',
			'theme_locale',
			'translation_file_format',
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
	 * Store log data for MO translation files prior to 6.5.
	 *
	 * @phpstan-template T
	 *
	 * @param mixed  $file Should be a string path to the MO file, could be anything.
	 * @param string $domain Text domain.
	 * @return string The original file path.
	 * @phpstan-param T $file
	 * @phpstan-return T
	 */
	public function log_mo_file_load( $file, $domain ) {
		if ( class_exists( 'WP_Translation_Controller' ) ) {
			return $file;
		}

		$found = is_string( $file ) && file_exists( $file );

		return $this->log_file_load( $file, $domain, $found );
	}

	/**
	 * Store log data for MO and PHP translation files in 6.5 and later.
	 *
	 * @phpstan-template T
	 *
	 * @param mixed  $file   Should be a string path to the MO or PHP file, could be anything.
	 * @param string $domain Text domain.
	 * @param string $locale Locale. Only present in 6.6 and later.
	 * @return string The original file path.
	 * @phpstan-param T $file
	 * @phpstan-return T
	 */
	public function log_translation_file_load( $file, $domain, ?string $locale = null ) {
		// @phpstan-ignore WPCompat.methodNotAvailable
		$i18n_controller = \WP_Translation_Controller::get_instance();

		// @phpstan-ignore WPCompat.methodNotAvailable
		$found = $i18n_controller->load_file( $file, $domain, $locale ?? determine_locale() );

		return $this->log_file_load( $file, $domain, $found );
	}

	/**
	 * Store log data.
	 *
	 * @phpstan-template T
	 *
	 * @param mixed  $mofile Should be a string path to the MO or PHP file, could be anything.
	 * @param string $domain Text domain.
	 * @param bool   $loaded Whether the translation file was found and loaded.
	 * @return string The original file path.
	 * @phpstan-param T $mofile
	 * @phpstan-return T
	 */
	public function log_file_load( $mofile, $domain, $loaded ) {
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
				'_load_textdomain_just_in_time' => true,
				'get_translations_for_domain' => true,
				'translate_with_gettext_context' => true,
				'translate_settings_using_i18n_schema' => true,
			),
		) );

		$found = ( $loaded && is_string( $mofile ) ) ? filesize( $mofile ) : false;
		$file = $mofile;

		if ( is_string( $file ) ) {
			switch ( pathinfo( $file, PATHINFO_EXTENSION ) ) {
				case 'mo':
					$type = 'gettext';
					break;
				case 'php':
					$type = 'php';
					break;
				default:
					$type = 'unknown';
					break;
			}
		} else {
			$type = 'unknown';
			$file = $type;
		}

		$this->data->languages[ $domain ][ $mofile ] = array(
			'caller' => $trace->get_caller(),
			'domain' => $domain,
			'file' => $file,
			'found' => $found,
			'handle' => null,
			'type' => $type,
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
