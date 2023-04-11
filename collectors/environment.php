<?php declare(strict_types = 1);
/**
 * Environment data collector.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @extends QM_DataCollector<QM_Data_Environment>
 */
class QM_Collector_Environment extends QM_DataCollector {

	/**
	 * @var string
	 */
	public $id = 'environment';

	/**
	 * @var array<int, string>
	 */
	protected $php_vars = array(
		'max_execution_time',
		'memory_limit',
		'upload_max_filesize',
		'post_max_size',
		'display_errors',
		'log_errors',
	);

	public function get_storage(): QM_Data {
		return new QM_Data_Environment();
	}

	/**
	 * @param int $error_reporting
	 * @return array<string, bool>
	 */
	protected static function get_error_levels( $error_reporting ) {
		$levels = array(
			'E_ERROR' => false,
			'E_WARNING' => false,
			'E_PARSE' => false,
			'E_NOTICE' => false,
			'E_CORE_ERROR' => false,
			'E_CORE_WARNING' => false,
			'E_COMPILE_ERROR' => false,
			'E_COMPILE_WARNING' => false,
			'E_USER_ERROR' => false,
			'E_USER_WARNING' => false,
			'E_USER_NOTICE' => false,
			'E_STRICT' => false,
			'E_RECOVERABLE_ERROR' => false,
			'E_DEPRECATED' => false,
			'E_USER_DEPRECATED' => false,
			'E_ALL' => false,
		);

		foreach ( $levels as $level => $reported ) {
			if ( defined( $level ) ) {
				$c = constant( $level );
				if ( $error_reporting & $c ) {
					$levels[ $level ] = true;
				}
			}
		}

		return $levels;
	}

	/**
	 * @return void
	 */
	public function process() {

		global $wp_version;

		$mysql_vars = array(
			'key_buffer_size' => true,  # Key cache size limit
			'max_allowed_packet' => false, # Individual query size limit
			'max_connections' => false, # Max number of client connections
			'query_cache_limit' => true,  # Individual query cache size limit
			'query_cache_size' => true,  # Total cache size limit
			'query_cache_type' => 'ON',  # Query cache on or off
			'innodb_buffer_pool_size' => false, # The amount of memory allocated to the InnoDB buffer pool
		);

		/** @var QM_Collector_DB_Queries|null */
		$dbq = QM_Collectors::get( 'db_queries' );

		if ( $dbq ) {
			if ( method_exists( $dbq->wpdb, 'db_version' ) ) {
				$server = $dbq->wpdb->db_version();
				// query_cache_* deprecated since MySQL 5.7.20
				if ( version_compare( $server, '5.7.20', '>=' ) ) {
					unset( $mysql_vars['query_cache_limit'], $mysql_vars['query_cache_size'], $mysql_vars['query_cache_type'] );
				}
			}

			// phpcs:disable
			/** @var array<int, stdClass>|null */
			$variables = $dbq->wpdb->get_results( "
				SHOW VARIABLES
				WHERE Variable_name IN ( '" . implode( "', '", array_keys( $mysql_vars ) ) . "' )
			" );
			// phpcs:enable

			/** @var mysqli|false|null $dbh */
			$dbh = $dbq->wpdb->dbh;

			if ( is_object( $dbh ) ) {
				# mysqli or PDO
				$extension = get_class( $dbh );
			} else {
				# Who knows?
				$extension = null;
			}

			$client = mysqli_get_client_version();

			if ( $client ) {
				$client_version = implode( '.', QM_Util::get_client_version( $client ) );
				$client_version = sprintf( '%s (%s)', $client, $client_version );
			} else {
				$client_version = null;
			}

			$server_version = self::get_server_version( $dbq->wpdb );

			$info = array(
				'server-version' => $server_version,
				'extension' => $extension,
				'client-version' => $client_version,
				'user' => $dbq->wpdb->dbuser,
				'host' => $dbq->wpdb->dbhost,
				'database' => $dbq->wpdb->dbname,
			);

			$this->data->db = array(
				'info' => $info,
				'vars' => $mysql_vars,
				'variables' => $variables ?: array(),
			);
		}

		$php_data = array(
			'variables' => array(),
		);

		$php_data['version'] = phpversion();
		$php_data['sapi'] = php_sapi_name();
		$php_data['user'] = self::get_current_user();

		// https://www.php.net/supported-versions.php
		$php_data['old'] = version_compare( $php_data['version'], '7.4', '<' );

		foreach ( $this->php_vars as $setting ) {
			$php_data['variables'][ $setting ] = ini_get( $setting ) ?: null;
		}

		if ( function_exists( 'get_loaded_extensions' ) ) {
			$extensions = get_loaded_extensions();
			sort( $extensions, SORT_STRING | SORT_FLAG_CASE );
			$php_data['extensions'] = array_combine( $extensions, array_map( array( $this, 'get_extension_version' ), $extensions ) ) ?: array();
		} else {
			$php_data['extensions'] = array();
		}

		$php_data['error_reporting'] = error_reporting();
		$php_data['error_levels'] = self::get_error_levels( $php_data['error_reporting'] );

		$this->data->wp['version'] = $wp_version;
		$constants = array(
			'WP_DEBUG' => self::format_bool_constant( 'WP_DEBUG' ),
			'WP_DEBUG_DISPLAY' => self::format_bool_constant( 'WP_DEBUG_DISPLAY' ),
			'WP_DEBUG_LOG' => self::format_bool_constant( 'WP_DEBUG_LOG' ),
			'SCRIPT_DEBUG' => self::format_bool_constant( 'SCRIPT_DEBUG' ),
			'WP_CACHE' => self::format_bool_constant( 'WP_CACHE' ),
			'CONCATENATE_SCRIPTS' => self::format_bool_constant( 'CONCATENATE_SCRIPTS' ),
			'COMPRESS_SCRIPTS' => self::format_bool_constant( 'COMPRESS_SCRIPTS' ),
			'COMPRESS_CSS' => self::format_bool_constant( 'COMPRESS_CSS' ),
			'WP_ENVIRONMENT_TYPE' => self::format_bool_constant( 'WP_ENVIRONMENT_TYPE' ),
		);

		if ( function_exists( 'wp_get_environment_type' ) ) {
			$this->data->wp['environment_type'] = wp_get_environment_type();
		}

		$this->data->wp['constants'] = apply_filters( 'qm/environment-constants', $constants );

		if ( is_multisite() ) {
			$this->data->wp['constants']['SUNRISE'] = self::format_bool_constant( 'SUNRISE' );
		}

		if ( isset( $_SERVER['SERVER_SOFTWARE'] ) ) {
			$server = explode( ' ', wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) );
			$server = explode( '/', reset( $server ) );
		} else {
			$server = array( '' );
		}

		$server_version = $server[1] ?? null;

		if ( isset( $_SERVER['SERVER_ADDR'] ) ) {
			$address = wp_unslash( $_SERVER['SERVER_ADDR'] );
		} else {
			$address = null;
		}

		$this->data->php = $php_data;

		$this->data->server = array(
			'name' => $server[0],
			'version' => $server_version,
			'address' => $address,
			'host' => null,
			'OS' => null,
			'arch' => null,
		);

		if ( function_exists( 'php_uname' ) ) {
			$this->data->server['host'] = php_uname( 'n' );
			$this->data->server['OS'] = php_uname( 's' ) . ' ' . php_uname( 'r' );
			$this->data->server['arch'] = php_uname( 'm' );
		}

	}

	/**
	 * @param string $extension
	 * @return string
	 */
	public function get_extension_version( $extension ) {
		// Nothing is simple in PHP. The exif and mysqlnd extensions (and probably others) add a bunch of
		// crap to their version number, so we need to pluck out the first numeric value in the string.
		$version = trim( phpversion( $extension ) ?: '' );

		if ( ! $version ) {
			return $version;
		}

		$parts = explode( ' ', $version );

		foreach ( $parts as $part ) {
			if ( $part && is_numeric( $part[0] ) ) {
				$version = $part;
				break;
			}
		}

		return $version;
	}

	/**
	 * @param wpdb $db
	 * @return string
	 */
	protected static function get_server_version( wpdb $db ) {
		$version = null;

		if ( method_exists( $db, 'db_server_info' ) ) {
			$version = $db->db_server_info();
		}

		if ( ! $version ) {
			$version = $db->get_var( 'SELECT VERSION()' );
		}

		if ( ! $version ) {
			$version = __( 'Unknown', 'query-monitor' );
		}

		return $version;
	}

	/**
	 * @return string
	 */
	protected static function get_current_user() {

		$php_u = null;

		if ( function_exists( 'posix_getpwuid' ) && function_exists( 'posix_getuid' ) && function_exists( 'posix_getgrgid' ) ) {
			$u = posix_getpwuid( posix_getuid() );

			if ( isset( $u['gid'], $u['name'] ) ) {
				$g = posix_getgrgid( $u['gid'] );

				if ( isset( $g['name'] ) ) {
					$php_u = $u['name'] . ':' . $g['name'];
				}
			}
		}

		if ( empty( $php_u ) && isset( $_ENV['APACHE_RUN_USER'] ) ) {
			$php_u = $_ENV['APACHE_RUN_USER'];
			if ( isset( $_ENV['APACHE_RUN_GROUP'] ) ) {
				$php_u .= ':' . $_ENV['APACHE_RUN_GROUP'];
			}
		}

		if ( empty( $php_u ) && isset( $_SERVER['USER'] ) ) {
			$php_u = wp_unslash( $_SERVER['USER'] );
		}

		if ( empty( $php_u ) && function_exists( 'exec' ) ) {
			$php_u = exec( 'whoami' ); // phpcs:ignore
		}

		if ( empty( $php_u ) && function_exists( 'getenv' ) ) {
			$php_u = getenv( 'USERNAME' );
		}

		return $php_u;

	}

}

/**
 * @param array<string, QM_Collector> $collectors
 * @param QueryMonitor $qm
 * @return array<string, QM_Collector>
 */
function register_qm_collector_environment( array $collectors, QueryMonitor $qm ) {
	$collectors['environment'] = new QM_Collector_Environment();
	return $collectors;
}

add_filter( 'qm/collectors', 'register_qm_collector_environment', 20, 2 );
