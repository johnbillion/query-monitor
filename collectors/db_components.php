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
 * @extends QM_DataCollector<QM_Data_DB_Queries>
 */
class QM_Collector_DB_Components extends QM_DataCollector {

	public $id = 'db_components';

	public function get_storage(): QM_Data {
		return new QM_Data_DB_Queries();
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
