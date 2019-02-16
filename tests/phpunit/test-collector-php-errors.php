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

	function test_it_knows_null_flag_is_always_reportable() {
		$actual = $this->collector->is_reportable_error(
			E_NOTICE, null
		);

		$this->assertTrue( $actual );
	}

	function test_it_knows_error_in_flags_is_reportable() {
		$actual = $this->collector->is_reportable_error(
			E_NOTICE, E_ALL & ~E_WARNING
		);

		$this->assertTrue( $actual );
	}

	function test_it_knows_error_outside_flags_is_not_reportable() {
		$actual = $this->collector->is_reportable_error(
			E_NOTICE, E_ALL & ~E_NOTICE
		);

		$this->assertFalse( $actual );
	}

	function test_it_knows_same_error_and_flag_is_reportable() {
		$actual = $this->collector->is_reportable_error(
			E_NOTICE, E_NOTICE
		);

		$this->assertTrue( $actual );
	}

	function test_it_knows_core_file_is_not_in_plugin() {
		$component = QM_Util::get_file_component( ABSPATH . 'wp-includes/plugin.php' );
		$actual = $this->collector->is_affected_component(
			$component, 'plugin', 'foo'
		);

		$this->assertFalse( $actual );
	}

	function test_it_knows_theme_file_is_not_in_plugin() {
		$component = QM_Util::get_file_component( WP_CONTENT_DIR . '/themes/foo/taxonomy.php' );
		$actual = $this->collector->is_affected_component(
			$component, 'plugin', 'foo'
		);

		$this->assertFalse( $actual );
	}

	function test_it_knows_another_plugin_file_is_not_in_plugin() {
		$component = QM_Util::get_file_component( WP_PLUGIN_DIR . '/bar/taxonomy.php' );
		$actual = $this->collector->is_affected_component(
			$component, 'plugin', 'foo'
		);

		$this->assertFalse( $actual );
	}

	function test_it_knows_empty_file_path_is_not_in_plugin() {
		$component = QM_Util::get_file_component( ABSPATH );
		$actual = $this->collector->is_affected_component(
			$component, 'plugin', 'foo'
		);

		$this->assertFalse( $actual );
	}

	function test_it_knows_empty_plugin_name_is_not_in_plugin() {
		$component = QM_Util::get_file_component( WP_PLUGIN_DIR . '/bar/taxonomy.php' );
		$actual = $this->collector->is_affected_component(
			$component, '', ''
		);

		$this->assertFalse( $actual );
	}

	function test_it_knows_plugin_file_is_in_plugin() {
		$component = QM_Util::get_file_component( WP_PLUGIN_DIR . '/foo/taxonomy.php' );
		$actual = $this->collector->is_affected_component(
			$component, 'plugin', 'foo'
		);

		$this->assertTrue( $actual );
	}

	function test_it_knows_theme_file_is_in_theme() {
		$component = QM_Util::get_file_component( get_stylesheet_directory() . '/taxonomy.php' );
		$actual = $this->collector->is_affected_component(
			$component, 'theme', 'stylesheet'
		);

		$this->assertTrue( $actual );
	}

	function test_it_knows_core_file_is_in_core() {
		$component = QM_Util::get_file_component( ABSPATH . 'wp-includes/plugin.php' );
		$actual = $this->collector->is_affected_component(
			$component, 'core', 'core'
		);

		$this->assertTrue( $actual );
	}

	function test_it_knows_folderless_plugin_file_is_in_plugin() {
		$component = QM_Util::get_file_component( WP_PLUGIN_DIR . '/foo.php' );
		$actual = $this->collector->is_affected_component(
			$component, 'plugin', 'foo.php'
		);

		$this->assertTrue( $actual );
	}

	function test_it_knows_internal_plugin_file_is_in_plugin() {
		$component = QM_Util::get_file_component( WP_PLUGIN_DIR . '/foo/includes/A/B/foo.php' );
		$actual = $this->collector->is_affected_component(
			$component, 'plugin', 'foo'
		);

		$this->assertTrue( $actual );
	}

	function test_it_knows_plugin_extension_file_is_not_in_plugin() {
		$component = QM_Util::get_file_component( WP_PLUGIN_DIR . '/foo-extension/foo-extension.php.php' );
		$actual = $this->collector->is_affected_component(
			$component, 'plugin', 'foo'
		);

		$this->assertFalse( $actual );
	}

	function test_it_will_not_filter_any_error_by_default() {
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

	function test_it_will_filter_notices_from_plugin() {
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
