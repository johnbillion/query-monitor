<?php declare(strict_types = 1);
/**
 * Hooks and actions collector.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @extends QM_DataCollector<QM_Data_Hooks>
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
		return new QM_Data_Hooks();
	}

	/**
	 * @return void
	 */
	public function process() {

	}

}

# Load early to catch all hooks
QM_Collectors::add( new QM_Collector_Web_Vitals() );
