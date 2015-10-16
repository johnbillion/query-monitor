<?php

$_tests_dir = getenv('WP_TESTS_DIR');
if ( !$_tests_dir ) $_tests_dir = '/tmp/wordpress-tests-lib';

require_once $_tests_dir . '/includes/functions.php';

require dirname( __FILE__ ) . '/dummy-objects.php';

function _manually_load_plugin() {
	define( 'QM_TESTS', true );
	require dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/query-monitor.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

require $_tests_dir . '/includes/bootstrap.php';

require dirname( __FILE__ ) . '/qm-test.php';
