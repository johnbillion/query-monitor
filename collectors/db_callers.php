<?php
/**
 * Database query calling function collector.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QM_Collector_DB_Callers extends QM_Collector {

	public $id = 'db_callers';

	/**
	 * @return void
	 */
	public function process() {
		$dbq = QM_Collectors::get( 'db_queries' );

		if ( $dbq ) {
			if ( isset( $dbq->data['times'] ) ) {
				$this->data['times'] = $dbq->data['times'];
				QM_Util::rsort( $this->data['times'], 'ltime' );
			}
			if ( isset( $dbq->data['types'] ) ) {
				$this->data['types'] = $dbq->data['types'];
			}
		}

	}

}

/**
 * @param array<string, QM_Collector> $collectors
 * @param QueryMonitor $qm
 * @return array<string, QM_Collector>
 */
function register_qm_collector_db_callers( array $collectors, QueryMonitor $qm ) {
	$collectors['db_callers'] = new QM_Collector_DB_Callers();
	return $collectors;
}

add_filter( 'qm/collectors', 'register_qm_collector_db_callers', 20, 2 );
