<?php
/*
Copyright 2013 John Blackbourn

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

*/

class QM_Collector_HTTP extends QM_Collector {

	public $id = 'http';

	public function name() {
		return __( 'HTTP Requests', 'query-monitor' );
	}

	public function __construct() {

		parent::__construct();

		add_action( 'http_api_debug',    array( $this, 'action_http_debug' ),    99, 5 );
		add_filter( 'http_request_args', array( $this, 'filter_http_request' ),  99, 2 );
		add_filter( 'http_response',     array( $this, 'filter_http_response' ), 99, 3 );
		# http://core.trac.wordpress.org/ticket/25747
		add_filter( 'pre_http_request',  array( $this, 'filter_pre_http_request' ), 99, 3 );

	}

	public function filter_http_request( array $args, $url ) {
		$trace = new QM_Backtrace;
		if ( isset( $args['_qm_key'] ) ) {
			// Something has triggered another HTTP request from within the `pre_http_request` filter
			// (eg. WordPress Beta Tester does this). This allows for one level of nested queries.
			$args['_qm_original_key'] = $args['_qm_key'];
			$m_start = $this->data['http'][$args['_qm_key']]['start'];
		} else {
			$m_start = microtime( true );
		}
		$x_start = microtime( true );
		$key = $x_start . $url;
		$this->data['http'][$key] = array(
			'url'   => $url,
			'args'  => $args,
			'start' => $m_start,
			'trace' => $trace,
		);
		$args['_qm_key'] = $key;
		return $args;
	}

	public function action_http_debug( $param, $action ) {

		switch ( $action ) {

			case 'response':

				$fga = func_get_args();

				list( $response, $action, $class ) = $fga;

				# http://core.trac.wordpress.org/ticket/18732
				if ( isset( $fga[3] ) )
					$args = $fga[3];
				if ( isset( $fga[4] ) )
					$url = $fga[4];
				if ( !isset( $args['_qm_key'] ) )
					return;

				if ( !empty( $class ) )
					$this->data['http'][$args['_qm_key']]['transport'] = str_replace( 'wp_http_', '', strtolower( $class ) );
				else
					$this->data['http'][$args['_qm_key']]['transport'] = false;

				if ( is_wp_error( $response ) )
					$this->filter_http_response( $response, $args, $url );

				break;

			case 'transports_list':
				# Nothing
				break;

		}

	}

	public function filter_pre_http_request( $response, array $args, $url ) {

		// All is well:
		if ( false === $response ) {
			return $response;
		}

		// Something has filtered `pre_http_request` and short-circuited the request.
		$this->data['http'][$args['_qm_key']]['end']      = $this->data['http'][$args['_qm_original_key']]['start'];
		$this->data['http'][$args['_qm_key']]['response'] = new WP_Error( 'http_request_not_executed', __( 'Request not executed due to a filter on pre_http_request', 'query-monitor' ) );

		return $response;
	}

	public function filter_http_response( $response, array $args, $url ) {
		$this->data['http'][$args['_qm_key']]['end']      = microtime( true );
		$this->data['http'][$args['_qm_key']]['response'] = $response;
		if ( isset( $args['_qm_original_key'] ) ) {
			$this->data['http'][$args['_qm_original_key']]['end']      = $this->data['http'][$args['_qm_original_key']]['start'];
			$this->data['http'][$args['_qm_original_key']]['response'] = new WP_Error( 'http_request_not_executed', __( 'Request not executed due to a filter on pre_http_request', 'query-monitor' ) );
		}

		return $response;
	}

	public function process() {

		if ( ! isset( $this->data['http'] ) )
			return;

		foreach ( $this->data['http'] as $key => & $http ) {

			if ( !isset( $http['response'] ) ) {
				// Timed out
				$http['response'] = new WP_Error( 'http_request_timed_out', __( 'Request timed out', 'query-monitor' ) );
				$http['end']      = floatval( $http['start'] + $http['args']['timeout'] );
			}

			if ( is_wp_error( $http['response'] ) ) {
				if ( 'http_request_not_executed' != $http['response']->get_error_code() )
					$this->data['errors']['error'][] = $key;
			} else {
				if ( intval( wp_remote_retrieve_response_code( $http['response'] ) ) >= 400 )
					$this->data['errors']['warning'][] = $key;
			}

		}

	}

}

function register_qm_collector_http( array $qm ) {
	$qm['http'] = new QM_Collector_HTTP;
	return $qm;
}

add_filter( 'query_monitor_collectors', 'register_qm_collector_http', 100 );
