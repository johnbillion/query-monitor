<?php
/**
 * Plugin Name: Query Monitor Database Class (Drop-in)
 * Description: Database class for Query Monitor, the developer tools panel for WordPress.
 * Version:     3.9.0
 * Plugin URI:  https://querymonitor.com/
 * Author:      John Blackbourn
 * Author URI:  https://querymonitor.com/
 *
 * *********************************************************************
 *
 * Ensure this file is symlinked to your wp-content directory to provide
 * additional database query information in Query Monitor's output.
 *
 * @see https://github.com/johnbillion/query-monitor/wiki/db.php-Symlink
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

if ( 'cli' === php_sapi_name() && ! defined( 'QM_TESTS' ) ) {
	# For the time being, let's not load QM when using the CLI because we've no persistent storage and no means of
	# outputting collected data on the CLI. This will hopefully change in a future version of QM.
	return;
}

if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
	# Let's not load QM during cron events for the same reason as above.
	return;
}

# No autoloaders for us. See https://github.com/johnbillion/query-monitor/issues/7
$qm_dir = dirname( dirname( __FILE__ ) );
$qm_php = "{$qm_dir}/classes/PHP.php";

if ( ! is_readable( $qm_php ) ) {
	return;
}
require_once $qm_php;

if ( ! QM_PHP::version_met() ) {
	return;
}

$backtrace = "{$qm_dir}/classes/Backtrace.php";
if ( ! is_readable( $backtrace ) ) {
	return;
}
require_once $backtrace;

if ( ! defined( 'SAVEQUERIES' ) ) {
	define( 'SAVEQUERIES', true );
}

class QM_DB extends wpdb {

	/**
	 * @var float
	 */
	public $time_start;

	/**
	 * @var array<string, string|null>
	 */
	public $qm_php_vars = array(
		'max_execution_time' => null,
		'memory_limit' => null,
		'upload_max_filesize' => null,
		'post_max_size' => null,
		'display_errors' => null,
		'log_errors' => null,
	);

	/**
	 * Class constructor
	 */
	public function __construct( $dbuser, $dbpassword, $dbname, $dbhost ) {

		foreach ( $this->qm_php_vars as $setting => &$val ) {
			$val = ini_get( $setting );
		}

		parent::__construct( $dbuser, $dbpassword, $dbname, $dbhost );

	}

	/**
	 * Performs a MySQL database query, using current database connection.
	 *
	 * @see wpdb::query()
	 *
	 * @param string $query Database query
	 * @return int|bool Boolean true for CREATE, ALTER, TRUNCATE and DROP queries. Number of rows
	 *                  affected/selected for all other queries. Boolean false on error.
	 */
	public function query( $query ) {
		if ( $this->show_errors ) {
			$this->hide_errors();
		}

		$result = parent::query( $query );
		$i = $this->num_queries - 1;

		if ( did_action( 'qm/cease' ) ) {
			// It's not possible to prevent the parent class from logging queries because it reads
			// the `SAVEQUERIES` constant and I don't want to override more methods than necessary.
			$this->queries = array();
		}

		if ( ! isset( $this->queries[ $i ] ) ) {
			return $result;
		}

		$this->queries[ $i ]['trace'] = new QM_Backtrace();

		if ( ! isset( $this->queries[ $i ][3] ) ) {
			$this->queries[ $i ][3] = $this->time_start;
		}

		if ( $this->last_error ) {
			$code = 'qmdb';

			if ( $this->dbh instanceof mysqli ) {
				$code = mysqli_errno( $this->dbh );
			}

			if ( is_resource( $this->dbh ) ) {
				// Please do not report this code as a PHP 7 incompatibility. Observe the surrounding logic.
				// phpcs:ignore
				$code = mysql_errno( $this->dbh );
			}

			$this->queries[ $i ]['result'] = new WP_Error( $code, $this->last_error );
		} else {
			$this->queries[ $i ]['result'] = $result;
		}

		return $result;
	}

}

// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
$wpdb = new QM_DB( DB_USER, DB_PASSWORD, DB_NAME, DB_HOST );
