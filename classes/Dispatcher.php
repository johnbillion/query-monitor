<?php declare(strict_types = 1);
/**
 * Abstract dispatcher.
 *
 * @package query-monitor
 */

if ( ! class_exists( 'QM_Dispatcher' ) ) {
abstract class QM_Dispatcher {

	/**
	 * Outputter instances.
	 *
	 * @var array<string, QM_Output> Array of outputters.
	 */
	protected $outputters = array();

	/**
	 * Query Monitor plugin instance.
	 *
	 * @var QM_Plugin Plugin instance.
	 */
	protected $qm;

	/**
	 * @var string
	 */
	public $id = '';

	/**
	 * @var bool
	 */
	protected $ceased = false;

	public function __construct( QM_Plugin $qm ) {
		$this->qm = $qm;

		if ( ! defined( 'QM_COOKIE' ) ) {
			define( 'QM_COOKIE', 'wp-query_monitor_' . COOKIEHASH );
		}
		if ( ! defined( 'QM_EDITOR_COOKIE' ) ) {
			define( 'QM_EDITOR_COOKIE', 'wp-query_monitor_editor_' . COOKIEHASH );
		}

		add_action( 'init', array( $this, 'init' ) );

	}

	/**
	 * @return bool
	 */
	abstract public function is_active();

	/**
	 * @return bool
	 */
	final public function should_dispatch() {

		$e = error_get_last();

		# Don't dispatch if a fatal has occurred:
		if ( ! empty( $e ) && ( $e['type'] & QM_ERROR_FATALS ) ) {
			return false;
		}

		/**
		 * Allows users to disable this dispatcher.
		 *
		 * The dynamic portion of the hook name, `$this->id`, refers to the dispatcher ID.
		 *
		 * Possible filter names include:
		 *
		 *  - `qm/dispatch/html`
		 *  - `qm/dispatch/ajax`
		 *  - `qm/dispatch/redirect`
		 *  - `qm/dispatch/rest`
		 *  - `qm/dispatch/wp_die`
		 *
		 * @since 2.8.0
		 *
		 * @param bool $true Whether or not the dispatcher is enabled.
		 */
		if ( ! apply_filters( "qm/dispatch/{$this->id}", true ) ) {
			return false;
		}

		return $this->is_active();

	}

	/**
	 * @return void
	 */
	public function cease() {
		$this->ceased = true;

		add_filter( "qm/dispatch/{$this->id}", '__return_false' );
	}

	/**
	 * Processes and fetches the outputters for this dispatcher.
	 *
	 * @param string $outputter_id The outputter ID.
	 * @return array<string, QM_Output> Array of outputters.
	 */
	public function get_outputters( $outputter_id ) {
		$collectors = QM_Collectors::init();
		$collectors->process();

		/**
		 * Allows users to filter what outputs.
		 *
		 * The dynamic portion of the hook name, `$outputter_id`, refers to the outputter ID.
		 *
		 * @since 2.8.0
		 *
		 * @param array<string, QM_Output> $outputters Array of outputters.
		 * @param QM_Collectors            $collectors List of collectors.
		 */
		$this->outputters = apply_filters( "qm/outputter/{$outputter_id}", array(), $collectors );

		return $this->outputters;
	}

	/**
	 * @return void
	 */
	public function init() {
		if ( ! self::user_can_view() ) {
			do_action( 'qm/cease' );
			return;
		}

		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', 1 );
		}

		add_action( 'send_headers', 'nocache_headers' );
	}

	/**
	 * @return void
	 */
	protected function before_output() {
	}

	/**
	 * @return void
	 */
	protected function after_output() {
	}

	/**
	 * @return bool
	 */
	public static function user_can_view() {

		if ( ! did_action( 'plugins_loaded' ) ) {
			return false;
		}

		if ( current_user_can( 'view_query_monitor' ) ) {
			return true;
		}

		return self::user_verified();

	}

	/**
	 * @return bool
	 */
	public static function user_verified() {
		if ( isset( $_COOKIE[QM_COOKIE] ) ) { // phpcs:ignore
			return self::verify_cookie( wp_unslash( $_COOKIE[QM_COOKIE] ) ); // phpcs:ignore
		}
		return false;
	}

	/**
	 * @return string
	 */
	public static function editor_cookie() {
		if ( defined( 'QM_EDITOR_COOKIE' ) && isset( $_COOKIE[QM_EDITOR_COOKIE] ) ) { // phpcs:ignore
			return $_COOKIE[QM_EDITOR_COOKIE]; // phpcs:ignore
		}
		return '';
	}

	/**
	 * @param string $value
	 * @return bool
	 */
	public static function verify_cookie( $value ) {
		$old_user_id = wp_validate_auth_cookie( $value, 'logged_in' );
		if ( $old_user_id ) {
			return user_can( $old_user_id, 'view_query_monitor' );
		}
		return false;
	}

	/**
	 * Attempts to switch to the given locale.
	 *
	 * This is a wrapper around `switch_to_locale()` which is safe to call at any point, even
	 * before the `$wp_locale_switcher` global is initialised.
	 *
	 * @param string $locale The locale.
	 * @return bool True on success, false on failure.
	 */
	public static function switch_to_locale( $locale ) {
		global $wp_locale_switcher;

		if ( $wp_locale_switcher instanceof WP_Locale_Switcher ) {
			return switch_to_locale( $locale );
		}

		return false;
	}

	/**
	 * Attempts to restore the previous locale.
	 *
	 * This is a wrapper around `restore_previous_locale()` which is safe to call at any point, even
	 * before the `$wp_locale_switcher` global is initialised.
	 *
	 * @return string|false Locale on success, false on error.
	 */
	public static function restore_previous_locale() {
		global $wp_locale_switcher;

		if ( $wp_locale_switcher instanceof WP_Locale_Switcher ) {
			return restore_previous_locale();
		}

		return false;
	}
}
}
