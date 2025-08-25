<?php declare(strict_types = 1);
/**
 * Cache data transfer object.
 *
 * @package query-monitor
 */

class QM_Data_Cache extends QM_Data {
	/**
	 * @var bool
	 */
	public $has;

	/**
	 * @var bool
	 */
	public $display_hit_rate_warning;

	/**
	 * @var int
	 */
	public $cache_hit_percentage;

	/**
	 * @var array<string, mixed>
	 */
	public $stats;

	/**
	 * @var array<string, bool>
	 */
	public $cache_extensions;

}
