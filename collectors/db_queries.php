<?php declare(strict_types = 1);
/**
 * Database query collector.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'SAVEQUERIES' ) ) {
	define( 'SAVEQUERIES', true );
}
if ( ! defined( 'QM_DB_EXPENSIVE' ) ) {
	define( 'QM_DB_EXPENSIVE', 0.05 );
}

if ( SAVEQUERIES && property_exists( $GLOBALS['wpdb'], 'save_queries' ) ) {
	$GLOBALS['wpdb']->save_queries = true;
}

/**
 * @extends QM_DataCollector<QM_Data_DB_Queries>
 */
class QM_Collector_DB_Queries extends QM_DataCollector {

	/**
	 * @var string
	 */
	public $id = 'db_queries';

	/**
	 * @var wpdb
	 */
	public $wpdb;

	public function get_storage(): QM_Data {
		return new QM_Data_DB_Queries();
	}

	/**
	 * @return mixed[]|false
	 */
	public function get_errors() {
		if ( ! empty( $this->data->errors ) ) {
			return $this->data->errors;
		}
		return false;
	}

	/**
	 * @return mixed[]|false
	 */
	public function get_expensive() {
		if ( ! empty( $this->data->expensive ) ) {
			return $this->data->expensive;
		}
		return false;
	}

	/**
	 * @param array<string, mixed> $row
	 * @return bool
	 */
	public static function is_expensive( array $row ) {
		return $row['ltime'] > QM_DB_EXPENSIVE;
	}

	/**
	 * @return void
	 */
	public function process() {
		$this->data->total_qs = 0;
		$this->data->total_time = 0;
		$this->data->errors = array();
		$this->process_db_object();
	}

	/**
	 * @param string $caller
	 * @param float $ltime
	 * @param string $type
	 * @return void
	 */
	protected function log_caller( $caller, $ltime, $type ) {

		if ( ! isset( $this->data->times[ $caller ] ) ) {
			$this->data->times[ $caller ] = array(
				'caller' => $caller,
				'ltime' => 0,
				'types' => array(),
			);
		}

		$this->data->times[ $caller ]['ltime'] += $ltime;

		if ( isset( $this->data->times[ $caller ]['types'][ $type ] ) ) {
			$this->data->times[ $caller ]['types'][ $type ]++;
		} else {
			$this->data->times[ $caller ]['types'][ $type ] = 1;
		}

	}

	/**
	 * @return void
	 */
	public function process_db_object() {
		global $wp_the_query, $wpdb;

		$this->wpdb = $wpdb;

		// With SAVEQUERIES defined as false, `wpdb::queries` is empty but `wpdb::num_queries` is not.
		if ( empty( $wpdb->queries ) ) {
			$this->data->total_qs += $wpdb->num_queries;
			return;
		}

		$types = array();
		$total_time = 0;
		$has_result = false;
		$has_trace = false;
		$i = 0;
		$request = trim( $wp_the_query->request ?: '' );

		if ( method_exists( $wpdb, 'remove_placeholder_escape' ) ) {
			$request = $wpdb->remove_placeholder_escape( $request );
		}

		/**
		 * @phpstan-var array{
		 *   0: string,
		 *   1: float,
		 *   2: string,
		 *   trace?: QM_Backtrace,
		 *   result?: int|bool|WP_Error,
		 * }|array{
		 *   query: string,
		 *   elapsed: float,
		 *   debug: string,
		 * } $query
		 */
		foreach ( $wpdb->queries as $query ) {
			$callers = array();

			if ( isset( $query['query'], $query['elapsed'], $query['debug'] ) ) {
				// WordPress.com VIP.
				$sql = $query['query'];
				$ltime = $query['elapsed'];
				$stack = $query['debug'];
			} elseif ( isset( $query[0], $query[1], $query[2] ) ) {
				// Standard WP.
				$sql = $query[0];
				$ltime = $query[1];
				$stack = $query[2];

				// Query Monitor db.php drop-in.
				$has_trace = isset( $query['trace'] );
				$has_result = isset( $query['result'] );
			} else {
				// ¯\_(ツ)_/¯
				continue;
			}

			// @TODO: decide what I want to do with this:
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( false !== strpos( $stack, 'wp_admin_bar' ) && ! isset( $_REQUEST['qm_display_admin_bar'] ) ) {
				continue;
			}

			$result = $query['result'] ?? null;
			$total_time += $ltime;

			if ( isset( $query['trace'] ) ) {

				$trace = $query['trace'];
				$component = $query['trace']->get_component();
				$caller = $query['trace']->get_caller();
				$caller_name = $caller['display'] ?? 'Unknown';
				$caller = $caller['display'] ?? 'Unknown';

			} else {

				$trace = null;
				$component = null;
				$callers = array_reverse( explode( ',', $stack ) );
				$callers = array_map( 'trim', $callers );
				$callers = QM_Backtrace::get_filtered_stack( $callers );
				$caller = reset( $callers );
				$caller_name = $caller;

			}

			$sql = trim( $sql );
			$type = QM_Util::get_query_type( $sql );

			$this->log_type( $type );
			$this->log_caller( $caller_name, $ltime, $type );
			$this->maybe_log_dupe( $sql, $i );

			if ( $component ) {
				$this->log_component( $component, $ltime, $type );
			}

			$is_main_query = ( $request === $sql && ( false !== strpos( $stack, ' WP->main,' ) ) );
			$row = compact( 'caller', 'caller_name', 'sql', 'ltime', 'result', 'type', 'component', 'trace', 'is_main_query' );

			if ( ! isset( $trace ) ) {
				$row['stack'] = $callers;
			}

			// @TODO these should store a reference ($i) instead of the whole row
			if ( $result instanceof WP_Error ) {
				$this->data->errors[] = $row;
			}

			// @TODO these should store a reference ($i) instead of the whole row
			if ( self::is_expensive( $row ) ) {
				$this->data->expensive[] = $row;
			}

			$this->data->rows[ $i ] = $row;
			$i++;
		}

		$has_main_query = wp_list_filter( $this->data->rows, array(
			'is_main_query' => true,
		) );

		$this->data->total_qs = count( $this->data->rows );
		$this->data->total_time = $total_time;
		$this->data->has_result = $has_result;
		$this->data->has_trace = $has_trace;
		$this->data->has_main_query = ! empty( $has_main_query );
	}

	/**
	 * @param string $sql
	 * @param int $i
	 * @return void
	 */
	protected function maybe_log_dupe( $sql, $i ) {
		$sql = str_replace( array( "\r\n", "\r", "\n" ), ' ', $sql );
		$sql = str_replace( array( "\t", '`' ), '', $sql );
		$sql = preg_replace( '/ +/', ' ', $sql );
		$sql = trim( $sql );
		$sql = rtrim( $sql, ';' );

		$this->data->dupes[ $sql ][] = $i;
	}
}

/**
 * @param array<string, QM_Collector> $collectors
 * @param QueryMonitor $qm
 * @return array<string, QM_Collector>
 */
function register_qm_collector_db_queries( array $collectors, QueryMonitor $qm ) {
	$collectors['db_queries'] = new QM_Collector_DB_Queries();
	return $collectors;
}

add_filter( 'qm/collectors', 'register_qm_collector_db_queries', 10, 2 );
