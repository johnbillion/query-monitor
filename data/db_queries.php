<?php declare(strict_types = 1);
/**
 * Database queries data transfer object.
 *
 * @package query-monitor
 */

class QM_Data_DB_Queries extends QM_Data {
	/**
	 * @var int
	 */
	public $total_qs;

	/**
	 * @var float
	 */
	public $total_time;

	/**
	 * @var array<int, array<string, mixed>>
	 */
	public $errors;

	/**
	 * @var ?array<int, array<string, mixed>>
	 */
	public $expensive;

	/**
	 * @var array<int, array<string, mixed>>
	 */
	public $rows;

	/**
	 * @var bool
	 */
	public $has_result;

	/**
	 * @var bool
	 */
	public $has_trace;

	/**
	 * @var bool
	 */
	public $has_main_query;

	/**
	 * @var ?array<string, array<string, mixed>>
	 * @phpstan-var ?array<string, array{
	 *   caller: string,
	 *   ltime: float,
	 *   types: array<string, int>,
	 * }>
	 */
	public $times = array();

	/**
	 * @var ?array<string, array<int, int>>
	 */
	public $dupes;
}
