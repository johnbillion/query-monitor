<?php declare(strict_types = 1);
/**
 * Database query callers data transfer object.
 *
 * @package query-monitor
 */

class QM_Data_DB_Callers extends QM_Data {
	/**
	 * @var array<string, array<string, mixed>>
	 * @phpstan-var array<string, array{
	 *   caller: string,
	 *   ltime: float,
	 *   types: array<string, int>,
	 * }>
	 */
	public $times = array();
}
