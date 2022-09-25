<?php
/**
 * Abstract data collector for structured data.
 *
 * @package query-monitor
 */

/**
 * @template T of QM_Data
 */
abstract class QM_DataCollector extends QM_Collector {
	/**
	 * @var T
	 */
	protected $data;

	/**
	 * @return T
	 */
	final public function get_data() {
		return $this->data;
	}
}
