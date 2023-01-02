<?php declare(strict_types = 1);
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
	 *   component: QM_Component,
	 *   type: string,
	 *   value: mixed,
	 *   expiration: int,
	 *   exp_diff: string,
	 *   size: int,
	 *   size_formatted: string,
	 * }>
	 */
	public $trans = array();

	/**
	 * @var bool
	 */
	public $has_type;
}
