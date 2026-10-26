<?php declare(strict_types = 1);

namespace QM\Tests;

class CollectorPhpErrorsTest extends Test {

	/**
	 * @var \QM_Collector_PHP_Errors
	 */
	public $collector;

	#[\Override]
	public function set_up(): void {
		parent::set_up();

		$this->collector = new \QM_Collector_PHP_Errors();
	}

	#[\Override]
	public function tear_down(): void {
		$this->collector->tear_down();

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

	function testItCallsPreviousErrorHandler(): void {
		// Test needs the previous error handler set before the sut is set up.
		ini_set( 'display_errors', '0' );
		$this->collector->set_up();
		$this->collector->tear_down();

		set_error_handler(function( $errno, $message, $file = null, $line = null, $context = null, $do_trace = true ) {
			return true;
		});

		$this->collector = new \QM_Collector_PHP_Errors();
		$this->collector->set_up();

		$result = $this->collector->error_handler( E_WARNING, 'test' );

		self::assertTrue( $result );
	}
}
