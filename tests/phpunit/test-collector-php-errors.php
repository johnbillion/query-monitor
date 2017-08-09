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
		$actual = $this->collector->is_plugin_file(
			'foo', ABSPATH . 'wp-includes/plugin.php'
		);

		$this->assertFalse( $actual );
	}

	function test_it_knows_theme_file_is_not_in_plugin() {
		$actual = $this->collector->is_plugin_file(
			'foo', ABSPATH . 'wp-content/themes/foo/taxonomy.php'
		);

		$this->assertFalse( $actual );
	}

	function test_it_knows_another_plugin_file_is_not_in_plugin() {
		$actual = $this->collector->is_plugin_file(
			'foo', ABSPATH . 'wp-content/plugins/another-plugin/taxonomy.php'
		);

		$this->assertFalse( $actual );
	}

	function test_it_knows_empty_file_path_is_not_in_plugin() {
		$actual = $this->collector->is_plugin_file(
			'foo', ABSPATH . ''
		);

		$this->assertFalse( $actual );
	}

	function test_it_knows_empty_plugin_name_is_not_in_plugin() {
		$actual = $this->collector->is_plugin_file(
			'', ABSPATH . 'wp-content/plugins/foo/foo.php'
		);

		$this->assertFalse( $actual );
	}

	function test_it_knows_plugin_file_is_in_plugin() {
		$actual = $this->collector->is_plugin_file(
			'foo', ABSPATH . 'wp-content/plugins/foo/foo.php'
		);

		$this->assertTrue( $actual );
	}

	function test_it_knows_internal_plugin_file_is_in_plugin() {
		$actual = $this->collector->is_plugin_file(
			'foo', ABSPATH . 'wp-content/plugins/foo/includes/A/B/foo.php'
		);

		$this->assertTrue( $actual );
	}

	function test_it_knows_plugin_extension_file_is_not_in_plugin() {
		$actual = $this->collector->is_plugin_file(
			'foo', ABSPATH . 'wp-content/plugins/foo-extension/foo-extension.php'
		);

		$this->assertFalse( $actual );
	}

	function test_it_will_not_filter_any_error_by_default() {
		$table = array();
		$errors = array(
			'notice' => array(
				'abc' => array(
					(object) array(
						'errno' => 2,
						'file'  => ABSPATH . 'wp-content/plugins/foo/foo.php'
					)
				)
			)
		);

		$actual = $this->collector->filter_reportable_errors( $errors, $table );
		$this->assertEquals( 1, count( $actual['notice'] ) );
	}

	function test_it_will_filter_notices_from_plugin() {
		$table = array(
			'foo' => ~E_NOTICE,
		);

		$errors = array(
			'notice' => array(
				'abc' => (object) array(
					'errno' => E_NOTICE,
					'file'  => ABSPATH . 'wp-content/plugins/foo/foo.php'
				)
			)
		);

		$actual = $this->collector->filter_reportable_errors( $errors, $table );
		$this->assertEmpty( $actual['notice'] );
	}

	function test_it_can_process_and_filter_out_unreportable_errors() {
		add_filter( 'qm/collect/silent_php_errors', function( $table ) {
			$table['foo'] = ~E_NOTICE;
			$table['bar'] = E_ALL;

			return $table;
		} );

		$errors = array(
			'notice' => array(
				'abc' => (object) array(
					'errno' => E_NOTICE,
					'file'  => ABSPATH . 'wp-content/plugins/foo/foo.php'
				),
				'efg' => (object) array(
					'errno' => E_NOTICE,
					'file'  => ABSPATH . 'wp-content/plugins/bar/bar.php'
				)
			),
		);

		$this->collector->set_php_errors( $errors );
		$this->collector->process();

		$actual = $this->collector->get_data();

		$this->assertEquals( 1, count( $actual['filtered_errors']['notice'] ) );
	}
}
