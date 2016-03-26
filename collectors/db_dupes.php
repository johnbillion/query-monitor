<?php
/*
Copyright 2009-2016 John Blackbourn

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

*/

class QM_Collector_DB_Dupes extends QM_Collector {

	public $id = 'db_dupes';

	public function name() {
		return __( 'Duplicate Queries', 'query-monitor' );
	}

	public function process() {

		if ( ! $dbq = QM_Collectors::get( 'db_queries' ) ) {
			return;
		}
		if ( ! isset( $dbq->data['dupes'] ) ) {
			return;
		}

		// Filter out SQL queries that do not have dupes
		$this->data['dupes'] = array_filter( $dbq->data['dupes'], array( $this, '_filter_dupe_queries' ) );

		// Ignore dupes from `WP_Query->set_found_posts()`
		unset( $this->data['dupes']['SELECT FOUND_ROWS()'] );

		$stacks     = array();
		$tops       = array();
		$callers    = array();
		$components = array();

		// Loop over all SQL queries that have dupes
		foreach ( $this->data['dupes'] as $sql => $query_ids ) {

			// Loop over each query
			foreach ( $query_ids as $query_id ) {

				if ( isset( $dbq->data['dbs']['$wpdb']->rows[ $query_id ]['trace'] ) ) {

					$trace     = $dbq->data['dbs']['$wpdb']->rows[ $query_id ]['trace'];
					$stack     = wp_list_pluck( $trace->get_filtered_trace(), 'id' );
					$component = $trace->get_component();

					// Populate the component counts for this query
					if ( isset( $components[ $sql ][ $component->name ] ) ) {
						$components[ $sql ][ $component->name ]++;
					} else {
						$components[ $sql ][ $component->name ] = 1;
					}

				} else {
					$stack = array_reverse( explode( ', ', $dbq->data['dbs']['$wpdb']->rows[ $query_id ]['stack'] ) );
				}

				// Populate the caller counts for this query
				if ( isset( $callers[ $sql ][ $stack[0] ] ) ) {
					$callers[ $sql ][ $stack[0] ]++;
				} else {
					$callers[ $sql ][ $stack[0] ] = 1;
				}

				// Populate the stack for this query
				$stacks[ $sql ][] = $stack;

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
			$sources[ $sql ] = array_count_values( wp_list_pluck( $stacks[ $sql ], 0 ) );

		}

		if ( ! empty( $sources ) ) {
			$this->data['dupe_sources']    = $sources;
			$this->data['dupe_callers']    = $callers;
			$this->data['dupe_components'] = $components;
		}

	}

	public function _filter_dupe_queries( $queries ) {
		return ( count( $queries ) > 1 );
	}

}

function register_qm_collector_db_dupes( array $collectors, QueryMonitor $qm ) {
	$collectors['db_dupes'] = new QM_Collector_DB_Dupes;
	return $collectors;
}

add_filter( 'qm/collectors', 'register_qm_collector_db_dupes', 25, 2 );
