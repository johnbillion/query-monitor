<?php
/**
 * Query Monitor plugin for WordPress
 *
 * @package   query-monitor
 * @link      https://github.com/johnbillion/query-monitor
 * @author    John Blackbourn
 * @copyright 2009-2024 John Blackbourn
 * @license   GPL v2 or later
 *
 * Plugin Name:  Query Monitor
 * Description:  The developer tools panel for WordPress.
 * Version:      3.17.0
 * Plugin URI:   https://querymonitor.com/
 * Author:       John Blackbourn
 * Author URI:   https://querymonitor.com/
 * Text Domain:  query-monitor
 * Domain Path:  /languages/
 * Requires at least: 5.9
 * Requires PHP: 7.4
 * License URI:  https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * License:      GPL v2 or later
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

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'QM_VERSION', '3.17.0' );

// This must be required before vendor/autoload.php so QM can serve its own message about PHP compatibility.
require_once __DIR__ . '/classes/PHP.php';

if ( ! QM_PHP::version_met() ) {
	add_action( 'all_admin_notices', 'QM_PHP::php_version_nope' );
	return;
}

if ( ! file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	add_action( 'all_admin_notices', 'QM_PHP::vendor_nope' );
	return;
}

require_once __DIR__ . '/vendor/autoload.php';

// Safety check to ensure the autoloader is operational.
if ( ! class_exists( 'QM_Activation' ) ) {
	return;
}

QM_Activation::init( __FILE__ );

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	QM_CLI::init( __FILE__ );
}

if ( defined( 'QM_DISABLED' ) && QM_DISABLED ) {
	return;
}

if ( defined( 'WP_INSTALLING' ) && WP_INSTALLING ) {
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

# Don't load QM during plugin updates to prevent function signature changes causing issues between versions.
if ( is_admin() ) {
	if ( isset( $_GET['action'] ) && 'upgrade-plugin' === $_GET['action'] ) {
		return;
	}

	if ( isset( $_POST['action'] ) && 'update-plugin' === $_POST['action'] ) {
		return;
	}
}

QueryMonitor::init( __FILE__ )->set_up();
