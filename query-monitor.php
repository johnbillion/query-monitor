<?php
/**
 * Query Monitor plugin for WordPress
 *
 * @package   query-monitor
 * @link      https://github.com/johnbillion/query-monitor
 * @author    John Blackbourn <john@johnblackbourn.com>
 * @copyright 2009-2020 John Blackbourn
 * @license   GPL v2 or later
 *
 * Plugin Name:  Query Monitor
 * Description:  The Developer Tools Panel for WordPress.
 * Version:      3.6.6
 * Plugin URI:   https://querymonitor.com/
 * Author:       John Blackbourn
 * Author URI:   https://querymonitor.com/
 * Text Domain:  query-monitor
 * Domain Path:  /languages/
 * Requires PHP: 5.3.6
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

defined( 'ABSPATH' ) || die();

$qm_dir = dirname( __FILE__ );

require_once "{$qm_dir}/classes/Plugin.php";

if ( ! QM_Plugin::php_version_met() ) {
	add_action( 'admin_notices', 'QM_Plugin::php_version_nope' );
	return;
}

# No autoloaders for us. See https://github.com/johnbillion/query-monitor/issues/7
foreach ( array( 'Activation', 'Util', 'QM' ) as $qm_class ) {
	require_once "{$qm_dir}/classes/{$qm_class}.php";
}

QM_Activation::init( __FILE__ );

if ( ! QM_Plugin::php_version_met() ) {
	return;
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once "{$qm_dir}/classes/CLI.php";
	QM_CLI::init( __FILE__ );
}

if ( defined( 'QM_DISABLED' ) && QM_DISABLED ) {
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

foreach ( array( 'QueryMonitor', 'Backtrace', 'Collectors', 'Collector', 'Dispatchers', 'Dispatcher', 'Hook', 'Output', 'Timer' ) as $qm_class ) {
	require_once "{$qm_dir}/classes/{$qm_class}.php";
}

unset(
	$qm_dir,
	$qm_class
);

QueryMonitor::init( __FILE__ );
