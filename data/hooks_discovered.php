<?php declare(strict_types = 1);
/**
 * Hooks data transfer object.
 *
 * @package query-monitor
 */

class QM_Data_Hooks_Discovered extends QM_Data {

	/**
	 * @var array<string, int>
	 */
	public $active;

	/**
	 * @var array<string, array<string, mixed>>
	 */
	public $bounds;

	/**
	 * @var array<string, int>
	 */
	public $counts;

	/**
	 * @var array<string, array<int, array<string, mixed>>>
	 */
	public $hooks;

}
