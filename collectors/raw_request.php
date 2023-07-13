<?php declare(strict_types = 1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @extends QM_DataCollector<QM_Data_Raw_Request>
 */
class QM_Collector_Raw_Request extends QM_DataCollector {

	public $id = 'raw_request';

	public function get_storage(): QM_Data {
		return new QM_Data_Raw_Request();
	}

	/**
	 * Extracts headers from a PHP-style $_SERVER array.
	 *
	 * From WP_REST_Server::get_headers()
	 *
	 * @param array<string, string> $server Associative array similar to `$_SERVER`.
	 * @return array<string, string> Headers extracted from the input.
	 */
	protected function get_headers( array $server ) {
		$headers = array();

		// CONTENT_* headers are not prefixed with HTTP_.
		$additional = array(
			'CONTENT_LENGTH' => true,
			'CONTENT_MD5' => true,
			'CONTENT_TYPE' => true,
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
	 *
	 * @return void
	 */
	public function process() {
		$request = array(
			'ip' => $_SERVER['REMOTE_ADDR'],
			'method' => strtoupper( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ),
			'scheme' => is_ssl() ? 'https' : 'http',
			'host' => wp_unslash( $_SERVER['HTTP_HOST'] ),
			'path' => wp_unslash( $_SERVER['REQUEST_URI'] ?? '/' ),
			'query' => wp_unslash( $_SERVER['QUERY_STRING'] ?? '' ),
			'headers' => $this->get_headers( wp_unslash( $_SERVER ) ),
		);

		ksort( $request['headers'] );

		$request['url'] = sprintf( '%s://%s%s', $request['scheme'], $request['host'], $request['path'] );

		$this->data->request = $request;

		$headers = array();
		$raw_headers = headers_list();
		foreach ( $raw_headers as $row ) {
			list( $key, $value ) = explode( ':', $row, 2 );
			$headers[ trim( $key ) ] = trim( $value );
		}

		ksort( $headers );

		$response = array(
			'status' => http_response_code(),
			'headers' => $headers,
		);

		$this->data->response = $response;
	}
}

/**
 * @param array<string, QM_Collector> $collectors
 * @param QueryMonitor $qm
 * @return array<string, QM_Collector>
 */
function register_qm_collector_raw_request( array $collectors, QueryMonitor $qm ) {
	$collectors['raw_request'] = new QM_Collector_Raw_Request();
	return $collectors;
}

add_filter( 'qm/collectors', 'register_qm_collector_raw_request', 10, 2 );
