<?php
/*
Plugin Name: Query Monitor
Description: Monitoring of database queries, hooks, conditionals and more.
Version:     2.13.4
Plugin URI:  https://github.com/johnbillion/query-monitor
Author:      John Blackbourn
Author URI:  https://johnblackbourn.com/
Text Domain: query-monitor
Domain Path: /languages/
License:     GPL v2 or later

Copyright 2009-2017 John Blackbourn

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

*/

defined( 'ABSPATH' ) or die();

$qm_dir = dirname( __FILE__ );

# No autoloaders for us. See https://github.com/johnbillion/query-monitor/issues/7
foreach ( array( 'Plugin', 'Activation', 'Util' ) as $qm_class ) {
	require_once "{$qm_dir}/classes/{$qm_class}.php";
}

QM_Activation::init( __FILE__ );

if ( defined( 'QM_DISABLED' ) and QM_DISABLED ) {
	return;
}

if ( 'cli' === php_sapi_name() && ! defined( 'QM_TESTS' ) ) {
	# For the time being, let's not load QM when using the CLI because we've no persistent storage and no means of
	# outputting collected data on the CLI. This will hopefully change in a future version of QM.
	return;
}

if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
	# Let's not load QM during cron events for the same reason as above.
	return;
}

foreach ( array( 'QueryMonitor', 'Backtrace', 'Collectors', 'Collector', 'Dispatchers', 'Dispatcher', 'Output' ) as $qm_class ) {
	require_once "{$qm_dir}/classes/{$qm_class}.php";
}

QueryMonitor::init( __FILE__ );
