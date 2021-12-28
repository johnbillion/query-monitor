<?php
/**
 * HTTP redirect collector.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QM_Collector_Redirects extends QM_Collector {

	public $id = 'redirects';

	/**
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		add_filter( 'wp_redirect', array( $this, 'filter_wp_redirect' ), 9999, 2 );
	}

	/**
	 * @return void
	 */
	public function tear_down() {
		remove_filter( 'wp_redirect', array( $this, 'filter_wp_redirect' ), 9999 );

		parent::tear_down();
	}

	/**
	 * @param string $location
	 * @param int $status
	 * @return string
	 */
	public function filter_wp_redirect( $location, $status ) {

		if ( ! $location ) {
			return $location;
		}

		$trace = new QM_Backtrace( array(
			'ignore_hook' => array(
				current_filter() => true,
			),
			'ignore_func' => array(
				'wp_redirect' => true,
			),
		) );

		$this->data['trace'] = $trace;
		$this->data['location'] = $location;
		$this->data['status'] = $status;

		return $location;

	}

}

# Load early in case a plugin is doing a redirect when it initialises instead of after the `plugins_loaded` hook
QM_Collectors::add( new QM_Collector_Redirects() );
