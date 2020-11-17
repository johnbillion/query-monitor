<?php
/**
 * WP error collector.
 *
 * @package query-monitor
 */

class QM_Collector_WP_Errors extends QM_Collector {

	public $id = 'wp_errors';

	public function __construct() {
		parent::__construct();

		add_action( 'wp_error_added', array( $this, 'action_wp_error_added' ), 10, 4 );
		add_action( 'is_wp_error_instance', array( $this, 'action_is_wp_error_instance' ) );
	}

	public function action_wp_error_added( $code, $message, $data, WP_Error $wp_error ) {
		$id = spl_object_hash( $wp_error );

		$this->data['errors'][ $id ] = array(
			'error' => $wp_error,
			'trace' => new QM_Backtrace( array(
				'ignore_current_filter' => false,
				'ignore_frames'         => 6,
			) ),
		);
	}

	public function action_is_wp_error_instance( WP_Error $wp_error ) {
		$id = spl_object_hash( $wp_error );

		if ( ! isset( $this->data['checked'][ $id ] ) ) {
			$this->data['checked'][ $id ] = array();
		}

		$this->data['checked'][ $id ][] = array(
			'error' => $wp_error,
			'trace' => new QM_Backtrace( array(
				'ignore_current_filter' => false,
				'ignore_frames'         => 5,
			) ),
		);
	}
}

# Load early to catch early errors
QM_Collectors::add( new QM_Collector_WP_Errors() );
