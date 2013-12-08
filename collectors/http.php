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

	var $id   = 'http';

	function name() {
		return __( 'HTTP Requests', 'query-monitor' );
	}

	function __construct() {

		parent::__construct();

		add_action( 'http_api_debug',      array( $this, 'http_debug' ),    99, 5 );
		add_filter( 'http_request_args',   array( $this, 'http_request' ),  99, 2 );
		add_filter( 'http_response',       array( $this, 'http_response' ), 99, 3 );
		# http://core.trac.wordpress.org/ticket/25747
		add_filter( 'pre_http_request',    array( $this, 'http_response' ), 99, 3 );

	}

	function http_request( array $args, $url ) {
		$m_start = microtime( true );
		$key = $m_start . $url;
		$this->data['http'][$key] = array(
			'url'   => $url,
			'args'  => $args,
			'start' => $m_start,
			'trace' => new QM_Backtrace
		);
		$args['_qm_key'] = $key;
		return $args;
	}

	function http_debug( $param, $action ) {

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
					$this->http_response( $response, $args, $url );

				break;

			case 'transports_list':
				# Nothing
				break;

		}

	}

	function http_response( $response, array $args, $url ) {
		$this->data['http'][$args['_qm_key']]['end']      = microtime( true );
		$this->data['http'][$args['_qm_key']]['response'] = $response;

		if ( is_wp_error( $response ) ) {
			$this->data['errors']['error'][] = $args['_qm_key'];
		} else {
			if ( intval( wp_remote_retrieve_response_code( $response ) ) >= 400 )
				$this->data['errors']['warning'][] = $args['_qm_key'];
		}
		return $response;
	}

}

function register_qm_http( array $qm ) {
	$qm['http'] = new QM_Collector_HTTP;
	return $qm;
}

add_filter( 'query_monitor_collectors', 'register_qm_http', 100 );
