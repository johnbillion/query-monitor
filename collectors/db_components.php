<?php
/**
 * Database query calling component collector.
 *
 * @package query-monitor
 */

class QM_Collector_DB_Components extends QM_Collector {

	public $id = 'db_components';

	public function name() {
		return __( 'Queries by Component', 'query-monitor' );
	}

	public function process() {
		$dbq = QM_Collectors::get( 'db_queries' );

		if ( $dbq ) {
			if ( isset( $dbq->data['component_times'] ) ) {
				$this->data['times'] = $dbq->data['component_times'];
				QM_Util::rsort( $this->data['times'], 'ltime' );
			}
			if ( isset( $dbq->data['types'] ) ) {
				$this->data['types'] = $dbq->data['types'];
			}
		}

	}

}

function register_qm_collector_db_components( array $collectors, QueryMonitor $qm ) {
	$collectors['db_components'] = new QM_Collector_DB_Components();
	return $collectors;
}

add_filter( 'qm/collectors', 'register_qm_collector_db_components', 20, 2 );
