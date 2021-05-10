<?php
/**
 * REST API request dispatcher.
 *
 * @package query-monitor
 */

defined( 'ABSPATH' ) || exit;

class QM_Dispatcher_REST extends QM_Dispatcher {

	public $id = 'rest';

	public function __construct( QM_Plugin $qm ) {
		parent::__construct( $qm );

		add_filter( 'rest_post_dispatch', array( $this, 'filter_rest_post_dispatch' ), 1, 3 );

	}

	/**
	 * Filters a REST API response in order to add QM's headers.
	 *
	 * @param WP_HTTP_Response $result  Result to send to the client. Usually a WP_REST_Response.
	 * @param WP_REST_Server   $server  Server instance.
	 * @param WP_REST_Request  $request Request used to generate the response.
	 * @return WP_HTTP_Response Result to send to the client.
	 */
	public function filter_rest_post_dispatch( WP_HTTP_Response $result, WP_REST_Server $server, WP_REST_Request $request ) {

		if ( ! $this->should_dispatch() ) {
			return $result;
		}

		$this->before_output();

		/* @var QM_Output_Headers[] */
		foreach ( $this->get_outputters( 'headers' ) as $id => $output ) {
			$output->output();
		}

		$this->after_output();

		return $result;

	}

	protected function before_output() {

		require_once $this->qm->plugin_path( 'output/Headers.php' );

		foreach ( glob( $this->qm->plugin_path( 'output/headers/*.php' ) ) as $file ) {
			include_once $file;
		}
	}

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

function register_qm_dispatcher_rest( array $dispatchers, QM_Plugin $qm ) {
	$dispatchers['rest'] = new QM_Dispatcher_REST( $qm );
	return $dispatchers;
}

add_filter( 'qm/dispatchers', 'register_qm_dispatcher_rest', 10, 2 );
