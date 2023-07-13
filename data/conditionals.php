<?php declare(strict_types = 1);
/**
 * Conditionals data transfer object.
 *
 * @package query-monitor
 */

class QM_Data_Conditionals extends QM_Data {
	/**
	 * @var array<string, array<int, string>>
	 * @phpstan-var array{
	 *   true: list<string>,
	 *   false: list<string>,
	 *   na: list<string>,
	 * }
	 */
	public $conds;
}
