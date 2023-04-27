<?php declare(strict_types = 1);
/**
 * PHP errors data transfer object.
 *
 * @package query-monitor
 */

/**
 * @phpstan-type errorObject array{
 *   errno: int,
 *   file: string,
 *   line: int,
 *   type: string,
 *   message: string,
 *   trace: ?QM_StackTrace,
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
