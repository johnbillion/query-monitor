<?php
/**
 * Plugin Name: Query Monitor Database Class (Drop-in)
 * Description: Database drop-in for Query Monitor, the developer tools panel for WordPress.
 * Version:     3.19.0
 * Plugin URI:  https://querymonitor.com/
 * Author:      John Blackbourn
 * Author URI:  https://querymonitor.com/
 *
 * *********************************************************************
 *
 * Ensure this file is symlinked to your wp-content directory to provide
 * additional database query information in Query Monitor's output.
 *
 * @see https://querymonitor.com/help/db-php-symlink/
 *
 * *********************************************************************
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'DB_USER' ) ) {
	return;
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

// This must be required before vendor/autoload.php so QM can serve its own message about PHP compatibility.
$qm_dir = dirname( dirname( __FILE__ ) );
$qm_php = "{$qm_dir}/classes/PHP.php";

if ( ! is_readable( $qm_php ) ) {
	return;
}
require_once $qm_php;

if ( ! QM_PHP::version_met() ) {
	return;
}

if ( ! file_exists( "{$qm_dir}/vendor/autoload.php" ) ) {
	add_action( 'all_admin_notices', 'QM_PHP::vendor_nope' );
	return;
}

require_once "{$qm_dir}/vendor/autoload.php";

// Safety check to ensure the autoloader is operational.
if ( ! class_exists( 'QM_Backtrace' ) ) {
	return;
}

if ( ! defined( 'SAVEQUERIES' ) ) {
	define( 'SAVEQUERIES', true );
}

// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
$wpdb = new QM_DB( DB_USER, DB_PASSWORD, DB_NAME, DB_HOST );
