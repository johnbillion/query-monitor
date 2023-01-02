<?php declare(strict_types = 1);
/**
 * Redirect data transfer object.
 *
 * @package query-monitor
 */

class QM_Data_Redirect extends QM_Data {
	/**
	 * @var ?QM_Backtrace
	 */
	public $trace;

	/**
	 * @var ?string
	 */
	public $location;

	/**
	 * @var ?int
	 */
	public $status;
}
