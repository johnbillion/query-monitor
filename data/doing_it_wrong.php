<?php declare(strict_types = 1);
/**
 * Doing it Wrong data transfer object.
 *
 * @package query-monitor
 */

class QM_Data_Doing_It_Wrong extends QM_Data {
	/**
	 * @var array<int, array<string, mixed>>
	 * @phpstan-var array<int, array{
	 *   hook: string,
	 *   filtered_trace: list<array<string, mixed>>,
	 *   message: string,
	 *   component: QM_Component,
	 * }>
	 */
	public $actions;
}
