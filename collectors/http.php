<?php
/*
Copyright 2014 John Blackbourn

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

		add_filter( 'http_request_args', array( $this, 'filter_http_request_args' ), 99, 2 );
		add_filter( 'pre_http_request',  array( $this, 'filter_pre_http_request' ), 99, 3 );
		add_action( 'http_api_debug',    array( $this, 'action_http_api_debug' ), 99, 5 );
		add_filter( 'http_response',     array( $this, 'filter_http_response' ), 99, 3 );

	}

	/**
	 * Filter the arguments used in an HTTP request.
	 *
	 * Used to log the request, and to add the logging key to the arguments array.
	 * 
	 * @param  array  $args HTTP request arguments.
	 * @param  string $url  The request URL.
	 * @return array        HTTP request arguments.
	 */
	public function filter_http_request_args( array $args, $url ) {
		$trace = new QM_Backtrace;
		if ( isset( $args['_qm_key'] ) ) {
			// Something has triggered another HTTP request from within the `pre_http_request` filter
			// (eg. WordPress Beta Tester does this). This allows for one level of nested queries.
			$args['_qm_original_key'] = $args['_qm_key'];
			$start = $this->data['http'][$args['_qm_key']]['start'];
		} else {
			$start = microtime( true );
		}
		$key = microtime( true ) . $url;
		$this->data['http'][$key] = array(
			'url'   => $url,
			'args'  => $args,
			'start' => $start,
			'trace' => $trace,
		);
		$args['_qm_key'] = $key;
		return $args;
	}

	/**
	 * Log the HTTP request's response if it's being short-circuited by another plugin.
	 * This is necessary due to https://core.trac.wordpress.org/ticket/25747
	 *
	 * $response should be one of boolean false, an array, or a `WP_Error`, but be aware that plugins
	 * which short-circuit the request using this filter may (incorrectly) return data of another type.
	 *
	 * @param bool|array|WP_Error $response The preemptive HTTP response. Default false.
	 * @param array               $args     HTTP request arguments.
	 * @param string              $url      The request URL.
	 * @return bool|array|WP_Error          The preemptive HTTP response.
	 */
	public function filter_pre_http_request( $response, array $args, $url ) {

		// All is well:
		if ( false === $response ) {
			return $response;
		}

		// Something's filtering the response, so we'll log it
		$this->filter_http_response( $response, $args, $url );

		return $response;
	}

	/**
	 * Debugging action for the HTTP API.
	 * 
	 * @param mixed  $response A parameter which varies depending on $action.
	 * @param string $action   The debug action. Currently one of 'response' or 'transports_list'.
	 * @param string $class    The HTTP transport class name.
	 * @param array  $args     HTTP request arguments.
	 * @param string $url      The request URL.
	 */
	public function action_http_api_debug( $response, $action, $class = null, $args = null, $url = null ) {

		switch ( $action ) {

			case 'response':

				# https://core.trac.wordpress.org/ticket/18732
				if ( empty( $args ) or empty( $args['_qm_key'] ) ) {
					return;
				}

				if ( !empty( $class ) ) {
					$this->data['http'][$args['_qm_key']]['transport'] = str_replace( 'wp_http_', '', strtolower( $class ) );
				} else {
					$this->data['http'][$args['_qm_key']]['transport'] = null;
				}

				if ( is_wp_error( $response ) ) {
					$this->filter_http_response( $response, $args, $url );
				}

				break;

			case 'transports_list':
				# Nothing
				break;

		}

	}

	/**
	 * Filter the HTTP response in order to log the response.
	 *
	 * @param array|WP_Error $response The HTTP response.
	 * @param array          $args     HTTP request arguments.
	 * @param string         $url      The request URL.
	 * @return array|WP_Error          The HTTP response.
	 */
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

		foreach ( array(
			'WP_PROXY_HOST',
			'WP_PROXY_PORT',
			'WP_PROXY_USERNAME',
			'WP_PROXY_PASSWORD',
			'WP_PROXY_BYPASS_HOSTS',
		) as $var ) {
			if ( defined( $var ) and constant( $var ) ) {
				$this->data['vars'][$var] = constant( $var );
			}
		}

		if ( ! isset( $this->data['http'] ) ) {
			return;
		}

		$silent = apply_filters( 'query_monitor_silent_http_error_codes', array(
			'http_request_not_executed',
			'airplane_mode_enabled'
		) );

		foreach ( $this->data['http'] as $key => & $http ) {

			if ( !isset( $http['response'] ) ) {
				// Timed out
				$http['response'] = new WP_Error( 'http_request_timed_out', __( 'Request timed out', 'query-monitor' ) );
				$http['end']      = floatval( $http['start'] + $http['args']['timeout'] );
			}

			if ( is_wp_error( $http['response'] ) ) {
				if ( !in_array( $http['response']->get_error_code(), $silent ) ) {
					$this->data['errors']['error'][] = $key;
				}
			} else {
				if ( intval( wp_remote_retrieve_response_code( $http['response'] ) ) >= 400 ) {
					$this->data['errors']['warning'][] = $key;
				}
			}

			$http['ltime'] = ( $http['end'] - $http['start'] );

		}

	}

}

function register_qm_collector_http( array $qm ) {
	$qm['http'] = new QM_Collector_HTTP;
	return $qm;
}

add_filter( 'query_monitor_collectors', 'register_qm_collector_http', 100 );
