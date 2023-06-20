<?php declare(strict_types = 1);
/**
 * Doing It Wrong data transfer object.
 *
 * @package query-monitor
 */

class QM_Data_Doing_It_Wrong extends QM_Data {
	/**
	 * @var array<int, array<int, string>> */
	public $actions;

	/**
	 * @var array<string, int>
	 */
	public $counts;
}
