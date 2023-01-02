<?php declare(strict_types = 1);
/**
 * Asset data transfer object.
 *
 * @package query-monitor
 */

class QM_Data_Assets extends QM_Data {
	/**
	 * @var array<string, array<string, array<string, mixed>>>
	 */
	public $assets;

	/**
	 * @var array<int, string>
	 */
	public $broken;

	/**
	 * @var array<string, int>
	 */
	public $counts;

	/**
	 * @var string
	 */
	public $default_version;

	/**
	 * @var array<int, string>
	 */
	public $dependencies;

	/**
	 * @var array<int, string>
	 */
	public $dependents;

	/**
	 * @var array<int, string>
	 */
	public $footer;

	/**
	 * @var array<int, string>
	 */
	public $header;

	/**
	 * @var string
	 */
	public $host;

	/**
	 * @var bool
	 */
	public $is_ssl;

	/**
	 * @var array<int, string>
	 */
	public $missing;

	/**
	 * @var array<string, true>
	 */
	public $missing_dependencies;

	/**
	 * @var string
	 */
	public $port;
}
