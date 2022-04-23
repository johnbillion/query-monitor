<?php
/**
 * Database query calling component collector.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QM_Collector_DB_Components extends QM_Collector {

	public $id = 'db_components';

	/**
	 * @return void
	 */
	public function process() {
		/** @var QM_Collector_DB_Queries|null $dbq */
		$dbq = QM_Collectors::get( 'db_queries' );

		if ( $dbq ) {
			/** @var QM_Data_DB_Queries $dbq_data */
			$dbq_data = $dbq->get_data();

			if ( isset( $dbq_data->component_times ) ) {
				$this->data['times'] = $dbq_data->component_times;
				QM_Util::rsort( $this->data['times'], 'ltime' );
			}
			if ( isset( $dbq_data->types ) ) {
				$this->data['types'] = $dbq_data->types;
			}
		}

	}

}

/**
 * @param array<string, QM_Collector> $collectors
 * @param QueryMonitor $qm
 * @return array<string, QM_Collector>
 */
function register_qm_collector_db_components( array $collectors, QueryMonitor $qm ) {
	$collectors['db_components'] = new QM_Collector_DB_Components();
	return $collectors;
}

add_filter( 'qm/collectors', 'register_qm_collector_db_components', 20, 2 );
