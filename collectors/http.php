<?php declare(strict_types = 1);
/**
 * HTTP API request collector.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @extends QM_DataCollector<QM_Data_HTTP>
 */
class QM_Collector_HTTP extends QM_DataCollector {

	/**
	 * @var string
	 */
	public $id = 'http';

	/**
	 * @var mixed|null
	 */
	private $info = null;

	/**
	 * @var array<string, array<string, mixed>>
	 * @phpstan-var array<string, array{
	 *   url: string,
	 *   start: float,
	 *   args: array<string, mixed>,
	 *   filtered_trace: list<array<string, mixed>>,
	 *   component: QM_Component,
	 * }>
	 */
	private $http_requests = array();

	/**
	 * @var array<string, array<string, mixed>>
	 * @phpstan-var array<string, array{
	 *   end: float,
	 *   args: array<string, mixed>,
	 *   response: mixed[]|WP_Error,
	 *   info: array<string, mixed>|null,
	 * }>
	 */
	private $http_responses = array();

	public function get_storage(): QM_Data {
		return new QM_Data_HTTP();
	}

	/**
	 * @return void
	 */
	public function set_up() {

		parent::set_up();

		add_filter( 'http_request_args', array( $this, 'filter_http_request_args' ), 9999, 2 );
		add_filter( 'pre_http_request', array( $this, 'filter_pre_http_request' ), 9999, 3 );
		add_action( 'http_api_debug', array( $this, 'action_http_api_debug' ), 9999, 5 );

		add_action( 'requests-curl.after_request', array( $this, 'action_curl_after_request' ), 9999, 2 );
		add_action( 'requests-fsockopen.after_request', array( $this, 'action_fsockopen_after_request' ), 9999, 2 );

	}

	/**
	 * @return void
	 */
	public function tear_down() {
		remove_filter( 'http_request_args', array( $this, 'filter_http_request_args' ), 9999 );
		remove_filter( 'pre_http_request', array( $this, 'filter_pre_http_request' ), 9999 );
		remove_action( 'http_api_debug', array( $this, 'action_http_api_debug' ), 9999 );

		remove_action( 'requests-curl.before_request', array( $this, 'action_curl_before_request' ), 9999 );
		remove_action( 'requests-curl.after_request', array( $this, 'action_curl_after_request' ), 9999 );
		remove_action( 'requests-fsockopen.before_request', array( $this, 'action_fsockopen_before_request' ), 9999 );
		remove_action( 'requests-fsockopen.after_request', array( $this, 'action_fsockopen_after_request' ), 9999 );

		parent::tear_down();
	}

	/**
	 * @return array<int, string>
	 */
	public function get_concerned_actions() {
		$actions = array(
			'http_api_curl',
			'requests-multiple.request.complete',
			'requests-request.progress',
			'requests-transport.internal.parse_error',
			'requests-transport.internal.parse_response',
		);
		$transports = array(
			'requests',
			'curl',
			'fsockopen',
		);

		foreach ( $transports as $transport ) {
			$actions[] = "requests-{$transport}.after_headers";
			$actions[] = "requests-{$transport}.after_multi_exec";
			$actions[] = "requests-{$transport}.after_request";
			$actions[] = "requests-{$transport}.after_send";
			$actions[] = "requests-{$transport}.before_multi_add";
			$actions[] = "requests-{$transport}.before_multi_exec";
			$actions[] = "requests-{$transport}.before_parse";
			$actions[] = "requests-{$transport}.before_redirect";
			$actions[] = "requests-{$transport}.before_redirect_check";
			$actions[] = "requests-{$transport}.before_request";
			$actions[] = "requests-{$transport}.before_send";
			$actions[] = "requests-{$transport}.remote_host_path";
			$actions[] = "requests-{$transport}.remote_socket";
		}

		return $actions;
	}

	/**
	 * @return array<int, string>
	 */
	public function get_concerned_filters() {
		return array(
			'block_local_requests',
			'http_request_args',
			'http_response',
			'https_local_ssl_verify',
			'https_ssl_verify',
			'pre_http_request',
			'use_curl_transport',
			'use_streams_transport',
		);
	}

	/**
	 * @return array<int, string>
	 */
	public function get_concerned_constants() {
		return array(
			'WP_PROXY_HOST',
			'WP_PROXY_PORT',
			'WP_PROXY_USERNAME',
			'WP_PROXY_PASSWORD',
			'WP_PROXY_BYPASS_HOSTS',
			'WP_HTTP_BLOCK_EXTERNAL',
			'WP_ACCESSIBLE_HOSTS',
		);
	}

	/**
	 * Filter the arguments used in an HTTP request.
	 *
	 * Used to log the request, and to add the logging key to the arguments array.
	 *
	 * @param  array<string, mixed> $args HTTP request arguments.
	 * @param  string               $url  The request URL.
	 * @return array<string, mixed> HTTP request arguments.
	 */
	public function filter_http_request_args( array $args, $url ) {
		$trace = new QM_Backtrace( array(
			'ignore_hook' => array(
				current_filter() => true,
			),
			'ignore_class' => array(
				'WP_Http' => true,
			),
			'ignore_func' => array(
				'wp_safe_remote_request' => true,
				'wp_safe_remote_get' => true,
				'wp_safe_remote_post' => true,
				'wp_safe_remote_head' => true,
				'wp_remote_request' => true,
				'wp_remote_get' => true,
				'wp_remote_post' => true,
				'wp_remote_head' => true,
				'wp_remote_fopen' => true,
				'download_url' => true,
				'vip_safe_wp_remote_get' => true,
				'vip_safe_wp_remote_request' => true,
				'wpcom_vip_file_get_contents' => true,
			),
		) );

		if ( isset( $args['_qm_key'], $this->http_requests[ $args['_qm_key'] ] ) ) {
			// Something has triggered another HTTP request from within the `pre_http_request` filter
			// (eg. WordPress Beta Tester does this). This allows for one level of nested queries.
			$args['_qm_original_key'] = $args['_qm_key'];
			$start = $this->http_requests[ $args['_qm_key'] ]['start'];
		} else {
			$start = microtime( true );
		}

		$key = microtime( true ) . $url;
		$this->http_requests[ $key ] = array(
			'url' => $url,
			'args' => $args,
			'start' => $start,
			'filtered_trace' => $trace->get_filtered_trace(),
			'component' => $trace->get_component(),
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
	 * @param false|mixed[]|WP_Error $response The preemptive HTTP response. Default false.
	 * @param array<string, mixed>   $args     HTTP request arguments.
	 * @param string                 $url      The request URL.
	 * @return false|mixed[]|WP_Error The preemptive HTTP response.
	 */
	public function filter_pre_http_request( $response, array $args, $url ) {

		// All is well:
		if ( false === $response ) {
			return $response;
		}

		// Something's filtering the response, so we'll log it
		$this->log_http_response( $response, $args, $url );

		return $response;
	}

	/**
	 * Debugging action for the HTTP API.
	 *
	 * @param mixed                $response A parameter which varies depending on $action.
	 * @param string               $action   The debug action. Currently one of 'response' or 'transports_list'.
	 * @param string               $class    The HTTP transport class name.
	 * @param array<string, mixed> $args     HTTP request arguments.
	 * @param string               $url      The request URL.
	 * @return void
	 */
	public function action_http_api_debug( $response, $action, $class, $args, $url ) {
		switch ( $action ) {

			case 'response':
				$this->log_http_response( $response, $args, $url );

				break;

			case 'transports_list':
				# Nothing
				break;

		}

	}

	/**
	 * @param mixed $headers
	 * @param mixed[] $info
	 * @return void
	 */
	public function action_curl_after_request( $headers, array $info = null ) {
		$this->info = $info;
	}

	/**
	 * @param mixed $headers
	 * @param mixed[] $info
	 * @return void
	 */
	public function action_fsockopen_after_request( $headers, array $info = null ) {
		$this->info = $info;
	}

	/**
	 * Log an HTTP response.
	 *
	 * @param mixed[]|WP_Error     $response The HTTP response.
	 * @param array<string, mixed> $args     HTTP request arguments.
	 * @param string               $url      The request URL.
	 * @return void
	 */
	public function log_http_response( $response, array $args, $url ) {
		/** @var string */
		$key = $args['_qm_key'];

		$http_response = array(
			'end' => microtime( true ),
			'response' => $response,
			'args' => $args,
			'info' => $this->info,
		);

		if ( isset( $args['_qm_original_key'] ) ) {
			/** @var string */
			$original_key = $args['_qm_original_key'];
			$this->http_responses[ $original_key ]['end'] = $this->http_requests[ $original_key ]['start'];
			$this->http_responses[ $original_key ]['response'] = new WP_Error( 'http_request_not_executed', sprintf(
				/* translators: %s: Hook name */
				__( 'Request not executed due to a filter on %s', 'query-monitor' ),
				'pre_http_request'
			) );
		}

		$this->http_responses[ $key ] = $http_response;

		$this->info = null;
	}

	/**
	 * @return void
	 */
	public function process() {
		$this->data->ltime = 0;

		if ( empty( $this->http_requests ) ) {
			return;
		}

		/**
		 * List of HTTP API error codes to ignore.
		 *
		 * @since 2.7.0
		 *
		 * @param array $http_errors Array of HTTP errors.
		 */
		$silent = apply_filters( 'qm/collect/silent_http_errors', array(
			'http_request_not_executed',
			'airplane_mode_enabled',
		) );

		$home_host = (string) parse_url( home_url(), PHP_URL_HOST );

		foreach ( $this->http_requests as $key => $request ) {
			$response = $this->http_responses[ $key ];

			if ( empty( $response['response'] ) ) {
				// Timed out
				$response['response'] = new WP_Error( 'http_request_timed_out', __( 'Request timed out', 'query-monitor' ) );
				$response['end'] = floatval( $request['start'] + $response['args']['timeout'] );
			}

			if ( $response['response'] instanceof WP_Error ) {
				if ( ! in_array( $response['response']->get_error_code(), $silent, true ) ) {
					$this->data->errors['alert'][] = $key;
				}
				$type = 'error';
			} elseif ( ! $response['args']['blocking'] ) {
				$type = 'non-blocking';
			} else {
				$code = intval( wp_remote_retrieve_response_code( $response['response'] ) );
				$type = "http:{$code}";
				if ( ( $code >= 400 ) && ( 'HEAD' !== $request['args']['method'] ) ) {
					$this->data->errors['warning'][] = $key;
				}
			}

			$ltime = ( $response['end'] - $request['start'] );
			$redirected_to = null;

			if ( isset( $response['info'] ) && is_string( $response['info']['url'] ) && ! empty( $response['info']['url'] ) ) {
				// Ignore query variables when detecting a redirect.
				$from = untrailingslashit( preg_replace( '#\?[^$]+$#', '', $request['url'] ) );
				$to = untrailingslashit( preg_replace( '#\?[^$]+$#', '', $response['info']['url'] ) );
				if ( $from !== $to ) {
					$redirected_to = $response['info']['url'];
				}
			}

			$this->data->ltime += $ltime;

			$host = (string) parse_url( $request['url'], PHP_URL_HOST );
			$local = ( $host === $home_host );

			$this->log_type( $type );
			$this->log_component( $request['component'], $ltime, $type );
			$this->data->http[ $key ] = array(
				'args' => $response['args'],
				'component' => $request['component'],
				'filtered_trace' => $request['filtered_trace'],
				'info' => $response['info'],
				'local' => $local,
				'ltime' => $ltime,
				'redirected_to' => $redirected_to,
				'response' => $response['response'],
				'type' => $type,
				'url' => $request['url'],
			);
		}

	}

}

# Load early in case a plugin is doing an HTTP request when it initialises instead of after the `plugins_loaded` hook
QM_Collectors::add( new QM_Collector_HTTP() );
