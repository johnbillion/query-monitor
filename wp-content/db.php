<?php
/**
 * Plugin Name: Query Monitor Database Class
 *
 * *********************************************************************
 *
 * Ensure this file is symlinked to your wp-content directory to provide
 * additional database query information in Query Monitor's output.
 *
 * *********************************************************************
 *
 * @package query-monitor
 */

defined( 'ABSPATH' ) || die();

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
$plugin = "{$qm_dir}/classes/Plugin.php";

if ( ! is_readable( $plugin ) ) {
	return;
}
require_once $plugin;

if ( ! QM_Plugin::php_version_met() ) {
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

	public $qm_php_vars = array(
		'max_execution_time'  => null,
		'memory_limit'        => null,
		'upload_max_filesize' => null,
		'post_max_size'       => null,
		'display_errors'      => null,
		'log_errors'          => null,
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
	 * Perform a MySQL database query, using current database connection.
	 *
	 * @see wpdb::query()
	 *
	 * @param string $query Database query
	 * @return int|false Number of rows affected/selected or false on error
	 */
	public function query( $query ) {
		if ( ! $this->ready ) {
			if ( isset( $this->check_current_query ) ) {
				// This property was introduced in WP 4.2
				$this->check_current_query = true;
			}
			return false;
		}

		if ( $this->show_errors ) {
			$this->hide_errors();
		}

		$result = parent::query( $query );

		if ( ! SAVEQUERIES ) {
			return $result;
		}

		$i = $this->num_queries - 1;
		$this->queries[ $i ]['trace'] = new QM_Backtrace( array(
			'ignore_frames' => 1,
		) );

		if ( ! isset( $this->queries[ $i ][3] ) ) {
			$this->queries[ $i ][3] = $this->time_start;
		}

		if ( $this->last_error ) {
			$code = 'qmdb';
			if ( $this->use_mysqli ) {
				if ( $this->dbh instanceof mysqli ) {
					$code = mysqli_errno( $this->dbh );
				}
			} else {
				if ( is_resource( $this->dbh ) ) {
					// Please do not report this code as a PHP 7 incompatibility. Observe the surrounding logic.
					// @codingStandardsIgnoreLine
					$code = mysql_errno( $this->dbh );
				}
			}
			$this->queries[ $i ]['result'] = new WP_Error( $code, $this->last_error );
		} else {
			$this->queries[ $i ]['result'] = $result;
		}

		return $result;
	}

}

// phpcs:ignore WordPress.WP.GlobalVariablesOverride.OverrideProhibited
$wpdb = new QM_DB( DB_USER, DB_PASSWORD, DB_NAME, DB_HOST );
