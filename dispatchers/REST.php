<?php declare(strict_types = 1);
/**
 * REST API request dispatcher.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QM_Dispatcher_REST extends QM_Dispatcher {

	public $id = 'rest';

	public function __construct( QM_Plugin $qm ) {
		parent::__construct( $qm );

		add_filter( 'rest_post_dispatch', array( $this, 'filter_rest_post_dispatch' ), 1 );

	}

	/**
	 * Filters a REST API response in order to add QM's headers.
	 *
	 * @param WP_HTTP_Response $result  Result to send to the client. Usually a WP_REST_Response.
	 * @return WP_HTTP_Response Result to send to the client.
	 */
	public function filter_rest_post_dispatch( WP_HTTP_Response $result ) {

		if ( ! $this->should_dispatch() ) {
			return $result;
		}

		$this->before_output();

		/** @var array<string, QM_Output_Headers> $outputters */
		$outputters = $this->get_outputters( 'headers' );

		foreach ( $outputters as $output ) {
			$output->output();
		}

		$this->after_output();

		return $result;

	}

	/**
	 * @return void
	 */
	protected function before_output() {
		foreach ( (array) glob( $this->qm->plugin_path( 'output/headers/*.php' ) ) as $file ) {
			include_once $file;
		}
	}

	/**
	 * @return bool
	 */
	public function is_active() {

		# If the headers have already been sent then we can't do anything about it
		if ( headers_sent() ) {
			return false;
		}

		if ( ! defined( 'REST_REQUEST' ) || ! REST_REQUEST ) {
			return false;
		}

		if ( ! self::user_can_view() ) {
			return false;
		}

		return true;

	}

}

/**
 * @param array<string, QM_Dispatcher> $dispatchers
 * @param QM_Plugin $qm
 * @return array<string, QM_Dispatcher>
 */
function register_qm_dispatcher_rest( array $dispatchers, QM_Plugin $qm ) {
	$dispatchers['rest'] = new QM_Dispatcher_REST( $qm );
	return $dispatchers;
}

add_filter( 'qm/dispatchers', 'register_qm_dispatcher_rest', 10, 2 );
