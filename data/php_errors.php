<?php declare(strict_types = 1);
/**
 * PHP errors data transfer object.
 *
 * @package query-monitor
 */

/**
 * @phpstan-type errorObject array{
 *   errno: int,
 *   type: string,
 *   message: string,
 *   file: string|null,
 *   filename: string,
 *   line: int|null,
 *   filtered_trace: list<array<string, mixed>>|null,
 *   component: QM_Component,
 *   calls: int,
 * }
 * @phpstan-type errorObjects array<string, array<string, errorObject>>
 */
class QM_Data_PHP_Errors extends QM_Data {
	/**
	 * @var array<string, string>
	 */
	public $components;

	/**
	 * @var array<string, array<string, array<string, mixed>>>
	 * @phpstan-var errorObjects
	 */
	public $errors;

	/**
	 * @var array<string, array<string, array<string, mixed>>>
	 * @phpstan-var errorObjects
	 */
	public $suppressed;

	/**
	 * @var array<string, array<string, array<string, mixed>>>
	 * @phpstan-var errorObjects
	 */
	public $silenced;
}
