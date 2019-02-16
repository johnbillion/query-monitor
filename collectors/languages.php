<?php
/**
 * Language and locale collector.
 *
 * @package query-monitor
 */

class QM_Collector_Languages extends QM_Collector {

	public $id = 'languages';

	public function name() {
		return __( 'Languages', 'query-monitor' );
	}

	public function __construct() {

		parent::__construct();

		add_filter( 'override_load_textdomain', array( $this, 'log_file_load' ), 9999, 3 );
		add_filter( 'load_script_translation_file', array( $this, 'log_script_file_load' ), 9999, 3 );

	}

	public function get_concerned_actions() {
		return array(
			'load_textdomain',
			'unload_textdomain',
		);
	}

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

	public function get_concerned_options() {
		return array(
			'WPLANG',
		);
	}

	public function get_concerned_constants() {
		return array(
			'WPLANG',
		);
	}

	public function process() {
		$this->data['locale']      = get_locale();
		$this->data['user_locale'] = function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale();
		ksort( $this->data['languages'] );
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

		$trace    = new QM_Backtrace();
		$filtered = $trace->get_filtered_trace();
		$caller   = array();

		foreach ( $filtered as $i => $item ) {

			if ( in_array( $item['function'], array(
				'load_muplugin_textdomain',
				'load_plugin_textdomain',
				'load_theme_textdomain',
				'load_child_theme_textdomain',
				'load_default_textdomain',
			), true ) ) {
				$caller = $item;
				$display = $i + 1;
				if ( isset( $filtered[ $display ] ) ) {
					$caller['display'] = $filtered[ $display ]['display'];
				}
				break;
			}
		}

		if ( empty( $caller ) ) {
			if ( isset( $filtered[1] ) ) {
				$caller = $filtered[1];
			} else {
				$caller = $filtered[0];
			}
		}

		if ( ! isset( $caller['file'] ) && isset( $filtered[0]['file'] ) && isset( $filtered[0]['line'] ) ) {
			$caller['file'] = $filtered[0]['file'];
			$caller['line'] = $filtered[0]['line'];
		}

		$this->data['languages'][ $domain ][] = array(
			'caller' => $caller,
			'domain' => $domain,
			'file'   => $mofile,
			'found'  => file_exists( $mofile ) ? filesize( $mofile ) : false,
			'handle' => null,
			'type'   => 'gettext',
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
		$trace    = new QM_Backtrace();
		$filtered = $trace->get_filtered_trace();
		$caller   = $filtered[0];

		$this->data['languages'][ $domain ][] = array(
			'caller' => $caller,
			'domain' => $domain,
			'file'   => $file,
			'found'  => ( $file && file_exists( $file ) ) ? filesize( $file ) : false,
			'handle' => $handle,
			'type'   => 'jed',
		);

		return $file;
	}

}

# Load early to catch early errors
QM_Collectors::add( new QM_Collector_Languages() );
