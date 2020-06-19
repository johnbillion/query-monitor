<?php
/**
 * Database query collector.
 *
 * @package query-monitor
 */

if ( ! defined( 'SAVEQUERIES' ) ) {
	define( 'SAVEQUERIES', true );
}
if ( ! defined( 'QM_DB_EXPENSIVE' ) ) {
	define( 'QM_DB_EXPENSIVE', 0.05 );
}

if ( SAVEQUERIES && property_exists( $GLOBALS['wpdb'], 'save_queries' ) ) {
	$GLOBALS['wpdb']->save_queries = true;
}

class QM_Collector_DB_Queries extends QM_Collector {

	public $id         = 'db_queries';
	public $db_objects = array();

	public function get_errors() {
		if ( ! empty( $this->data['errors'] ) ) {
			return $this->data['errors'];
		}
		return false;
	}

	public function get_expensive() {
		if ( ! empty( $this->data['expensive'] ) ) {
			return $this->data['expensive'];
		}
		return false;
	}

	public static function is_expensive( array $row ) {
		return $row['ltime'] > QM_DB_EXPENSIVE;
	}

	public function process() {
		$this->data['total_qs']   = 0;
		$this->data['total_time'] = 0;
		$this->data['errors']     = array();

		/**
		 * Filters the `wpdb` instances that are exposed to QM.
		 *
		 * This allows Query Monitor to display multiple instances of `wpdb` on one page load.
		 *
		 * @since 2.7.0
		 *
		 * @param wpdb[] $db_objects Array of `wpdb` instances, keyed by their name.
		 */
		$this->db_objects = apply_filters( 'qm/collect/db_objects', array(
			'$wpdb' => $GLOBALS['wpdb'],
		) );

		foreach ( $this->db_objects as $name => $db ) {
			if ( is_a( $db, 'wpdb' ) ) {
				$this->process_db_object( $name, $db );
			} else {
				unset( $this->db_objects[ $name ] );
			}
		}

	}

	protected function log_caller( $caller, $ltime, $type ) {

		if ( ! isset( $this->data['times'][ $caller ] ) ) {
			$this->data['times'][ $caller ] = array(
				'caller' => $caller,
				'ltime'  => 0,
				'types'  => array(),
			);
		}

		$this->data['times'][ $caller ]['ltime'] += $ltime;

		if ( isset( $this->data['times'][ $caller ]['types'][ $type ] ) ) {
			$this->data['times'][ $caller ]['types'][ $type ]++;
		} else {
			$this->data['times'][ $caller ]['types'][ $type ] = 1;
		}

	}

	public function process_db_object( $id, wpdb $db ) {
		global $EZSQL_ERROR, $wp_the_query;

		// With SAVEQUERIES defined as false, `wpdb::queries` is empty but `wpdb::num_queries` is not.
		if ( empty( $db->queries ) ) {
			$this->data['total_qs'] += $db->num_queries;
			return;
		}

		$rows       = array();
		$types      = array();
		$total_time = 0;
		$has_result = false;
		$has_trace  = false;
		$i          = 0;
		$request    = trim( $wp_the_query->request );

		if ( method_exists( $db, 'remove_placeholder_escape' ) ) {
			$request = $db->remove_placeholder_escape( $request );
		}

		foreach ( (array) $db->queries as $query ) {

			# @TODO: decide what I want to do with this:
			if ( false !== strpos( $query[2], 'wp_admin_bar' ) and !isset( $_REQUEST['qm_display_admin_bar'] ) ) { // phpcs:ignore
				continue;
			}

			$sql        = $query[0];
			$ltime      = $query[1];
			$stack      = $query[2];
			$has_start  = isset( $query[3] );
			$has_trace  = isset( $query['trace'] );
			$has_result = isset( $query['result'] );

			if ( $has_result ) {
				$result = $query['result'];
			} else {
				$result = null;
			}

			$total_time += $ltime;

			if ( $has_trace ) {

				$trace       = $query['trace'];
				$component   = $query['trace']->get_component();
				$caller      = $query['trace']->get_caller();
				$caller_name = $caller['display'];
				$caller      = $caller['display'];

			} else {

				$trace     = null;
				$component = null;
				$callers   = explode( ',', $stack );
				$caller    = trim( end( $callers ) );

				if ( false !== strpos( $caller, '(' ) ) {
					$caller_name = substr( $caller, 0, strpos( $caller, '(' ) ) . '()';
				} else {
					$caller_name = $caller;
				}
			}

			$sql  = trim( $sql );
			$type = QM_Util::get_query_type( $sql );

			$this->log_type( $type );
			$this->log_caller( $caller_name, $ltime, $type );

			$this->maybe_log_dupe( $sql, $i );

			if ( $component ) {
				$this->log_component( $component, $ltime, $type );
			}

			if ( ! isset( $types[ $type ]['total'] ) ) {
				$types[ $type ]['total'] = 1;
			} else {
				$types[ $type ]['total']++;
			}

			if ( ! isset( $types[ $type ]['callers'][ $caller ] ) ) {
				$types[ $type ]['callers'][ $caller ] = 1;
			} else {
				$types[ $type ]['callers'][ $caller ]++;
			}

			$is_main_query = ( $request === $sql && ( false !== strpos( $stack, ' WP->main,' ) ) );

			$row = compact( 'caller', 'caller_name', 'sql', 'ltime', 'result', 'type', 'component', 'trace', 'is_main_query' );

			if ( ! isset( $trace ) ) {
				$row['stack'] = $stack;
			}

			if ( is_wp_error( $result ) ) {
				$this->data['errors'][] = $row;
			}

			if ( self::is_expensive( $row ) ) {
				$this->data['expensive'][] = $row;
			}

			$rows[ $i ] = $row;
			$i++;

		}

		if ( '$wpdb' === $id && ! $has_result && ! empty( $EZSQL_ERROR ) && is_array( $EZSQL_ERROR ) ) {
			// Fallback for displaying database errors when wp-content/db.php isn't in place
			foreach ( $EZSQL_ERROR as $error ) {
				$row = array(
					'caller'      => null,
					'caller_name' => null,
					'stack'       => '',
					'sql'         => $error['query'],
					'ltime'       => 0,
					'result'      => new WP_Error( 'qmdb', $error['error_str'] ),
					'type'        => '',
					'component'   => false,
					'trace'       => null,
					'is_main_query' => false,
				);
				$this->data['errors'][] = $row;
			}
		}

		$total_qs = count( $rows );

		$this->data['total_qs']   += $total_qs;
		$this->data['total_time'] += $total_time;

		$has_main_query = wp_list_filter( $rows, array(
			'is_main_query' => true,
		) );

		# @TODO put errors in here too:
		# @TODO proper class instead of (object)
		$this->data['dbs'][ $id ] = (object) compact( 'rows', 'types', 'has_result', 'has_trace', 'total_time', 'total_qs', 'has_main_query' );

	}

}

function register_qm_collector_db_queries( array $collectors, QueryMonitor $qm ) {
	$collectors['db_queries'] = new QM_Collector_DB_Queries();
	return $collectors;
}

add_filter( 'qm/collectors', 'register_qm_collector_db_queries', 10, 2 );
