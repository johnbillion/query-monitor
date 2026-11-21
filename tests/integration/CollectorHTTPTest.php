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

}
