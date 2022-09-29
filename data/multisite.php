<?php declare(strict_types = 1);
/**
 * Multisite data transfer object.
 *
 * @package query-monitor
 */

class QM_Data_Multisite extends QM_Data {
	/**
	 * @var array<int, array<string, mixed>>
	 * @phpstan-var list<array{
	 *   new: int,
	 *   prev: int,
	 *   to: bool,
	 *   trace: QM_Backtrace,
	 * }>
	 */
	public $switches;
}
