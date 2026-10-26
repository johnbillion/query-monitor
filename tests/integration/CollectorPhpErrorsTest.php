<?php declare(strict_types = 1);

namespace QM\Tests;

class CollectorPhpErrorsTest extends Test {

	/**
	 * @var \QM_Collector_PHP_Errors
	 */
	public $collector;

	private bool $registered_previous_error_handler = false;

	#[\Override]
	public function set_up(): void {
		parent::set_up();

		$this->collector = new \QM_Collector_PHP_Errors();
	}

	#[\Override]
	public function tear_down(): void {
		$this->collector->tear_down();

		if ( $this->registered_previous_error_handler ) {
			restore_error_handler();
		}

		parent::tear_down();
	}

	/**
	 * Helper to create a QM_Data_PHP_Error instance.
	 *
	 * @param int $errno
	 * @param 'warning'|'notice'|'strict'|'deprecated' $level
	 * @param \QM_Backtrace $trace
	 * @return \QM_Data_PHP_Error
	 */
	private function createError( int $errno, string $level, \QM_Backtrace $trace ): \QM_Data_PHP_Error {
		$error = new \QM_Data_PHP_Error();
		$error->errno = $errno;
		$error->level = $level;
		$error->suppressed = false;
		$error->message = 'Test error';
		$error->trace = $trace;
		$error->count = 1;

		return $error;
	}

	function testItKnowsNullFlagIsAlwaysReportable(): void {
		$actual = $this->collector->is_reportable_error(
			E_NOTICE, null
		);

		self::assertTrue( $actual );
	}

	function testItKnowsErrorInFlagsIsReportable(): void {
		$actual = $this->collector->is_reportable_error(
			E_NOTICE, E_ALL & ~E_WARNING
		);

		self::assertTrue( $actual );
	}

	function testItKnowsErrorOutsideFlagsIsNotReportable(): void {
		$actual = $this->collector->is_reportable_error(
			E_NOTICE, E_ALL & ~E_NOTICE
		);

		self::assertFalse( $actual );
	}

	function testItKnowsSameErrorAndFlagIsReportable(): void {
		$actual = $this->collector->is_reportable_error(
			E_NOTICE, E_NOTICE
		);

		self::assertTrue( $actual );
	}

	function testItKnowsCoreFileIsNotInPlugin(): void {
		$component = \QM_Util::get_file_component( ABSPATH . 'wp-includes/plugin.php' );
		$actual = $this->collector->is_affected_component(
			$component, \QM_Component::TYPE_PLUGIN, 'foo'
		);

		self::assertSame( 'core', $component->context );
		self::assertFalse( $actual );
	}

	function testItKnowsThemeFileIsNotInPlugin(): void {
		$component = \QM_Util::get_file_component( WP_CONTENT_DIR . '/themes/foo/taxonomy.php' );
		$actual = $this->collector->is_affected_component(
			$component, \QM_Component::TYPE_PLUGIN, 'foo'
		);

		// self::assertSame( 'other', $component->context );
		self::assertFalse( $actual );
	}

	function testItKnowsAnotherPluginFileIsNotInPlugin(): void {
		$component = \QM_Util::get_file_component( WP_PLUGIN_DIR . '/bar/taxonomy.php' );
		$actual = $this->collector->is_affected_component(
			$component, \QM_Component::TYPE_PLUGIN, 'foo'
		);

		self::assertSame( 'bar', $component->context );
		self::assertFalse( $actual );
	}

	function testItKnowsEmptyFilePathIsNotInPlugin(): void {
		$component = \QM_Util::get_file_component( ABSPATH );
		$actual = $this->collector->is_affected_component(
			$component, \QM_Component::TYPE_PLUGIN, 'foo'
		);

		self::assertSame( 'core', $component->context );
		self::assertFalse( $actual );
	}

	function testItKnowsEmptyPluginNameIsNotInPlugin(): void {
		$component = \QM_Util::get_file_component( WP_PLUGIN_DIR . '/bar/taxonomy.php' );
		$actual = $this->collector->is_affected_component(
			$component, '', ''
		);

		self::assertSame( 'bar', $component->context );
		self::assertFalse( $actual );
	}

	function testItKnowsPluginFileIsInPlugin(): void {
		$component = \QM_Util::get_file_component( WP_PLUGIN_DIR . '/foo/taxonomy.php' );
		$actual = $this->collector->is_affected_component(
			$component, \QM_Component::TYPE_PLUGIN, 'foo'
		);

		self::assertSame( 'foo', $component->context );
		self::assertTrue( $actual );
	}

	function testItKnowsThemeFileIsInTheme(): void {
		$component = \QM_Util::get_file_component( get_stylesheet_directory() . '/taxonomy.php' );
		$actual = $this->collector->is_affected_component(
			$component, \QM_Component::TYPE_STYLESHEET, 'stylesheet'
		);

		self::assertSame( 'stylesheet', $component->context );
		self::assertTrue( $actual );
	}

	function testItKnowsCoreFileIsInCore(): void {
		$component = \QM_Util::get_file_component( ABSPATH . 'wp-includes/plugin.php' );
		$actual = $this->collector->is_affected_component(
			$component, \QM_Component::TYPE_CORE, 'core'
		);

		self::assertSame( 'core', $component->context );
		self::assertTrue( $actual );
	}

	function testItKnowsFolderlessPluginFileIsInPlugin(): void {
		$component = \QM_Util::get_file_component( WP_PLUGIN_DIR . '/foo.php' );
		$actual = $this->collector->is_affected_component(
			$component, \QM_Component::TYPE_PLUGIN, 'foo.php'
		);

		self::assertSame( 'foo.php', $component->context );
		self::assertTrue( $actual );
	}

	function testItKnowsInternalPluginFileIsInPlugin(): void {
		$component = \QM_Util::get_file_component( WP_PLUGIN_DIR . '/foo/includes/A/B/foo.php' );
		$actual = $this->collector->is_affected_component(
			$component, \QM_Component::TYPE_PLUGIN, 'foo'
		);

		self::assertSame( 'foo', $component->context );
		self::assertTrue( $actual );
	}

	function testItKnowsPluginExtensionFileIsNotInPlugin(): void {
		$component = \QM_Util::get_file_component( WP_PLUGIN_DIR . '/foo-extension/foo-extension.php.php' );
		$actual = $this->collector->is_affected_component(
			$component, \QM_Component::TYPE_PLUGIN, 'foo'
		);

		self::assertSame( 'foo-extension', $component->context );
		self::assertFalse( $actual );
	}

	function testItWillNotFilterAnyErrorByDefault(): void {
		$trace = new Supports\TestBacktrace( [
			[
				'file' => WP_PLUGIN_DIR . '/foo/bar.php',
			],
		] );

		$errors = array(
			'abc' => $this->createError( E_NOTICE, 'notice', $trace ),
			'def' => $this->createError( E_NOTICE, 'notice', $trace ),
		);

		$this->collector->set_php_errors( $errors );
		$this->collector->process();

		$actual = $this->collector->get_data();

		// All errors should remain (no filtering by default):
		self::assertSame( 2, count( $actual->errors ) );
	}

	function testItWillFilterNoticesFromPlugin(): void {
		add_filter( 'qm/collect/php_error_levels', function( $table ) {
			$table[ \QM_Component::TYPE_PLUGIN ]['foo'] = E_ALL & ~E_NOTICE;
			return $table;
		} );

		$trace = new Supports\TestBacktrace( [
			[
				'file' => WP_PLUGIN_DIR . '/foo/bar.php',
			],
		] );

		$errors = array(
			'warning_abc' => $this->createError( E_WARNING, 'warning', $trace ),
			'notice_abc' => $this->createError( E_NOTICE, 'notice', $trace ),
		);

		$this->collector->set_php_errors( $errors );
		$this->collector->process();
		$actual = $this->collector->get_data();

		// Only the warning should remain after filtering:
		self::assertSame( 1, count( $actual->errors ) );
		self::assertArrayHasKey( 'warning_abc', $actual->errors );
		self::assertArrayNotHasKey( 'notice_abc', $actual->errors );
	}

	function testItReturnsFalseWhenNoPreviousErrorHandler(): void {
		$result = $this->collector->error_handler( E_WARNING, 'test' );

		self::assertFalse( $result );
	}

	function testItCallsFilterForErrorHandlerResult(): void {
		add_filter( 'qm/collect/php_errors_return_value', '__return_true' );

		$result = $this->collector->error_handler( E_WARNING, 'test' );

		self::assertTrue( $result );
	}

	/**
	 * Registers an error handler that becomes the collector's previous handler when the collector is set up.
	 *
	 * @param mixed $return_value
	 * @return \ArrayObject<int, list<mixed>> The arguments of each call to the handler.
	 */
	private function registerPreviousErrorHandler( $return_value ): \ArrayObject {
		/** @var \ArrayObject<int, list<mixed>> $calls */
		$calls = new \ArrayObject();

		set_error_handler( static function () use ( $calls, $return_value ) {
			$calls[] = func_get_args();
			return $return_value;
		} );

		$this->registered_previous_error_handler = true;

		return $calls;
	}

	/**
	 * @param mixed $return_value
	 * @return \ArrayObject<int, list<mixed>> The arguments of each call to the previous handler.
	 */
	private function setUpWithPreviousErrorHandler( $return_value ): \ArrayObject {
		$calls = $this->registerPreviousErrorHandler( $return_value );

		// Prevent an error from an earlier test being recorded by the collector.
		error_clear_last();
		$this->collector->set_up();

		return $calls;
	}

	function testItCallsPreviousErrorHandlerWithStandardArguments(): void {
		$calls = $this->setUpWithPreviousErrorHandler( false );

		$this->collector->error_handler( E_WARNING, 'test', 'file.php', 123, array(), false );

		self::assertSame( array(
			array( E_WARNING, 'test', 'file.php', 123 ),
		), $calls->getArrayCopy() );
	}

	function testItCallsPreviousErrorHandlerForFatalErrors(): void {
		$calls = $this->setUpWithPreviousErrorHandler( true );

		$result = $this->collector->error_handler( E_RECOVERABLE_ERROR, 'test', 'file.php', 123 );

		self::assertTrue( $result );
		self::assertCount( 1, $calls );
		self::assertEmpty( $this->collector->get_data()->errors );
	}

	/**
	 * @return array<string, array{mixed, bool}>
	 */
	public static function dataPreviousErrorHandlerReturnValues(): array {
		return array(
			'true' => array( true, true ),
			'false' => array( false, false ),
			// PHP treats any return value other than false as handled.
			'null' => array( null, true ),
		);
	}

	/**
	 * @dataProvider dataPreviousErrorHandlerReturnValues
	 * @param mixed $return_value
	 */
	function testItReturnsPreviousErrorHandlerResult( $return_value, bool $expected ): void {
		$this->setUpWithPreviousErrorHandler( $return_value );

		$result = $this->collector->error_handler( E_WARNING, 'test' );

		self::assertSame( $expected, $result );
	}

	function testItCallsPreviousErrorHandlerWhenFilterReturnsTrue(): void {
		add_filter( 'qm/collect/php_errors_return_value', '__return_true' );
		$calls = $this->setUpWithPreviousErrorHandler( false );

		$result = $this->collector->error_handler( E_WARNING, 'test' );

		self::assertTrue( $result );
		self::assertCount( 1, $calls );
	}

	function testItRecordsErrorWhenPreviousErrorHandlerReturnsTrue(): void {
		$this->setUpWithPreviousErrorHandler( true );

		$this->collector->error_handler( E_WARNING, 'test' );

		$errors = $this->collector->get_data()->errors;

		self::assertCount( 1, $errors );
		self::assertSame( 'test', reset( $errors )->message );
	}

	function testItDoesNotPassPriorErrorToPreviousErrorHandler(): void {
		$calls = $this->registerPreviousErrorHandler( false );

		error_clear_last();
		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		@trigger_error( 'prior', E_USER_NOTICE );
		$calls->exchangeArray( array() );

		$this->collector->set_up();
		$errors = $this->collector->get_data()->errors;

		self::assertCount( 0, $calls );
		self::assertCount( 1, $errors );
		self::assertSame( 'prior', reset( $errors )->message );
	}
}
