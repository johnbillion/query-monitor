<?php declare(strict_types = 1);
/**
 * HTTP data transfer object.
 *
 * @package query-monitor
 */

class QM_Data_HTTP extends QM_Data {
	/**
	 * @var array<string, array<string, mixed>>
	 * @phpstan-var array<string, array{
	 *   args: array<string, mixed>,
	 *   component: QM_Component,
	 *   filtered_trace: list<array<string, mixed>>,
	 *   info: array<string, mixed>|null,
	 *   host: string,
	 *   local: bool,
	 *   ltime: float,
	 *   redirected_to: string|null,
	 *   response: mixed[]|WP_Error,
	 *   type: string,
	 *   url: string,
	 *   intercepted: bool,
	 * }>
	 */
	public $http;

	/**
	 * @var float
	 */
	public $ltime;

	/**
	 * @var array<string, array<int, string>>
	 * @phpstan-var array{
	 *   alert?: list<string>,
	 *   warning?: list<string>,
	 * }
	 */
	public $errors;
}
