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

	/**
	 * @param stdClass $component
	 * @param float $ltime
	 * @param string|int $type
	 * @return void
	 */
	protected function log_component( $component, $ltime, $type ) {
		if ( ! isset( $this->data->component_times[ $component->name ] ) ) {
			$this->data->component_times[ $component->name ] = array(
				'component' => $component->name,
				'ltime' => 0,
				'types' => array(),
			);
		}

		$this->data->component_times[ $component->name ]['ltime'] += $ltime;

		if ( isset( $this->data->component_times[ $component->name ]['types'][ $type ] ) ) {
			$this->data->component_times[ $component->name ]['types'][ $type ]++;
		} else {
			$this->data->component_times[ $component->name ]['types'][ $type ] = 1;
		}
	}

}
