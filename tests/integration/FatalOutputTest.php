<?php declare(strict_types = 1);

namespace QM\Tests;

/**
 * @phpstan-import-type FatalError from \QM_Dispatcher
 */
class FatalOutputTest extends Test {

	private ?\QM_Collector_PHP_Errors $collector = null;

	/**
	 * @var string|false
	 */
	private $display_errors;

	#[\Override]
	public function set_up(): void {
		parent::set_up();

		$this->display_errors = ini_get( 'display_errors' );

		// Ensures QM's handler is the outermost one unless a test registers its own previous handler.
		set_exception_handler( null );
	}

	#[\Override]
	public function tear_down(): void {
		if ( $this->collector ) {
			$this->collector->tear_down();
		}

		restore_exception_handler();

		if ( is_string( $this->display_errors ) ) {
			ini_set( 'display_errors', $this->display_errors );
		}

		parent::tear_down();
	}

	/**
	 * @phpstan-return FatalError
	 */
	private static function getFatalError(): array {
		return array(
			'message' => 'Uncaught Error: This is a test fatal error',
			'file' => __FILE__,
			'line' => 123,
			'trace' => array(),
		);
	}

	private function setUpCollector( string $display_errors ): \QM_Collector_PHP_Errors {
		ini_set( 'display_errors', $display_errors );

		$this->collector = new \QM_Collector_PHP_Errors();
		$this->collector->set_up();

		return $this->collector;
	}

	private function getExceptionHandlerOutput( \QM_Collector_PHP_Errors $collector ): string {
		ob_start();

		try {
			$collector->exception_handler( new \Error( 'This is a test fatal error' ) );
		} catch ( \Error $e ) {
			// Rethrown by QM after it outputs the fatal.
		} finally {
			$output = (string) ob_get_clean();
		}

		return $output;
	}

	public function testAjaxDispatcherOutputsFatalAsJson(): void {
		$dispatcher = \QM_Dispatchers::get( 'ajax' );
		self::assertInstanceOf( \QM_Dispatcher_AJAX::class, $dispatcher );

		ob_start();
		$dispatcher->output_fatal( 'This is a test fatal error', self::getFatalError() );
		$output = (string) ob_get_clean();

		$json = json_decode( $output, true );

		self::assertIsArray( $json );
		self::assertSame( 'qm_fatal', $json['code'] );
		self::assertSame( 'This is a test fatal error', $json['message'] );
		self::assertSame( 10, has_filter( 'wp_php_error_message', '__return_empty_string' ) );
	}

	public function testRestDispatcherOutputsFatalAsJson(): void {
		$dispatcher = \QM_Dispatchers::get( 'rest' );
		self::assertInstanceOf( \QM_Dispatcher_REST::class, $dispatcher );

		ob_start();
		$dispatcher->output_fatal( 'This is a test fatal error', self::getFatalError() );
		$output = (string) ob_get_clean();

		$json = json_decode( $output, true );

		self::assertIsArray( $json );
		self::assertSame( 'qm_fatal', $json['code'] );
		self::assertSame( 'This is a test fatal error', $json['message'] );
		self::assertFalse( has_filter( 'wp_php_error_message', '__return_empty_string' ) );
	}

	public function testFatalIsNotOutputForLoggedOutUserWhenDisplayErrorsIsOff(): void {
		wp_set_current_user( 0 );

		$output = $this->getExceptionHandlerOutput( $this->setUpCollector( '0' ) );

		self::assertStringNotContainsString( 'id="qm-fatal"', $output );
	}

	public function testFatalIsOutputForLoggedOutUserWhenDisplayErrorsIsOn(): void {
		wp_set_current_user( 0 );

		$output = $this->getExceptionHandlerOutput( $this->setUpCollector( '1' ) );

		self::assertStringContainsString( 'id="qm-fatal"', $output );
		self::assertStringContainsString( 'This is a test fatal error', $output );
	}

	public function testFatalIsOutputForAdminWhenDisplayErrorsIsOff(): void {
		$admin = self::factory()->user->create( array(
			'role' => 'administrator',
		) );
		if ( is_multisite() ) {
			grant_super_admin( $admin );
		}
		wp_set_current_user( $admin );

		$output = $this->getExceptionHandlerOutput( $this->setUpCollector( '0' ) );

		self::assertStringContainsString( 'id="qm-fatal"', $output );
		self::assertStringContainsString( 'This is a test fatal error', $output );
	}

	public function testExceptionHandlerRethrowsWhenThereIsNoPreviousHandler(): void {
		wp_set_current_user( 0 );
		$collector = $this->setUpCollector( '0' );

		$this->expectException( \Error::class );
		$this->expectExceptionMessage( 'This is a test fatal error' );

		$collector->exception_handler( new \Error( 'This is a test fatal error' ) );
	}

	public function testExceptionHandlerPassesErrorToPreviousHandler(): void {
		// The previous handler throws so that QM's subsequent exit() isn't reached.
		set_exception_handler( static function ( \Throwable $e ): void {
			throw new \LogicException( 'Previous handler received: ' . $e->getMessage() );
		} );

		wp_set_current_user( 0 );
		$collector = $this->setUpCollector( '0' );

		$this->expectException( \LogicException::class );
		$this->expectExceptionMessage( 'Previous handler received: This is a test fatal error' );

		try {
			$collector->exception_handler( new \Error( 'This is a test fatal error' ) );
		} finally {
			$collector->tear_down();
			$this->collector = null;
			restore_exception_handler();
		}
	}

}
