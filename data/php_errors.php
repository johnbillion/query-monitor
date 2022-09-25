<?php
/**
 * PHP errors data transfer object.
 *
 * @package query-monitor
 */

/**
 * @phpstan-type errorObject array<string, array<string, array{
 *   errno: int,
 *   type: string,
 *   message: string,
 *   file: string|null,
 *   filename: string,
 *   line: int|null,
 *   filtered_trace: list<array<string, mixed>>|null,
 *   component: QM_Component,
 *   calls: int,
 * }>>
 */
class QM_Data_PHP_Errors extends QM_Data {
	/**
	 * @var array<string, string>
	 */
	public $components;

	/**
	 * @var array<string, array<string, array<string, mixed>>>
	 * @phpstan-var errorObject
	 */
	public $errors;

	/**
	 * @var array<string, array<string, array<string, mixed>>>
	 * @phpstan-var errorObject
	 */
	public $suppressed;

	/**
	 * @var array<string, array<string, array<string, mixed>>>
	 * @phpstan-var errorObject
	 */
	public $silenced;
}
