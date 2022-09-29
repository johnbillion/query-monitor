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
	public $has_object_cache;

	/**
	 * @var bool
	 */
	public $display_hit_rate_warning;

	/**
	 * @var bool
	 */
	public $has_opcode_cache;

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
	public $object_cache_extensions;

	/**
	 * @var array<string, bool>
	 */
	public $opcode_cache_extensions;

}
