<?php declare(strict_types = 1);
/**
 * Database query components data transfer object.
 *
 * @package query-monitor
 */

class QM_Data_DB_Components extends QM_Data {
	/**
	 * @var array<string, array<string, mixed>>
	 * @phpstan-var array<string, array{
	 *   ltime: float,
	 *   types: array<string, int>,
	 *   component: string,
	 * }>
	 */
	public $times;
}
