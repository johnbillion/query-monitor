<?php
/**
 * Conditionals data transfer object.
 *
 * @package query-monitor
 */

class QM_Data_Conditionals extends QM_Data {
	/**
	 * @var array<string, array<int, string>>
	 * @phpstan-var array{
	 *   true: array<int, string>,
	 *   false: array<int, string>,
	 *   na: array<int, string>,
	 * }
	 */
	public $conds;
}
