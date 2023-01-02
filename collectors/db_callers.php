<?php declare(strict_types = 1);
/**
 * Database query calling function collector.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @extends QM_DataCollector<QM_Data_DB_Callers>
 */
class QM_Collector_DB_Callers extends QM_DataCollector {

	public $id = 'db_callers';

	public function get_storage(): QM_Data {
		return new QM_Data_DB_Callers();
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

			$this->data->times = $dbq_data->times;
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
function register_qm_collector_db_callers( array $collectors, QueryMonitor $qm ) {
	$collectors['db_callers'] = new QM_Collector_DB_Callers();
	return $collectors;
}

add_filter( 'qm/collectors', 'register_qm_collector_db_callers', 20, 2 );
