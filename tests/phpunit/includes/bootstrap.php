<?php

$_tests_dir = getenv('WP_TESTS_DIR');

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find {$_tests_dir}/includes/functions.php, have you run bin/install-wp-tests.sh ?";
	exit( 1 );
}

require_once $_tests_dir . '/includes/functions.php';

require dirname( __FILE__ ) . '/dummy-objects.php';

function _manually_load_plugin() {
	define( 'QM_TESTS', true );
	require dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/query-monitor.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

require $_tests_dir . '/includes/bootstrap.php';

require dirname( __FILE__ ) . '/qm-test.php';
