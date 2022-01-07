<?php

declare(strict_types = 1);

namespace QM\Tests;

class TestCollectorPHPErrors extends Test {

	/**
	 * @var \QM_Collector_PHP_Errors
	 */
	public $collector;

	function setUp() {
		parent::setUp();

		$this->collector = new \QM_Collector_PHP_Errors();
	}

	function tearDown() {
		$this->collector->tear_down();

		parent::tearDown();
	}

	function testItKnowsNullFlagIsAlwaysReportable() {
		$actual = $this->collector->is_reportable_error(
			E_NOTICE, null
		);

		self::assertTrue( $actual );
	}

	function testItKnowsErrorInFlagsIsReportable() {
		$actual = $this->collector->is_reportable_error(
			E_NOTICE, E_ALL & ~E_WARNING
		);

		self::assertTrue( $actual );
	}

	function testItKnowsErrorOutsideFlagsIsNotReportable() {
		$actual = $this->collector->is_reportable_error(
			E_NOTICE, E_ALL & ~E_NOTICE
		);

		self::assertFalse( $actual );
	}

	function testItKnowsSameErrorAndFlagIsReportable() {
		$actual = $this->collector->is_reportable_error(
			E_NOTICE, E_NOTICE
		);

		self::assertTrue( $actual );
	}

	function testItKnowsCoreFileIsNotInPlugin() {
		$component = QM_Util::get_file_component( ABSPATH . 'wp-includes/plugin.php' );
		$actual = $this->collector->is_affected_component(
			$component, 'plugin', 'foo'
		);

		self::assertFalse( $actual );
	}

	function testItKnowsThemeFileIsNotInPlugin() {
		$component = QM_Util::get_file_component( WP_CONTENT_DIR . '/themes/foo/taxonomy.php' );
		$actual = $this->collector->is_affected_component(
			$component, 'plugin', 'foo'
		);

		self::assertFalse( $actual );
	}

	function testItKnowsAnotherPluginFileIsNotInPlugin() {
		$component = QM_Util::get_file_component( WP_PLUGIN_DIR . '/bar/taxonomy.php' );
		$actual = $this->collector->is_affected_component(
			$component, 'plugin', 'foo'
		);

		self::assertFalse( $actual );
	}

	function testItKnowsEmptyFilePathIsNotInPlugin() {
		$component = QM_Util::get_file_component( ABSPATH );
		$actual = $this->collector->is_affected_component(
			$component, 'plugin', 'foo'
		);

		self::assertFalse( $actual );
	}

	function testItKnowsEmptyPluginNameIsNotInPlugin() {
		$component = QM_Util::get_file_component( WP_PLUGIN_DIR . '/bar/taxonomy.php' );
		$actual = $this->collector->is_affected_component(
			$component, '', ''
		);

		self::assertFalse( $actual );
	}

	function testItKnowsPluginFileIsInPlugin() {
		$component = QM_Util::get_file_component( WP_PLUGIN_DIR . '/foo/taxonomy.php' );
		$actual = $this->collector->is_affected_component(
			$component, 'plugin', 'foo'
		);

		self::assertTrue( $actual );
	}

	function testItKnowsThemeFileIsInTheme() {
		$component = QM_Util::get_file_component( get_stylesheet_directory() . '/taxonomy.php' );
		$actual = $this->collector->is_affected_component(
			$component, 'theme', 'stylesheet'
		);

		self::assertTrue( $actual );
	}

	function testItKnowsCoreFileIsInCore() {
		$component = QM_Util::get_file_component( ABSPATH . 'wp-includes/plugin.php' );
		$actual = $this->collector->is_affected_component(
			$component, 'core', 'core'
		);

		self::assertTrue( $actual );
	}

	function testItKnowsFolderlessPluginFileIsInPlugin() {
		$component = QM_Util::get_file_component( WP_PLUGIN_DIR . '/foo.php' );
		$actual = $this->collector->is_affected_component(
			$component, 'plugin', 'foo.php'
		);

		self::assertTrue( $actual );
	}

	function testItKnowsInternalPluginFileIsInPlugin() {
		$component = QM_Util::get_file_component( WP_PLUGIN_DIR . '/foo/includes/A/B/foo.php' );
		$actual = $this->collector->is_affected_component(
			$component, 'plugin', 'foo'
		);

		self::assertTrue( $actual );
	}

	function testItKnowsPluginExtensionFileIsNotInPlugin() {
		$component = QM_Util::get_file_component( WP_PLUGIN_DIR . '/foo-extension/foo-extension.php.php' );
		$actual = $this->collector->is_affected_component(
			$component, 'plugin', 'foo'
		);

		self::assertFalse( $actual );
	}

	function testItWillNotFilterAnyErrorByDefault() {
		$trace = new QM_Test_Backtrace;
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
		self::assertArrayHasKey( 'errors', $actual );
		self::assertArrayHasKey( 'notice', $actual['errors'] );
		self::assertEquals( 2, count( $actual['errors']['notice'] ) );

		// silenced errors:
		self::assertArrayNotHasKey( 'silenced', $actual );
	}

	function testItWillFilterNoticesFromPlugin() {
		add_filter( 'qm/collect/php_error_levels', function( $table ) {
			$table['plugin']['foo'] = E_ALL & ~E_NOTICE;
			return $table;
		} );

		$trace = new QM_Test_Backtrace;
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
		self::assertArrayHasKey( 'errors', $actual );
		self::assertArrayHasKey( 'warning', $actual['errors'] );
		self::assertArrayNotHasKey( 'notice', $actual['errors'] );
		self::assertEquals( 1, count( $actual['errors']['warning'] ) );

		// silenced errors:
		self::assertArrayHasKey( 'silenced', $actual );
		self::assertArrayHasKey( 'notice', $actual['silenced'] );
		self::assertArrayNotHasKey( 'warning', $actual['silenced'] );
		self::assertEquals( 1, count( $actual['silenced']['notice'] ) );
	}
}
