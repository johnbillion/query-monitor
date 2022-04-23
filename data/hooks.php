<?php
/**
 * Hooks data transfer object.
 *
 * @package query-monitor
 */

class QM_Data_Hooks extends QM_Data {
	/**
	 * @var array<int, array<string, mixed>>
	 * @phpstan-var array<int, array{
	 *   name: string,
	 *   actions: array<int, array{
	 *     priority: int,
	 *     callback: array<string, mixed>,
	 *   }>,
	 *   parts: array<int, string>,
	 *   components: array<string, string>,
	 * }>
	 */
	public $hooks;

	/**
	 * @var array<int, string>
	 */
	public $parts;

	/**
	 * @var array<string, string>
	 */
	public $components;

	/**
	 * @var bool
	 */
	public $all_hooks;
}
