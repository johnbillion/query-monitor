<?php
/**
 * Language and locale collector.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QM_Collector_Languages extends QM_Collector {

	public $id = 'languages';

	/**
	 * @return void
	 */
	public function set_up() {

		parent::set_up();

		add_filter( 'override_load_textdomain', array( $this, 'log_file_load' ), 9999, 3 );
		add_filter( 'load_script_translation_file', array( $this, 'log_script_file_load' ), 9999, 3 );

	}

	/**
	 * @return void
	 */
	public function tear_down() {
		remove_filter( 'override_load_textdomain', array( $this, 'log_file_load' ), 9999 );
		remove_filter( 'load_script_translation_file', array( $this, 'log_script_file_load' ), 9999 );

		parent::tear_down();
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
		if ( empty( $this->data['languages'] ) ) {
			return;
		}

		$this->data['locale'] = get_locale();
		$this->data['user_locale'] = function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale();
		ksort( $this->data['languages'] );

		foreach ( $this->data['languages'] as & $mofiles ) {
			foreach ( $mofiles as & $mofile ) {
				$mofile['found_formatted'] = $mofile['found'] ? size_format( $mofile['found'] ) : '';
			}
		}
	}

	/**
	 * Store log data.
	 *
	 * @param bool   $override Whether to override the text domain. Default false.
	 * @param string $domain   Text domain. Unique identifier for retrieving translated strings.
	 * @param string $mofile   Path to the MO file.
	 * @return bool
	 */
	public function log_file_load( $override, $domain, $mofile ) {
		if ( 'query-monitor' === $domain && self::hide_qm() ) {
			return $override;
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

		$found = file_exists( $mofile ) ? filesize( $mofile ) : false;

		$this->data['languages'][ $domain ][] = array(
			'caller' => $trace->get_caller(),
			'domain' => $domain,
			'file' => $mofile,
			'found' => $found,
			'handle' => null,
			'type' => 'gettext',
		);

		return $override;

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

		$this->data['languages'][ $domain ][] = array(
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
