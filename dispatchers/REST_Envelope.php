<?php
/**
 * REST API enveloped request dispatcher.
 *
 * @package query-monitor
 */

class QM_Dispatcher_REST_Envelope extends QM_Dispatcher {

	public $id = 'rest_envelope';

	public function __construct( QM_Plugin $qm ) {
		parent::__construct( $qm );

		add_filter( 'rest_envelope_response', array( $this, 'filter_rest_envelope_response' ), 999, 2 );
	}

	/**
	 * Filters the enveloped form of a REST API response to add QM's data.
	 *
	 * @param array<string, mixed> $envelope Envelope data.
	 * @param WP_REST_Response     $response Original response data.
	 * @return array<string, mixed> Envelope data.
	 */
	public function filter_rest_envelope_response( array $envelope, WP_REST_Response $response ) {
		if ( ! $this->should_dispatch() ) {
			return $envelope;
		}

		$data = array();

		$this->before_output();

		/* @var QM_Output_Raw[] */
		foreach ( $this->get_outputters( 'raw' ) as $id => $output ) {
			$data[ $id ] = $output->get_output();
		}

		$this->after_output();

		$envelope['qm'] = $data;

		return $envelope;
	}

	/**
	 * @return void
	 */
	protected function before_output() {
		foreach ( glob( $this->qm->plugin_path( 'output/raw/*.php' ) ) as $file ) {
			include_once $file;
		}
	}

	/**
	 * @return bool
	 */
	public function is_active() {
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
function register_qm_dispatcher_rest_envelope( array $dispatchers, QM_Plugin $qm ) {
	$dispatchers['rest_envelope'] = new QM_Dispatcher_REST_Envelope( $qm );
	return $dispatchers;
}

add_filter( 'qm/dispatchers', 'register_qm_dispatcher_rest_envelope', 10, 2 );
