<?php declare(strict_types = 1);
/**
 * Hooks data transfer object.
 *
 * @package query-monitor
 */

class QM_Data_Hooks extends QM_Data {
	/**
	 * @var array<int, array<string, mixed>>
	 * @phpstan-var list<array{
	 *   name: string,
	 *   actions: list<array{
	 *     priority: int,
	 *     callback: QM_Callback|WP_Error,
	 *   }>,
	 *   parts: list<string>,
	 *   components: list<string>,
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
