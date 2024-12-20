<?php declare(strict_types = 1);
/**
 * Web vitals dummy collector.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @extends QM_DataCollector<QM_Data_Web_Vitals>
 */
class QM_Collector_Web_Vitals extends QM_DataCollector {

	/**
	 * @var string
	 */
	public $id = 'web-vitals';

	/**
	 * @var bool
	 */
	protected static $hide_core;

	public function get_storage(): QM_Data {
		return new QM_Data_Web_Vitals();
	}

	/**
	 * @return void
	 */
	public function process() {

	}

}

QM_Collectors::add( new QM_Collector_Web_Vitals() );
