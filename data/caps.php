<?php declare(strict_types = 1);
/**
 * Cache data transfer object.
 *
 * @package query-monitor
 */

class QM_Data_Caps extends QM_Data {
	/**
	 * @var array<int, array<string, mixed>>
	 * @phpstan-var list<array{
	 *   args: list<mixed>,
	 *   trace: QM_StackTrace,
	 *   result: bool,
	 *   parts: list<string>,
	 *   name: string,
	 *   user: string,
	 * }>
	 */
	public $caps;

	/**
	 * @var array<int, string>
	 */
	public $parts;

	/**
	 * @var array<int, int>
	 */
	public $users;

	/**
	 * @var array<string, string>
	 */
	public $components;
}
