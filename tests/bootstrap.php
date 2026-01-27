<?php declare(strict_types = 1);

// Define QM_TESTS to allow the plugin to load during CLI tests
define( 'QM_TESTS', true );

$_tests_dir = dirname( __DIR__ ) . '/vendor/wp-phpunit/wp-phpunit/';

// Get access to tests_add_filter() function
require_once $_tests_dir . 'includes/functions.php';

// Manually load the plugin
tests_add_filter( 'muplugins_loaded', fn() => require_once dirname( __DIR__ ) . '/query-monitor.php' );

// Start up the WP testing environment
require_once $_tests_dir . 'includes/bootstrap.php';
