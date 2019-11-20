<?php

class QM_Collector_Raw_Request extends QM_Collector {

	public $id = 'raw_request';

	/**
	 * Extracts headers from a PHP-style $_SERVER array.
	 *
	 * From WP_REST_Server::get_headers()
	 *
	 * @param array $server Associative array similar to `$_SERVER`.
	 * @return array Headers extracted from the input.
	 */
	protected function get_headers( array $server ) {
		$headers = array();

		// CONTENT_* headers are not prefixed with HTTP_.
		$additional = array(
			'CONTENT_LENGTH' => true,
			'CONTENT_MD5'    => true,
			'CONTENT_TYPE'   => true,
		);

		foreach ( $server as $key => $value ) {
			if ( strpos( $key, 'HTTP_' ) === 0 ) {
				$headers[ substr( $key, 5 ) ] = $value;
			} elseif ( isset( $additional[ $key ] ) ) {
				$headers[ $key ] = $value;
			}
		}

		return $headers;
	}

	/**
	 * Process request and response data.
	 */
	public function process() {
		$request = array(
			'ip'      => $_SERVER['REMOTE_ADDR'],
			'method'  => strtoupper( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ),
			'scheme'  => is_ssl() ? 'https' : 'http',
			'host'    => wp_unslash( $_SERVER['HTTP_HOST'] ),
			'path'    => isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/',
			'query'   => isset( $_SERVER['QUERY_STRING'] ) ? wp_unslash( $_SERVER['QUERY_STRING'] ) : '',
			'headers' => $this->get_headers( wp_unslash( $_SERVER ) ),
		);

		ksort( $request['headers'] );

		$request['url'] = sprintf( '%s://%s%s', $request['scheme'], $request['host'], $request['path'] );

		$this->data['request'] = $request;

		$headers = array();
		$raw_headers = headers_list();
		foreach ( $raw_headers as $row ) {
			list( $key, $value ) = explode( ':', $row, 2 );
			$headers[ trim( $key ) ] = trim( $value );
		}

		ksort( $headers );

		$response = array(
			'status'  => self::http_response_code(),
			'headers' => $headers,
		);

		$this->data['response'] = $response;
	}

	public static function http_response_code() {
		if ( is_callable( 'http_response_code' ) ) {
			// phpcs:ignore PHPCompatibility.FunctionUse.NewFunctions.http_response_codeFound
			return http_response_code();
		}

		return null;
	}
}

function register_qm_collector_raw_request( array $collectors, QueryMonitor $qm ) {
	$collectors['raw_request'] = new QM_Collector_Raw_Request();
	return $collectors;
}

add_filter( 'qm/collectors', 'register_qm_collector_raw_request', 10, 2 );
