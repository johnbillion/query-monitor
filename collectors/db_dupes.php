<?php declare(strict_types = 1);
/**
 * Duplicate database query collector.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @extends QM_DataCollector<QM_Data_DB_Dupes>
 */
class QM_Collector_DB_Dupes extends QM_DataCollector {

	public $id = 'db_dupes';

	public function get_storage(): QM_Data {
		return new QM_Data_DB_Dupes();
	}

	/**
	 * @return void
	 */
	public function process() {
		/** @var QM_Collector_DB_Queries|null $dbq */
		$dbq = QM_Collectors::get( 'db_queries' );

		if ( ! $dbq ) {
			return;
		}

		/** @var QM_Data_DB_Queries $dbq_data */
		$dbq_data = $dbq->get_data();

		if ( empty( $dbq_data->dupes ) ) {
			return;
		}

		// Filter out SQL queries that do not have dupes
		$this->data->dupes = array_filter( $dbq_data->dupes, array( $this, 'filter_dupe_items' ) );

		// Ignore dupes from `WP_Query->set_found_posts()`
		unset( $this->data->dupes['SELECT FOUND_ROWS()'] );

		$stacks = array();
		$callers = array();
		$components = array();
		$times = array();

		// Loop over all SQL queries that have dupes
		foreach ( $this->data->dupes as $sql => $query_ids ) {

			// Loop over each query
			foreach ( $query_ids as $query_id ) {

				if ( isset( $dbq_data->rows[ $query_id ]['trace'] ) ) {
					/** @var QM_Backtrace */
					$trace = $dbq_data->rows[ $query_id ]['trace'];
					$stack = array_column( $trace->get_filtered_trace(), 'id' );
					$component = $trace->get_component();

					// Populate the component counts for this query
					if ( isset( $components[ $sql ][ $component->name ] ) ) {
						$components[ $sql ][ $component->name ]++;
					} else {
						$components[ $sql ][ $component->name ] = 1;
					}
				} else {
					/** @var array<int, string> */
					$stack = $dbq_data->rows[ $query_id ]['stack'];
				}

				// Populate the caller counts for this query
				if ( isset( $callers[ $sql ][ $stack[0] ] ) ) {
					$callers[ $sql ][ $stack[0] ]++;
				} else {
					$callers[ $sql ][ $stack[0] ] = 1;
				}

				// Populate the stack for this query
				$stacks[ $sql ][] = $stack;

				// Populate the time for this query
				if ( isset( $times[ $sql ] ) ) {
					$times[ $sql ] += $dbq->data->rows[ $query_id ]['ltime'];
				} else {
					$times[ $sql ] = $dbq->data->rows[ $query_id ]['ltime'];
				}
			}

			// Get the callers which are common to all stacks for this query
			$common = call_user_func_array( 'array_intersect', $stacks[ $sql ] );

			// Remove callers which are common to all stacks for this query
			foreach ( $stacks[ $sql ] as $i => $stack ) {
				$stacks[ $sql ][ $i ] = array_values( array_diff( $stack, $common ) );

				// No uncommon callers within the stack? Just use the topmost caller.
				if ( empty( $stacks[ $sql ][ $i ] ) ) {
					$stacks[ $sql ][ $i ] = array_keys( $callers[ $sql ] );
				}
			}

			// Wave a magic wand
			$sources[ $sql ] = array_count_values( array_column( $stacks[ $sql ], 0 ) );

		}

		if ( ! empty( $sources ) ) {
			$this->data->dupe_sources = $sources;
			$this->data->dupe_callers = $callers;
			$this->data->dupe_components = $components;
			$this->data->dupe_times = $times;
		}

	}
}

/**
 * @param array<string, QM_Collector> $collectors
 * @param QueryMonitor $qm
 * @return array<string, QM_Collector>
 */
function register_qm_collector_db_dupes( array $collectors, QueryMonitor $qm ) {
	$collectors['db_dupes'] = new QM_Collector_DB_Dupes();
	return $collectors;
}

add_filter( 'qm/collectors', 'register_qm_collector_db_dupes', 25, 2 );
