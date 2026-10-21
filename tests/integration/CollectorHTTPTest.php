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

}
