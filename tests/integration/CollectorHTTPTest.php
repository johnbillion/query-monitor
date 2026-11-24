<?php declare(strict_types = 1);

namespace QM\Tests;

class CollectorHTTPTest extends Test {

	public function testPreemptedResponseWithIncompleteResponseArrayIsLogged(): void {
		$collector = new \QM_Collector_HTTP();
		$collector->set_up();

		$callback = static function () {
			return array(
				'headers' => array(),
				'body' => '',
				'response' => array(),
				'cookies' => array(),
			);
		};

		$errors = array();

		// QM's PHP errors collector replaces PHPUnit's handler, so warnings need to be captured here.
		set_error_handler( static function ( int $errno, string $errstr ) use ( &$errors ): bool {
			$errors[] = $errstr;
			return true;
		} );

		add_filter( 'pre_http_request', $callback );

		try {
			wp_remote_get( 'https://example.org/' );
		} finally {
			restore_error_handler();
			remove_filter( 'pre_http_request', $callback );
			$collector->tear_down();
		}

		self::assertSame( array(), $errors );

		$collector->process();

		/** @var \QM_Data_HTTP $data */
		$data = $collector->get_data();

		self::assertCount( 1, $data->http );

		$result = $data->http[0]->result;

		self::assertInstanceOf( \QM_Data_HTTP_Response::class, $result );
		self::assertSame( 0, $result->code );
		self::assertSame( '', $result->message );
		self::assertTrue( $data->http[0]->intercepted );
	}

	public function testResponsePreemptedAfterQMsFilterIsLoggedAsNotExecuted(): void {
		$collector = new \QM_Collector_HTTP();
		$collector->set_up();

		$callback = static function () {
			return new \WP_Error( 'intercepted', 'Intercepted after QM' );
		};

		add_filter( 'pre_http_request', $callback, 10000 );

		try {
			wp_remote_get( 'https://example.org/' );
		} finally {
			remove_filter( 'pre_http_request', $callback, 10000 );
			$collector->tear_down();
		}

		$collector->process();

		/** @var \QM_Data_HTTP $data */
		$data = $collector->get_data();

		self::assertCount( 1, $data->http );

		$result = $data->http[0]->result;

		self::assertInstanceOf( \WP_Error::class, $result );
		self::assertSame( 'http_request_not_executed', $result->get_error_code() );
		self::assertSame( array(), $data->errors['alert'] ?? array() );
		self::assertSame( 0.0, $data->ltime );
	}

	public function testSuccessfulGuzzleRequestIsLogged(): void {
		$data = $this->perform_guzzle_request(
			new \GuzzleHttp\Psr7\Response( 200, array(), '{"foo":"bar"}' ),
			'https://example.org/json'
		);

		self::assertCount( 1, $data->http );

		$request = $data->http[0];

		self::assertSame( 'https://example.org/json', $request->url );
		self::assertSame( 'HTTP 200', $request->type );
		self::assertTrue( $request->args['_qm_guzzle'] ?? null );
		self::assertInstanceOf( \QM_Data_HTTP_Response::class, $request->result );
		self::assertSame( 200, $request->result->code );
		self::assertEmpty( $data->errors );
	}

	public function testErrorGuzzleRequestIsLogged(): void {
		$data = $this->perform_guzzle_request(
			new \GuzzleHttp\Psr7\Response( 404, array(), 'Not Found' ),
			'https://example.org/status/404'
		);

		self::assertCount( 1, $data->http );

		$request = $data->http[0];

		self::assertSame( 'https://example.org/status/404', $request->url );
		self::assertSame( 'HTTP 404', $request->type );
		self::assertInstanceOf( \QM_Data_HTTP_Response::class, $request->result );
		self::assertSame( 404, $request->result->code );
		self::assertContains( 'https://example.org/status/404', $data->errors['warning'] ?? array() );
	}

	private function perform_guzzle_request( \GuzzleHttp\Psr7\Response $response, string $url ): \QM_Data_HTTP {
		$original = \QM_Collectors::get( 'http' );
		self::assertInstanceOf( \QM_Collector_HTTP::class, $original );

		$collector = new \QM_Collector_HTTP();
		\QM_Collectors::add( $collector );

		$stack = \GuzzleHttp\HandlerStack::create( new \GuzzleHttp\Handler\MockHandler( array( $response ) ) );
		$stack->push( \QM_Collector_HTTP::guzzle_middleware() );
		$client = new \GuzzleHttp\Client( array(
			'handler' => $stack,
		) );

		try {
			$client->get( $url );
			self::assertLessThan( 400, $response->getStatusCode() );
		} catch ( \GuzzleHttp\Exception\ClientException $e ) {
			self::assertGreaterThanOrEqual( 400, $response->getStatusCode() );
			self::assertSame( $response, $e->getResponse() );
		} finally {
			$collector->tear_down();
			\QM_Collectors::add( $original );
		}

		$collector->process();

		/** @var \QM_Data_HTTP $data */
		$data = $collector->get_data();

		return $data;
	}

}
