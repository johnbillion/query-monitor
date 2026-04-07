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
 * @phpstan-type QueryStandard array{
 *   0: string,
 *   1: float,
 *   2: string,
 *   trace?: QM_Backtrace,
 *   result?: int|bool|WP_Error,
 * }
 * @phpstan-type QueryVIP array{
 *   query: string,
 *   elapsed: float,
 *   debug: string,
 * }
 *
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

	/**
	 * @var ?array<string, array<int, int>>
	 */
	protected $dupes = array();

	public function get_storage(): QM_Data {
		return new QM_Data_DB_Queries();
	}

	/**
	 * @return int[]|false
	 */
	public function get_errors() {
		if ( ! empty( $this->data->errors ) ) {
			return $this->data->errors;
		}
		return false;
	}

	/**
	 * @return int[]|false
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
		$this->data->errors = array();
		$this->process_db_object();
	}

	/**
	 * @deprecated Caller calculations are now handled client-side.
	 *
	 * @param string $caller
	 * @param float $ltime
	 * @param string $type
	 * @return void
	 */
	protected function log_caller( $caller, $ltime, $type ) {}

	/**
	 * @return void
	 */
	public function process_db_object() {
		/**
		 * @var WP_Query $wp_the_query
		 * @var wpdb $wpdb
		 */
		global $wp_the_query, $wpdb;

		$this->wpdb = $wpdb;

		// With SAVEQUERIES defined as false, `wpdb::queries` is empty but `wpdb::num_queries` is not.
		if ( empty( $wpdb->queries ) ) {
			$this->data->total_qs += $wpdb->num_queries;
			return;
		}

		$this->data->rows = array();

		$types = array();
		$has_result = false;
		$has_trace = false;
		$i = 0;
		$request = trim( $wp_the_query->request ?: '' );

		$request = $wpdb->remove_placeholder_escape( $request );

		/**
		 * @phpstan-var QueryStandard|QueryVIP $query
		 */
		foreach ( $wpdb->queries as $query ) {
			if ( isset( $query['query'], $query['elapsed'], $query['debug'] ) ) {
				// WordPress.com VIP.
				$sql = $query['query'];
				$ltime = $query['elapsed'];
				$stack = $query['debug'];
			// @phpstan-ignore-next-line isset.offset
			} elseif ( isset( $query[0], $query[1], $query[2] ) ) {
				// Standard WP.
				$sql = $query[0];
				$ltime = $query[1];
				$stack = $query[2];

				// Query Monitor db.php drop-in.
				// @phpstan-ignore-next-line instanceof.alwaysTrue
				if ( isset( $query['trace'] ) && ( $query['trace'] instanceof QM_Backtrace ) ) {
					$has_trace = true;
					$trace = $query['trace'];
				}
				if ( isset( $query['result'] ) ) {
					$has_result = true;
					$result = $query['result'];
				}
			} else {
				// ¯\_(ツ)_/¯
				continue;
			}

			// @TODO: decide what I want to do with this:
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( false !== strpos( $stack, 'wp_admin_bar' ) && ! isset( $_REQUEST['qm_display_admin_bar'] ) ) {
				continue;
			}

			$sql = trim( $sql );

			$this->maybe_log_dupe( $sql, $i );

			$row = compact( 'sql', 'ltime' );

			if ( false !== strpos( $stack, ' WP->main,' ) ) {
				// Ignore comments that are appended to queries by some web hosts.
				$match_sql = preg_replace( '#/\*.*?\*/\s*$#s', '', $sql );

				$is_main_query = ( $request === $match_sql );

				if ( $is_main_query ) {
					$row['is_main_query'] = true;
				}
			}

			if ( ! isset( $trace ) ) {
				$callers = array_reverse( explode( ',', $stack ) );
				$callers = array_map( 'trim', $callers );
				$row['stack'] = QM_Backtrace::get_filtered_stack( $callers );
			} else {
				$row['trace'] = $trace;
			}

			if ( isset( $result ) ) {
				$row['result'] = $result;

				if ( $result instanceof WP_Error ) {
					$this->data->errors[] = $i;
				}
			}

			if ( self::is_expensive( $row ) ) {
				$this->data->expensive[] = $i;
			}

			$this->data->rows[ $i ] = $row;
			$i++;
		}

		$this->data->total_qs = count( $this->data->rows );
		$this->data->has_result = $has_result;
		$this->data->has_trace = $has_trace;

		// Filter out queries that do not have duplicates
		$this->dupes = array_filter( $this->dupes, array( $this, 'filter_dupe_items' ) );

		// Ignore duplicates from `WP_Query->set_found_posts()`
		unset( $this->dupes['SELECT FOUND_ROWS()'] );

		$stacks = array();

		// Iterate all queries that have duplicates
		foreach ( $this->dupes as $sql => $query_ids ) {
			$dupe_data = array(
				'query' => $sql,
				'count' => count( $query_ids ),
				'ltime' => 0,
				'callers' => array(),
				'components' => array(),
				'sources' => array(),
			);

			foreach ( $query_ids as $query_id ) {
				if ( isset( $this->data->rows[ $query_id ]['trace'] ) ) {
					$trace = $this->data->rows[ $query_id ]['trace'];
					$stack = $trace->get_stack();
					$component = $trace->get_component();

					// Populate the component counts for this query
					if ( isset( $dupe_data['components'][ $component->name ] ) ) {
						$dupe_data['components'][ $component->name ]++;
					} else {
						$dupe_data['components'][ $component->name ] = 1;
					}
				} else {
					$stack = $this->data->rows[ $query_id ]['stack'] ?? array();
				}

				// Populate the caller counts for this query
				if ( isset( $dupe_data['callers'][ $stack[0] ] ) ) {
					$dupe_data['callers'][ $stack[0] ]++;
				} else {
					$dupe_data['callers'][ $stack[0] ] = 1;
				}

				// Populate the stack for this query
				$stacks[ $sql ][] = $stack;

				// Populate the time for this query
				$dupe_data['ltime'] += $this->data->rows[ $query_id ]['ltime'];
			}

			// Get the callers which are common to all stacks for this query
			$common = call_user_func_array( 'array_intersect', $stacks[ $sql ] );

			// Remove callers which are common to all stacks for this query
			foreach ( $stacks[ $sql ] as $i => $stack ) {
				$stacks[ $sql ][ $i ] = array_values( array_diff( $stack, $common ) );

				// No uncommon callers within the stack? Just use the topmost caller.
				if ( empty( $stacks[ $sql ][ $i ] ) ) {
					$stacks[ $sql ][ $i ] = array_keys( $dupe_data['callers'] );
				}
			}

			// Count the occurrences of each primary caller across duplicate queries
			$dupe_data['sources'] = array_count_values( array_column( $stacks[ $sql ], 0 ) );

			$this->data->dupes[] = $dupe_data;
		}
	}

	/**
	 * @param string $sql
	 * @param int $i
	 * @return void
	 */
	protected function maybe_log_dupe( $sql, $i ) {
		// Replace all newlines with a single space
		$sql = str_replace( array( "\r\n", "\r", "\n" ), ' ', $sql );
		// Remove all tabs and backticks
		$sql = str_replace( array( "\t", '`' ), '', $sql );
		// Replace all instance of multiple spaces with a single space
		$sql = preg_replace( '/ +/', ' ', $sql );
		// Trim the SQL
		$sql = trim( $sql );
		// Remove trailing semicolon
		$sql = rtrim( $sql, ';' );

		$this->dupes[ $sql ][] = $i;
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
