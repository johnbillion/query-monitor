<?php declare(strict_types = 1);
/**
 * Web_Vitals data transfer object.
 *
 * @package query-monitor
 */

class QM_Data_Web_Vitals extends QM_Data {
	/**
	 * @var array<int, array<string, mixed>>
	 * @phpstan-var list<array{
	 *   name: string,
	 *   actions: list<array{
	 *     priority: int,
	 *     callback: array<string, mixed>,
	 *   }>,
	 *   parts: list<string>,
	 *   components: array<string, string>,
	 * }>
	 */
	public $web_vitals;

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
	public $all_web_vitals;
}
