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

	/**
	 * @param string|int $type
	 * @return void
	 */
	protected function log_type( $type ) {
		if ( isset( $this->data->types[ $type ] ) ) {
			$this->data->types[ $type ]++;
		} else {
			$this->data->types[ $type ] = 1;
		}
	}
}
