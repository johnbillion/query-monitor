<?php declare(strict_types = 1);
/**
 * Emails data transfer object.
 *
 * @package query-monitor
 */

class QM_Data_Emails extends QM_Data {
	/**
	 * @var array<string, array<string, mixed>>
	 */
	public $emails;

	/**
	 * @var array<int, string>
	 */
	public $preempted;

	/**
	 * @var array<int, string>
	 */
	public $failed;

	/**
	 * @var array<string, int>
	 */
	public $counts;
}
