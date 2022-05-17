<?php
/**
 * Transients data transfer object.
 *
 * @package query-monitor
 */

class QM_Data_Transients extends QM_Data {
	/**
	 * @var array<int, array{
	 *   name: string,
	 *   filtered_trace: mixed[],
	 *   component: stdClass,
	 *   type: string,
	 *   value: mixed,
	 *   expiration: int,
	 *   exp_diff: string,
	 *   size: int,
	 *   size_formatted: string,
	 * }>
	 */
	public $trans;

	/**
	 * @var bool
	 */
	public $has_type;
}
