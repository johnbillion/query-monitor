<?php
/**
 * Ajax request dispatcher.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QM_Dispatcher_AJAX extends QM_Dispatcher {

	public $id = 'ajax';

	public function __construct( QM_Plugin $qm ) {
		parent::__construct( $qm );

		// This dispatcher needs to run on a priority lower than 1 so it can output
		// its headers before wp_ob_end_flush_all() flushes all the output buffers:
		// https://github.com/WordPress/wordpress-develop/blob/0a3a3c5119897c6d551a42ae9b5dbfa4f576f2c9/src/wp-includes/default-filters.php#L382
		add_action( 'shutdown', array( $this, 'dispatch' ), 0 );
	}

	/**
	 * @return void
	 */
	public function init() {

		if ( ! self::user_can_view() ) {
			return;
		}

		if ( QM_Util::is_ajax() ) {
			// Start an output buffer for Ajax requests so headers can be output at the end:
			ob_start();
		}

		parent::init();
	}

	/**
	 * @return void
	 */
	public function dispatch() {

		if ( ! $this->should_dispatch() ) {
			return;
		}

		$this->before_output();

		foreach ( $this->get_outputters( 'headers' ) as $id => $output ) {
			$output->output();
		}

		$this->after_output();

	}

	/**
	 * @return void
	 */
	protected function before_output() {
		foreach ( glob( $this->qm->plugin_path( 'output/headers/*.php' ) ) as $file ) {
			require_once $file;
		}
	}

	/**
	 * @return void
	 */
	protected function after_output() {

		# flush once, because we're nice
		if ( ob_get_length() ) {
			ob_flush();
		}

	}

	/**
	 * @return bool
	 */
	public function is_active() {

		if ( ! QM_Util::is_ajax() ) {
			return false;
		}

		if ( ! self::user_can_view() ) {
			return false;
		}

		# If the headers have already been sent then we can't do anything about it
		if ( headers_sent() ) {
			return false;
		}

		# Don't process if the minimum required actions haven't fired:
		if ( is_admin() ) {
			if ( ! did_action( 'admin_init' ) ) {
				return false;
			}
		} else {
			if ( ! did_action( 'wp' ) ) {
				return false;
			}
		}

		return true;

	}

}

/**
 * @param array<string, QM_Dispatcher> $dispatchers
 * @param QM_Plugin $qm
 * @return array<string, QM_Dispatcher>
 */
function register_qm_dispatcher_ajax( array $dispatchers, QM_Plugin $qm ) {
	$dispatchers['ajax'] = new QM_Dispatcher_AJAX( $qm );
	return $dispatchers;
}

add_filter( 'qm/dispatchers', 'register_qm_dispatcher_ajax', 10, 2 );
