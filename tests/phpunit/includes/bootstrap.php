<?php

$_qm_dir = getcwd();

require_once $_qm_dir . '/vendor/autoload.php';

$_env_dir = dirname( dirname( __DIR__ ) );

if ( is_readable( $_env_dir . '/.env' ) ) {
	$dotenv = Dotenv\Dotenv::create( $_env_dir );
	$dotenv->load();
}

$_tests_dir = getenv( 'WP_PHPUNIT__DIR' );

require_once $_tests_dir . '/includes/functions.php';

require_once __DIR__ . '/dummy-objects.php';

tests_add_filter( 'muplugins_loaded', function() use ( $_qm_dir ) {
	define( 'QM_TESTS', true );
	require_once $_qm_dir . '/query-monitor.php';
} );

require_once $_tests_dir . '/includes/bootstrap.php';

require_once __DIR__ . '/qm-test.php';
require_once __DIR__ . '/qm-test-backtrace.php';
