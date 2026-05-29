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
 * @extends QM_DataCollector<QM_Data_DB_Queries>
 */
class QM_Collector_DB_Dupes extends QM_DataCollector {

	public $id = 'db_dupes';

	public function get_storage(): QM_Data {
		return new QM_Data_DB_Queries();
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
