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

			$this->stream( 'rows', $row );
			$i++;
		}

		$this->data->total_qs = $i;
		$this->data->has_result = $has_result;
		$this->data->has_trace = $has_trace;

		/**
		 * Filter whether to show the QM extended query information prompt.
		 *
		 * By default QM shows a prompt to install the QM db.php drop-in,
		 * this filter allows a dev to choose not to show the prompt.
		 *
		 * @since 2.9.0
		 *
		 * @param bool $show_prompt Whether to show the prompt.
		 */
		$show_extended_query_prompt = apply_filters( 'qm/show_extended_query_prompt', true );

		// Determine the reason for the extended query prompt
		if ( $show_extended_query_prompt && ! class_exists( 'QM_DB', false ) ) {
			if ( file_exists( WP_CONTENT_DIR . '/db.php' ) ) {
				$this->data->extended_query_prompt_reason = 'conflict';
			} elseif ( defined( 'QM_DB_SYMLINK' ) && ! QM_DB_SYMLINK ) {
				$this->data->extended_query_prompt_reason = 'disabled';
			} else {
				$this->data->extended_query_prompt_reason = 'failed';
			}
		}
	}

	/**
	 * @deprecated No longer used.
	 * @param string $sql
	 * @param int $i
	 * @return void
	 */
	protected function maybe_log_dupe( $sql, $i ) {
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
