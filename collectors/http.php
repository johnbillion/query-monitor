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
 *
 * @phpstan-type HTTP_API_Response_Shape array{
 *   headers: \WpOrg\Requests\Utility\CaseInsensitiveDictionary,
 *   body: string,
 *   response: array{
 *     code: int|false,
 *     message: string|false,
 *   },
 *   cookies: array<int, \WP_Http_Cookie>,
 *   filename: string|null,
 *   http_response: \WP_HTTP_Requests_Response|null,
 * }
 */
class QM_Collector_HTTP extends QM_DataCollector {

	/**
	 * @var string
	 */
	public $id = 'http';

	/**
	 * @TODO trim this down to only the data we need
	 * @var mixed|null
	 */
	private $info = null;

	/**
	 * @var array<string, array<string, mixed>>
	 * @phpstan-var array<string, array{
	 *   url: string,
	 *   start: float,
	 *   args: array<string, mixed>,
	 *   trace: QM_Backtrace,
	 * }>
	 */
	private $http_requests = array();

	/**
	 * @var array<string, array<string, mixed>>
	 * @phpstan-var array<string, array{
	 *   end: float,
	 *   args: array<string, mixed>,
	 *   result: QM_Data_HTTP_Response|WP_Error,
	 *   info: array<string, mixed>|null,
	 *   intercepted: bool,
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

		remove_action( 'requests-curl.after_request', array( $this, 'action_curl_after_request' ), 9999 );
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
			'http_allowed_safe_ports',
			'http_headers_useragent',
			'http_request_args',
			'http_request_host_is_external',
			'http_request_redirection_count',
			'http_request_reject_unsafe_urls',
			'http_request_timeout',
			'http_request_version',
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
			// (eg. WordPress Beta Tester and FAIR do this). This allows for one level of nested queries.
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
			'trace' => $trace,
		);
		$args['_qm_key'] = $key;
		return $args;
	}

	/**
	 * Log the HTTP request's response if it's being short-circuited by another plugin.
	 *
	 * `$response` should be one of boolean false, an array, or a `WP_Error`, but be aware that plugins
	 * which short-circuit the request using this filter may (incorrectly) return data of another type.
	 *
	 * @param false|mixed[]|WP_Error $response The preemptive HTTP response. Default false.
	 * @param array<string, mixed>   $args     HTTP request arguments.
	 * @param string                 $url      The request URL.
	 *
	 * @phpstan-param false|HTTP_API_Response_Shape|WP_Error $response
	 *
	 * @return false|mixed[]|WP_Error The preemptive HTTP response.
	 */
	public function filter_pre_http_request( $response, array $args, $url ) {
		if ( is_array( $response ) || ( $response instanceof WP_Error ) ) {
			// Something's filtering the response, so we'll log it
			$this->log_http_response( $response, $args, $url, true );
		}

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
		if ( $action === 'response' ) {
			$this->log_http_response( $response, $args, $url );
		}
	}

	/**
	 * @param mixed $headers
	 * @param mixed[] $info
	 * @return void
	 */
	public function action_curl_after_request( $headers, ?array $info = null ) {
		$this->info = $info;
	}

	/**
	 * @param mixed $headers
	 * @param mixed[] $info
	 * @return void
	 */
	public function action_fsockopen_after_request( $headers, ?array $info = null ) {
		$this->info = $info;
	}

	/**
	 * Log an HTTP response.
	 *
	 * @param mixed[]|WP_Error     $response    The HTTP response.
	 * @param array<string, mixed> $args        HTTP request arguments.
	 * @param string               $url         The request URL.
	 * @param bool                 $intercepted Whether the request was intercepted and short-circuited by a filter.
	 *
	 * @phpstan-param HTTP_API_Response_Shape|WP_Error $response
	 *
	 * @return void
	 */
	public function log_http_response( $response, array $args, $url, bool $intercepted = false ) {
		/** @var string */
		$key = $args['_qm_key'];

		if ( $response instanceof WP_Error ) {
			$response_data = $response;
		} else {
			$response_data = new QM_Data_HTTP_Response();
			$response_data->code = (int) $response['response']['code'];
			$response_data->message = (string) $response['response']['message'];
		}

		$http_response = array(
			'end' => microtime( true ),
			'result' => $response_data,
			'args' => $args,
			'info' => $this->info,
			'intercepted' => $intercepted,
		);

		if ( isset( $args['_qm_original_key'] ) ) {
			/** @var string $original_key */
			$original_key = $args['_qm_original_key'];
			$this->http_responses[ $original_key ] = array(
				'end' => $this->http_requests[ $original_key ]['start'],
				'result' => new WP_Error( 'http_request_not_executed' ),
				'args' => $args,
				'info' => null,
				'intercepted' => false,
			);
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
			if ( isset( $this->http_responses[ $key ] ) ) {
				$response = $this->http_responses[ $key ];
			} else {
				// Timed out
				$response = array(
					'end' => floatval( $request['start'] + $request['args']['timeout'] ),
					'args' => $request['args'],
					'result' => new WP_Error( 'http_request_timed_out' ),
					'info' => null,
					'intercepted' => false,
				);
			}

			if ( $response['result'] instanceof WP_Error ) {
				if ( ! in_array( $response['result']->get_error_code(), $silent, true ) ) {
					$this->data->errors['alert'][] = $key;
				}
				$type = 'error';
			} elseif ( ! $response['args']['blocking'] ) {
				$type = 'non-blocking';
			} else {
				$code = $response['result']->code;
				$type = "HTTP {$code}";
				if ( ( $code >= 400 ) && ( 'HEAD' !== $request['args']['method'] ) ) {
					$this->data->errors['warning'][] = $key;
				}
			}

			$ltime = ( $response['end'] - $request['start'] );
			$redirected_to = null;

			if ( ! empty( $response['info']['url'] ) && is_string( $response['info']['url'] ) ) {
				// Ignore query variables when detecting a redirect.
				$from = untrailingslashit( preg_replace( '#\?[^$]*$#', '', $request['url'] ) );
				$to = untrailingslashit( preg_replace( '#\?[^$]*$#', '', $response['info']['url'] ) );

				if ( $from !== $to ) {
					$redirected_to = $response['info']['url'];
				}
			}

			if ( ! $response['intercepted'] ) {
				$this->data->ltime += $ltime;
			}

			$host = (string) parse_url( $request['url'], PHP_URL_HOST );
			$local = ( $host === $home_host );

			$this->log_type( $type );

			$http_request = new QM_Data_HTTP_Request();
			$http_request->args = array(
				'method' => $response['args']['method'],
				'timeout' => (float) $response['args']['timeout'],
			);
			if ( isset( $response['args']['redirection'] ) ) {
				$http_request->args['redirection'] = (int) $response['args']['redirection'];
			}
			if ( isset( $response['args']['blocking'] ) ) {
				$http_request->args['blocking'] = (bool) $response['args']['blocking'];
			}
			if ( isset( $response['args']['sslverify'] ) ) {
				$http_request->args['sslverify'] = (bool) $response['args']['sslverify'];
			}
			$http_request->trace = $request['trace'];
			$http_request->host = $host;
			$http_request->info = $response['info'];
			$http_request->local = $local;
			$http_request->ltime = $ltime;
			$http_request->redirected_to = $redirected_to;
			$http_request->result = $response['result'];
			$http_request->type = $type;
			$http_request->url = $request['url'];
			$http_request->intercepted = $response['intercepted'];

			$this->data->http[] = $http_request;
		}

	}

	/**
	 * Log a Guzzle HTTP request.
	 *
	 * @since 3.19.0
	 *
	 * @param \Psr\Http\Message\RequestInterface $request    The Guzzle request object.
	 * @param \Psr\Http\Message\ResponseInterface|null $response The Guzzle response object, or null if an exception occurred.
	 * @param \Exception|null $exception The exception thrown, or null if the request was successful.
	 * @param string $url        The request URL.
	 * @param float $start_time  The request start time.
	 * @param QM_Backtrace $trace The backtrace object.
	 * @param array<string, mixed> $options Guzzle request options.
	 */
	public function log_guzzle_request( $request, $response, $exception, string $url, float $start_time, QM_Backtrace $trace, array $options ) : void {
		$end_time = microtime( true );
		$ltime = $end_time - $start_time;
		$key = $start_time . $url;
		$args = array(
			'method' => $request->getMethod(),
			'timeout' => $options['timeout'] ?? 30,
			'blocking' => true,
			'sslverify' => $options['verify'] ?? true,
			'_qm_guzzle' => true,
		);

		if ( $exception ) {
			$response_data = new WP_Error( 'guzzle_request_failed', $exception->getMessage() );
			$type = 'error';
		} else {
			$response_data = new QM_Data_HTTP_Response();
			$response_data->code = $response->getStatusCode();
			$response_data->message = $response->getReasonPhrase();

			$code = $response->getStatusCode();
			$type = "HTTP {$code}";
		}

		$home_host = (string) parse_url( home_url(), PHP_URL_HOST );
		$host = (string) parse_url( $url, PHP_URL_HOST );
		$local = ( $host === $home_host );

		$this->log_type( $type );

		$http_request = new QM_Data_HTTP_Request();
		$http_request->args = $args;
		$http_request->trace = $trace;
		$http_request->host = $host;
		$http_request->info = null;
		$http_request->local = $local;
		$http_request->ltime = $ltime;
		$http_request->redirected_to = null;
		$http_request->result = $response_data;
		$http_request->type = $type;
		$http_request->url = $url;
		$http_request->intercepted = false;

		$this->data->http[] = $http_request;

		$this->data->ltime += $ltime;

		if ( $exception || ( $response && $response->getStatusCode() >= 400 ) ) {
			// This value isn't used, it's just a placeholder to indicate an error occurred.
			// @TODO need a proper way to set error/warning state on a data instance.
			$this->data->errors['warning'][] = $url;
		}
	}

	/**
	 * Creates a Guzzle middleware for logging HTTP requests to Query Monitor.
	 *
	 * Usage:
	 *
	 *   $stack = HandlerStack::create();
	 *   $stack->push( QM_Collector_HTTP::guzzle_middleware() );
	 *   $client = new Client( [ 'handler' => $stack ] );
	 *
	 * @since 3.19.0
	 *
	 * @return callable Guzzle middleware callable.
	 */
	public static function guzzle_middleware(): callable {
		return function ( callable $handler ) {
			return function ( \Psr\Http\Message\RequestInterface $request, array $options ) use ( $handler ) {
				$collector = QM_Collectors::get( 'http' );

				if ( ! ( $collector instanceof QM_Collector_HTTP ) ) {
					return $handler( $request, $options );
				}

				$url = (string) $request->getUri();
				$start_time = microtime( true );

				$trace = new QM_Backtrace( array(
					'ignore_namespace' => array(
						'GuzzleHttp' => true,
					),
				) );

				$promise = $handler( $request, $options );

				return $promise->then(
					function ( \Psr\Http\Message\ResponseInterface $response ) use ( $collector, $request, $options, $url, $start_time, $trace ) {
						$collector->log_guzzle_request( $request, $response, null, $url, $start_time, $trace, $options );
						return $response;
					},
					function ( \Exception $exception ) use ( $collector, $request, $options, $url, $start_time, $trace ) {
						$collector->log_guzzle_request( $request, null, $exception, $url, $start_time, $trace, $options );
						throw $exception;
					}
				);
			};
		};
	}

}

# Load early in case a plugin is doing an HTTP request when it initialises instead of after the `plugins_loaded` hook
QM_Collectors::add( new QM_Collector_HTTP() );
