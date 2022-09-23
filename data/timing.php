<?php
/**
 * Timing data transfer object.
 *
 * @package query-monitor
 */

class QM_Data_Timing extends QM_Data {
	/**
	 * @var array<int, array<string, mixed>>
	 */
	public $warning;

	/**
	 * @var array<int, array<string, mixed>>
	 */
	public $timing;
}
