<?php declare(strict_types = 1);
/**
 * Abstract data collector for structured data.
 *
 * @package query-monitor
 */

/**
 * @phpstan-template T of QM_Data
 */
abstract class QM_DataCollector extends QM_Collector {
	/**
	 * @var QM_Data
	 * @phpstan-var T
	 */
	protected $data;

	/**
	 * @return QM_Data
	 * @phpstan-return T
	 */
	final public function get_data() {
		return $this->data;
	}
}
