<?php
/**
 * Cache data transfer object.
 *
 * @package query-monitor
 */

class QM_Data_Caps extends QM_Data {
	/**
	 * @var array<int, array<string, mixed>>
	 * @phpstan-var array<int, array{
	 *   args: array<int, mixed>,
	 *   filtered_trace: array<int, array<string, mixed>>,
	 *   component: QM_Component,
	 *   result: bool,
	 *   parts: array<int, string>,
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
	 * @var array<int, string>
	 */
	public $users;

	/**
	 * @var array<string, string>
	 */
	public $components;
}
