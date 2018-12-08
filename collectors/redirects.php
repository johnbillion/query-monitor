<?php
/**
 * HTTP redirect collector.
 *
 * @package query-monitor
 */

class QM_Collector_Redirects extends QM_Collector {

	public $id = 'redirects';

	public function name() {
		return __( 'Redirects', 'query-monitor' );
	}

	public function __construct() {
		parent::__construct();
		add_filter( 'wp_redirect', array( $this, 'filter_wp_redirect' ), 999, 2 );
	}

	public function filter_wp_redirect( $location, $status ) {

		if ( ! $location ) {
			return $location;
		}

		$trace = new QM_Backtrace();

		$this->data['trace']    = $trace;
		$this->data['location'] = $location;
		$this->data['status']   = $status;

		return $location;

	}

}

# Load early in case a plugin is doing a redirect when it initialises instead of after the `plugins_loaded` hook
QM_Collectors::add( new QM_Collector_Redirects() );
