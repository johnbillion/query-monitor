<?php
/**
 * Environment data collector.
 *
 * @package query-monitor
 */

class QM_Collector_Environment extends QM_Collector {

	public $id = 'environment';
	protected $php_vars = array(
		'max_execution_time',
		'memory_limit',
		'upload_max_filesize',
		'post_max_size',
		'display_errors',
		'log_errors',
	);

	public function name() {
		return __( 'Environment', 'query-monitor' );
	}

	public function __construct() {

		global $wpdb;

		parent::__construct();

		# If QM_DB is in place then we'll use the values which were
		# caught early before any plugins had a chance to alter them

		foreach ( $this->php_vars as $setting ) {
			if ( isset( $wpdb->qm_php_vars ) and isset( $wpdb->qm_php_vars[ $setting ] ) ) {
				$val = $wpdb->qm_php_vars[ $setting ];
			} else {
				$val = ini_get( $setting );
			}
			$this->data['php']['variables'][ $setting ]['before'] = $val;
		}

	}

	protected static function get_error_levels( $error_reporting ) {
		$levels = array(
			'E_ERROR'             => false,
			'E_WARNING'           => false,
			'E_PARSE'             => false,
			'E_NOTICE'            => false,
			'E_CORE_ERROR'        => false,
			'E_CORE_WARNING'      => false,
			'E_COMPILE_ERROR'     => false,
			'E_COMPILE_WARNING'   => false,
			'E_USER_ERROR'        => false,
			'E_USER_WARNING'      => false,
			'E_USER_NOTICE'       => false,
			'E_STRICT'            => false,
			'E_RECOVERABLE_ERROR' => false,
			'E_DEPRECATED'        => false,
			'E_USER_DEPRECATED'   => false,
			'E_ALL'               => false,
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

	public function process() {

		global $wp_version;

		$mysql_vars = array(
			'key_buffer_size'    => true,  # Key cache size limit
			'max_allowed_packet' => false, # Individual query size limit
			'max_connections'    => false, # Max number of client connections
			'query_cache_limit'  => true,  # Individual query cache size limit
			'query_cache_size'   => true,  # Total cache size limit
			'query_cache_type'   => 'ON',  # Query cache on or off
		);

		if ( $dbq = QM_Collectors::get( 'db_queries' ) ) {

			foreach ( $dbq->db_objects as $id => $db ) {

				if ( method_exists( $db, 'db_version' ) ) {
					$server = $db->db_version();
					// query_cache_* deprecated since MySQL 5.7.20
					if ( version_compare( $server, '5.7.20', '>=' ) ) {
						unset( $mysql_vars['query_cache_limit'], $mysql_vars['query_cache_size'], $mysql_vars['query_cache_type'] );
					}
				} else {
					$server = null;
				}

				$variables = $db->get_results( "
					SHOW VARIABLES
					WHERE Variable_name IN ( '" . implode( "', '", array_keys( $mysql_vars ) ) . "' )
				" );

				if ( is_resource( $db->dbh ) ) {
					# Old mysql extension
					$extension = 'mysql';
				} elseif ( is_object( $db->dbh ) ) {
					# mysqli or PDO
					$extension = get_class( $db->dbh );
				} else {
					# Who knows?
					$extension = null;
				}

				if ( isset( $db->use_mysqli ) && $db->use_mysqli ) {
					$client = mysqli_get_client_version();
					$info   = mysqli_get_server_info( $db->dbh );
				} else {
					if ( preg_match( '|[0-9]{1,2}\.[0-9]{1,2}\.[0-9]{1,2}|', mysql_get_client_info(), $matches ) ) {
						$client = $matches[0];
					} else {
						$client = null;
					}
					$info = mysql_get_server_info( $db->dbh );
				}

				if ( false !== strpos( $info, 'MariaDB' ) ) {
					$rdbms = 'MariaDB';
				} else {
					$rdbms = 'MySQL';
				}

				if ( $client ) {
					$client_version = implode( '.', QM_Util::get_client_version( $client ) );
					$client_version = sprintf( '%s (%s)', $client, $client_version );
				} else {
					$client_version = null;
				}

				$info = array(
					'rdbms'          => $rdbms,
					'server version' => $server,
					'extension'      => $extension,
					'client version' => $client_version,
					'user'           => $db->dbuser,
					'host'           => $db->dbhost,
					'database'       => $db->dbname,
				);

				$this->data['db'][ $id ] = array(
					'info'      => $info,
					'vars'      => $mysql_vars,
					'variables' => $variables,
				);

			}
		}

		$this->data['php']['version'] = phpversion();
		$this->data['php']['sapi']    = php_sapi_name();
		$this->data['php']['user']    = self::get_current_user();
		$this->data['php']['old']     = version_compare( $this->data['php']['version'], 7, '<' );

		foreach ( $this->php_vars as $setting ) {
			$this->data['php']['variables'][ $setting ]['after'] = ini_get( $setting );
		}

		if ( defined( 'SORT_FLAG_CASE' ) ) {
			// phpcs:ignore PHPCompatibility.PHP.NewConstants
			$sort_flags = SORT_STRING | SORT_FLAG_CASE;
		} else {
			$sort_flags = SORT_STRING;
		}

		if ( is_callable( 'get_loaded_extensions' ) ) {
			$extensions = get_loaded_extensions();
			sort( $extensions, $sort_flags );
			$this->data['php']['extensions'] = array_combine( $extensions, array_map( array( $this, 'get_extension_version' ), $extensions ) );
		} else {
			$this->data['php']['extensions'] = array();
		}

		$this->data['php']['error_reporting'] = error_reporting();
		$this->data['php']['error_levels']    = self::get_error_levels( $this->data['php']['error_reporting'] );

		$this->data['wp'] = array(
			'version'             => $wp_version,
			'WP_DEBUG'            => self::format_bool_constant( 'WP_DEBUG' ),
			'WP_DEBUG_DISPLAY'    => self::format_bool_constant( 'WP_DEBUG_DISPLAY' ),
			'WP_DEBUG_LOG'        => self::format_bool_constant( 'WP_DEBUG_LOG' ),
			'SCRIPT_DEBUG'        => self::format_bool_constant( 'SCRIPT_DEBUG' ),
			'WP_CACHE'            => self::format_bool_constant( 'WP_CACHE' ),
			'CONCATENATE_SCRIPTS' => self::format_bool_constant( 'CONCATENATE_SCRIPTS' ),
			'COMPRESS_SCRIPTS'    => self::format_bool_constant( 'COMPRESS_SCRIPTS' ),
			'COMPRESS_CSS'        => self::format_bool_constant( 'COMPRESS_CSS' ),
			'WP_LOCAL_DEV'        => self::format_bool_constant( 'WP_LOCAL_DEV' ),
		);

		if ( is_multisite() ) {
			$this->data['wp']['SUNRISE'] = self::format_bool_constant( 'SUNRISE' );
		}

		if ( isset( $_SERVER['SERVER_SOFTWARE'] ) ) { // WPCS: input var ok
			$server = explode( ' ', wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ); // WPCS: sanitization ok, input var ok
			$server = explode( '/', reset( $server ) );
		} else {
			$server = array( '' );
		}

		if ( isset( $server[1] ) ) {
			$server_version = $server[1];
		} else {
			$server_version = null;
		}

		if ( isset( $_SERVER['SERVER_ADDR'] ) ) { // WPCS: input var ok
			$address = wp_unslash( $_SERVER['SERVER_ADDR'] ); // WPCS: sanitization ok, input var ok
		} else {
			$address = null;
		}

		$this->data['server'] = array(
			'name'    => $server[0],
			'version' => $server_version,
			'address' => $address,
			'host'    => php_uname( 'n' ),
		);

	}

	public function get_extension_version( $extension ) {
		// Nothing is simple in PHP. The exif and mysqlnd extensions (and probably others) add a bunch of
		// crap to their version number, so we need to pluck out the first numeric value in the string.
		$version = phpversion( $extension );

		if ( ! $version ) {
			return $version;
		}

		$parts = explode( ' ', $version );

		foreach ( $parts as $part ) {
			if ( is_numeric( $part[0] ) ) {
				$version = $part;
				break;
			}
		}

		return $version;
	}

	protected static function get_current_user() {

		$php_u = null;

		if ( function_exists( 'posix_getpwuid' ) ) {
			$u = posix_getpwuid( posix_getuid() );
			$g = posix_getgrgid( $u['gid'] );
			$php_u = $u['name'] . ':' . $g['name'];
		}

		if ( empty( $php_u ) and isset( $_ENV['APACHE_RUN_USER'] ) ) {
			$php_u = $_ENV['APACHE_RUN_USER'];
			if ( isset( $_ENV['APACHE_RUN_GROUP'] ) ) {
				$php_u .= ':' . $_ENV['APACHE_RUN_GROUP'];
			}
		}

		if ( empty( $php_u ) and isset( $_SERVER['USER'] ) ) { // WPCS: input var ok
			$php_u = wp_unslash( $_SERVER['USER'] ); // WPCS: sanitization ok, input var ok
		}

		if ( empty( $php_u ) and function_exists( 'exec' ) ) {
			$php_u = exec( 'whoami' ); // @codingStandardsIgnoreLine
		}

		if ( empty( $php_u ) and function_exists( 'getenv' ) ) {
			$php_u = getenv( 'USERNAME' );
		}

		return $php_u;

	}

}

function register_qm_collector_environment( array $collectors, QueryMonitor $qm ) {
	$collectors['environment'] = new QM_Collector_Environment;
	return $collectors;
}

add_filter( 'qm/collectors', 'register_qm_collector_environment', 20, 2 );
