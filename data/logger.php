<?php declare(strict_types = 1);
/**
 * Logger data transfer object.
 *
 * @package query-monitor
 */

class QM_Data_Logger extends QM_Data {
	/**
	 * @var array<string, int>
	 * @phpstan-var array<QM_Collector_Logger::*, int>
	 */
	public $counts;

	/**
	 * @var array<int, array<string, mixed>>
	 * @phpstan-var list<array{
	 *   message: string,
	 *   filtered_trace: mixed[],
	 *   component: QM_Component,
	 *   level: QM_Collector_Logger::*,
	 * }>
	 */
	public $logs;

	/**
	 * @var array<string, string>
	 */
	public $components;
}
