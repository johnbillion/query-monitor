<?php
/**
 * HTTP redirect dispatcher.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QM_Dispatcher_Redirect extends QM_Dispatcher {

	public $id = 'redirect';

	public function __construct( QM_Plugin $qm ) {
		parent::__construct( $qm );

		add_filter( 'wp_redirect', array( $this, 'filter_wp_redirect' ), 9999, 2 );

	}

	/**
	 * Filters a redirect location in order to output QM's headers.
	 *
	 * @param string $location The path to redirect to.
	 * @param int    $status   Status code to use.
	 * @return string
	 */
	public function filter_wp_redirect( $location, $status ) {

		if ( ! $this->should_dispatch() ) {
			return $location;
		}

		$this->before_output();

		/* @var QM_Output_Headers[] */
		foreach ( $this->get_outputters( 'headers' ) as $id => $output ) {
			$output->output();
		}

		$this->after_output();

		return $location;

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
	 * @return bool
	 */
	public function is_active() {

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
			if ( ! ( did_action( 'wp' ) || did_action( 'login_init' ) ) ) {
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
function register_qm_dispatcher_redirect( array $dispatchers, QM_Plugin $qm ) {
	$dispatchers['redirect'] = new QM_Dispatcher_Redirect( $qm );
	return $dispatchers;
}

add_filter( 'qm/dispatchers', 'register_qm_dispatcher_redirect', 10, 2 );
