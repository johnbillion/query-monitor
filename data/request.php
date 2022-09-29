<?php declare(strict_types = 1);
/**
 * Request data transfer object.
 *
 * @package query-monitor
 */

class QM_Data_Request extends QM_Data {
	/**
	 * @var array<string, mixed>
	 * @phpstan-var array{
	 *   title: string,
	 *   data: WP_User|false,
	 * }
	 */
	public $user;

	/**
	 * @var array<string, array<string, mixed>>
	 */
	public $multisite;

	/**
	 * @var array<string, mixed>
	 */
	public $request;

	/**
	 * @var array<string, mixed>
	 */
	public $qvars;

	/**
	 * @var array<string, mixed>
	 */
	public $plugin_qvars;

	/**
	 * @var array<string, mixed>
	 */
	public $queried_object;

	/**
	 * @var string
	 */
	public $request_method;

	/**
	 * @var array<string, string>
	 */
	public $matching_rewrites;
}
