<?php

class TestCollectorPHPErrors extends QM_UnitTestCase {

	public $collector;

	function setUp() {
		parent::setUp();

		$this->collector = new QM_Collector_PHP_Errors();
	}

	function tearDown() {
		$this->collector->tear_down();

		parent::tearDown();
	}

	function testItKnowsNullFlagIsAlwaysReportable() {
		$actual = $this->collector->is_reportable_error(
			E_NOTICE, null
		);

		$this->assertTrue( $actual );
	}

	function testItKnowsErrorInFlagsIsReportable() {
		$actual = $this->collector->is_reportable_error(
			E_NOTICE, E_ALL & ~E_WARNING
		);

		$this->assertTrue( $actual );
	}

	function testItKnowsErrorOutsideFlagsIsNotReportable() {
		$actual = $this->collector->is_reportable_error(
			E_NOTICE, E_ALL & ~E_NOTICE
		);

		$this->assertFalse( $actual );
	}

	function testItKnowsSameErrorAndFlagIsReportable() {
		$actual = $this->collector->is_reportable_error(
			E_NOTICE, E_NOTICE
		);

		$this->assertTrue( $actual );
	}

	function testItKnowsCoreFileIsNotInPlugin() {
		$component = QM_Util::get_file_component( ABSPATH . 'wp-includes/plugin.php' );
		$actual = $this->collector->is_affected_component(
			$component, 'plugin', 'foo'
		);

		$this->assertFalse( $actual );
	}

	function testItKnowsThemeFileIsNotInPlugin() {
		$component = QM_Util::get_file_component( WP_CONTENT_DIR . '/themes/foo/taxonomy.php' );
		$actual = $this->collector->is_affected_component(
			$component, 'plugin', 'foo'
		);

		$this->assertFalse( $actual );
	}

	function testItKnowsAnotherPluginFileIsNotInPlugin() {
		$component = QM_Util::get_file_component( WP_PLUGIN_DIR . '/bar/taxonomy.php' );
		$actual = $this->collector->is_affected_component(
			$component, 'plugin', 'foo'
		);

		$this->assertFalse( $actual );
	}

	function testItKnowsEmptyFilePathIsNotInPlugin() {
		$component = QM_Util::get_file_component( ABSPATH );
		$actual = $this->collector->is_affected_component(
			$component, 'plugin', 'foo'
		);

		$this->assertFalse( $actual );
	}

	function testItKnowsEmptyPluginNameIsNotInPlugin() {
		$component = QM_Util::get_file_component( WP_PLUGIN_DIR . '/bar/taxonomy.php' );
		$actual = $this->collector->is_affected_component(
			$component, '', ''
		);

		$this->assertFalse( $actual );
	}

	function testItKnowsPluginFileIsInPlugin() {
		$component = QM_Util::get_file_component( WP_PLUGIN_DIR . '/foo/taxonomy.php' );
		$actual = $this->collector->is_affected_component(
			$component, 'plugin', 'foo'
		);

		$this->assertTrue( $actual );
	}

	function testItKnowsThemeFileIsInTheme() {
		$component = QM_Util::get_file_component( get_stylesheet_directory() . '/taxonomy.php' );
		$actual = $this->collector->is_affected_component(
			$component, 'theme', 'stylesheet'
		);

		$this->assertTrue( $actual );
	}

	function testItKnowsCoreFileIsInCore() {
		$component = QM_Util::get_file_component( ABSPATH . 'wp-includes/plugin.php' );
		$actual = $this->collector->is_affected_component(
			$component, 'core', 'core'
		);

		$this->assertTrue( $actual );
	}

	function testItKnowsFolderlessPluginFileIsInPlugin() {
		$component = QM_Util::get_file_component( WP_PLUGIN_DIR . '/foo.php' );
		$actual = $this->collector->is_affected_component(
			$component, 'plugin', 'foo.php'
		);

		$this->assertTrue( $actual );
	}

	function testItKnowsInternalPluginFileIsInPlugin() {
		$component = QM_Util::get_file_component( WP_PLUGIN_DIR . '/foo/includes/A/B/foo.php' );
		$actual = $this->collector->is_affected_component(
			$component, 'plugin', 'foo'
		);

		$this->assertTrue( $actual );
	}

	function testItKnowsPluginExtensionFileIsNotInPlugin() {
		$component = QM_Util::get_file_component( WP_PLUGIN_DIR . '/foo-extension/foo-extension.php.php' );
		$actual = $this->collector->is_affected_component(
			$component, 'plugin', 'foo'
		);

		$this->assertFalse( $actual );
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
				),
				'def' => array(
					'errno' => E_NOTICE,
					'trace' => $trace,
				),
			),
		);

		$this->collector->set_php_errors( $errors );
		$this->collector->process();

		$actual = $this->collector->get_data();

		// errors:
		$this->assertArrayHasKey( 'errors', $actual );
		$this->assertArrayHasKey( 'notice', $actual['errors'] );
		$this->assertEquals( 2, count( $actual['errors']['notice'] ) );

		// silenced errors:
		$this->assertArrayNotHasKey( 'silenced', $actual );
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
				),
			),
			'notice' => array(
				'abc' => array(
					'errno' => E_NOTICE,
					'trace' => $trace,
				),
			),
		);

		$this->collector->set_php_errors( $errors );
		$this->collector->process();
		$actual = $this->collector->get_data();

		// errors:
		$this->assertArrayHasKey( 'errors', $actual );
		$this->assertArrayHasKey( 'warning', $actual['errors'] );
		$this->assertArrayNotHasKey( 'notice', $actual['errors'] );
		$this->assertEquals( 1, count( $actual['errors']['warning'] ) );

		// silenced errors:
		$this->assertArrayHasKey( 'silenced', $actual );
		$this->assertArrayHasKey( 'notice', $actual['silenced'] );
		$this->assertArrayNotHasKey( 'warning', $actual['silenced'] );
		$this->assertEquals( 1, count( $actual['silenced']['notice'] ) );
	}
}
