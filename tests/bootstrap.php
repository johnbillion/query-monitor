<?php declare(strict_types = 1);

// Define QM_TESTS to allow the plugin to load during CLI tests
define( 'QM_TESTS', true );

$_tests_dir = dirname( __DIR__ ) . '/vendor/wp-phpunit/wp-phpunit/';

// Get access to tests_add_filter() function
require_once $_tests_dir . 'includes/functions.php';

// Manually load the plugin
tests_add_filter( 'muplugins_loaded', fn() => require_once dirname( __DIR__ ) . '/query-monitor.php' );

// Register the real themes directory and switch to a real theme after WordPress is loaded
tests_add_filter( 'setup_theme', function() {
	register_theme_directory( '/var/www/html/wp-content/themes' );
	switch_theme( 'twentytwentyfive' );
} );

// Start up the WP testing environment
require_once $_tests_dir . 'includes/bootstrap.php';
