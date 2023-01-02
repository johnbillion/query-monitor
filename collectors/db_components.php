<?php declare(strict_types = 1);
/**
 * Database query calling component collector.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @extends QM_DataCollector<QM_Data_DB_Components>
 */
class QM_Collector_DB_Components extends QM_DataCollector {

	public $id = 'db_components';

	public function get_storage(): QM_Data {
		return new QM_Data_DB_Components();
	}

	/**
	 * @return void
	 */
	public function process() {
		/** @var QM_Collector_DB_Queries|null $dbq */
		$dbq = QM_Collectors::get( 'db_queries' );

		if ( $dbq ) {
			/** @var QM_Data_DB_Queries $dbq_data */
			$dbq_data = $dbq->get_data();

			$this->data->times = $dbq_data->component_times;
			QM_Util::rsort( $this->data->times, 'ltime' );

			$this->data->types = $dbq_data->types;
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
