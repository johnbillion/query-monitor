<?php declare(strict_types = 1);
/**
 * OPCode cache collector.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @extends QM_DataCollector<QM_Data_Cache>
 */
class QM_Collector_Opcode_Cache extends QM_DataCollector {

	public $id = 'opcode-cache';

	public function get_storage(): QM_Data {
		return new QM_Data_Cache();
	}

	/**
	 * @return void
	 */
	public function process() {
		$this->data->has = false;
		$this->data->cache_hit_percentage = 0;
		$this->data->cache_extensions = array();

		if ( function_exists( 'extension_loaded' ) ) {
			$this->data->cache_extensions = array_map( 'extension_loaded', array(
				'APC' => 'APC',
				'Zend OPcache' => 'Zend OPcache',
			) );
		}

		$this->data->has = array_filter( $this->data->cache_extensions ) ? true : false;
	}

}

/**
 * @param array<string, QM_Collector> $collectors
 * @param QueryMonitor $qm
 * @return array<string, QM_Collector>
 */
function register_qm_collector_opcode_cache( array $collectors, QueryMonitor $qm ) {
	$collectors['opcode-cache'] = new QM_Collector_Opcode_Cache();
	return $collectors;
}

add_filter( 'qm/collectors', 'register_qm_collector_opcode_cache', 20, 2 );
