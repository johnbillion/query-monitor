<?php declare(strict_types = 1);
/**
 * Overview data transfer object.
 *
 * @package query-monitor
 */

class QM_Data_Overview extends QM_Data {
	/**
	 * @var ?float
	 */
	public $time_taken;

	/**
	 * @var int
	 */
	public $time_limit;

	/**
	 * @var float
	 */
	public $time_start;

	/**
	 * @var int|float
	 */
	public $time_usage;

	/**
	 * @var int
	 */
	public $memory;

	/**
	 * @var float
	 */
	public $memory_limit;

	/**
	 * @var int|float
	 */
	public $memory_usage;

	/**
	 * @var float
	 */
	public $wp_memory_limit;

	/**
	 * @var int|float
	 */
	public $wp_memory_usage;

	/**
	 * @var ?array<string, mixed>
	 */
	public $current_user;

	/**
	 * @var ?array<string, mixed>
	 */
	public $switched_user;

	/**
	 * @var bool
	 */
	public $display_time_usage_warning;

	/**
	 * @var bool
	 */
	public $display_memory_usage_warning;

	/**
	 * @var bool
	 */
	public $is_admin;

}
