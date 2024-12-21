<?php declare(strict_types = 1);

namespace QM\Tests;

class CollectorPhpErrorsTest extends Test {

	/**
	 * @var \QM_Collector_PHP_Errors
	 */
	public $collector;

	function _before(): void {
		parent::_before();

		$this->collector = new \QM_Collector_PHP_Errors();
	}

	function _after(): void {
		$this->collector->tear_down();

		parent::_after();
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
			$component, 'plugin', 'foo'
		);

		self::assertSame( 'core', $component->context );
		self::assertFalse( $actual );
	}

	function testItKnowsThemeFileIsNotInPlugin(): void {
		$component = \QM_Util::get_file_component( WP_CONTENT_DIR . '/themes/foo/taxonomy.php' );
		$actual = $this->collector->is_affected_component(
			$component, 'plugin', 'foo'
		);

		// self::assertSame( 'other', $component->context );
		self::assertFalse( $actual );
	}

	function testItKnowsAnotherPluginFileIsNotInPlugin(): void {
		$component = \QM_Util::get_file_component( WP_PLUGIN_DIR . '/bar/taxonomy.php' );
		$actual = $this->collector->is_affected_component(
			$component, 'plugin', 'foo'
		);

		self::assertSame( 'bar', $component->context );
		self::assertFalse( $actual );
	}

	function testItKnowsEmptyFilePathIsNotInPlugin(): void {
		$component = \QM_Util::get_file_component( ABSPATH );
		$actual = $this->collector->is_affected_component(
			$component, 'plugin', 'foo'
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
			$component, 'plugin', 'foo'
		);

		self::assertSame( 'foo', $component->context );
		self::assertTrue( $actual );
	}

	function testItKnowsThemeFileIsInTheme(): void {
		$component = \QM_Util::get_file_component( get_stylesheet_directory() . '/taxonomy.php' );
		$actual = $this->collector->is_affected_component(
			$component, 'theme', 'stylesheet'
		);

		self::assertSame( 'stylesheet', $component->context );
		self::assertTrue( $actual );
	}

	function testItKnowsCoreFileIsInCore(): void {
		$component = \QM_Util::get_file_component( ABSPATH . 'wp-includes/plugin.php' );
		$actual = $this->collector->is_affected_component(
			$component, 'core', 'core'
		);

		self::assertSame( 'core', $component->context );
		self::assertTrue( $actual );
	}

	function testItKnowsFolderlessPluginFileIsInPlugin(): void {
		$component = \QM_Util::get_file_component( WP_PLUGIN_DIR . '/foo.php' );
		$actual = $this->collector->is_affected_component(
			$component, 'plugin', 'foo.php'
		);

		self::assertSame( 'foo.php', $component->context );
		self::assertTrue( $actual );
	}

	function testItKnowsInternalPluginFileIsInPlugin(): void {
		$component = \QM_Util::get_file_component( WP_PLUGIN_DIR . '/foo/includes/A/B/foo.php' );
		$actual = $this->collector->is_affected_component(
			$component, 'plugin', 'foo'
		);

		self::assertSame( 'foo', $component->context );
		self::assertTrue( $actual );
	}

	function testItKnowsPluginExtensionFileIsNotInPlugin(): void {
		$component = \QM_Util::get_file_component( WP_PLUGIN_DIR . '/foo-extension/foo-extension.php.php' );
		$actual = $this->collector->is_affected_component(
			$component, 'plugin', 'foo'
		);

		self::assertSame( 'foo-extension', $component->context );
		self::assertFalse( $actual );
	}

	function testItWillNotFilterAnyErrorByDefault(): void {
		$trace = new Supports\TestBacktrace;
		$trace->set_trace( [
			[
				'file' => WP_PLUGIN_DIR . '/foo/bar.php',
			],
		] );

		$errors = array(
			'notice' => array(
				'abc' => array(
					'errno' => E_NOTICE,
					'trace' => $trace,
					'component' => $trace->get_component(),
				),
				'def' => array(
					'errno' => E_NOTICE,
					'trace' => $trace,
					'component' => $trace->get_component(),
				),
			),
		);

		$this->collector->set_php_errors( $errors );
		$this->collector->process();

		$actual = $this->collector->get_data();

		// errors:
		self::assertTrue( property_exists( $actual, 'errors' ) );
		self::assertArrayHasKey( 'notice', $actual->errors );
		self::assertSame( 2, count( $actual->errors['notice'] ) );

		// silenced errors:
		self::assertTrue( property_exists( $actual, 'silenced' ) );
		// @phpstan-ignore-next-line
		self::assertFalse( isset( $actual->silenced ) );
	}

	function testItWillFilterNoticesFromPlugin(): void {
		add_filter( 'qm/collect/php_error_levels', function( $table ) {
			$table['plugin']['foo'] = E_ALL & ~E_NOTICE;
			return $table;
		} );

		$trace = new Supports\TestBacktrace;
		$trace->set_trace( [
			[
				'file' => WP_PLUGIN_DIR . '/foo/bar.php',
			],
		] );

		$errors = array(
			'warning' => array(
				'abc' => array(
					'errno' => E_WARNING,
					'trace' => $trace,
					'component' => $trace->get_component(),
				),
			),
			'notice' => array(
				'abc' => array(
					'errno' => E_NOTICE,
					'trace' => $trace,
					'component' => $trace->get_component(),
				),
			),
		);

		$this->collector->set_php_errors( $errors );
		$this->collector->process();
		$actual = $this->collector->get_data();

		// errors:
		self::assertTrue( property_exists( $actual, 'errors' ) );
		self::assertArrayHasKey( 'warning', $actual->errors );
		self::assertArrayNotHasKey( 'notice', $actual->errors );
		self::assertSame( 1, count( $actual->errors['warning'] ) );

		// silenced errors:
		self::assertTrue( property_exists( $actual, 'silenced' ) );
		self::assertArrayHasKey( 'notice', $actual->silenced );
		self::assertArrayNotHasKey( 'warning', $actual->silenced );
		self::assertSame( 1, count( $actual->silenced['notice'] ) );
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
