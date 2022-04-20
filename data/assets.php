<?php
/**
 * Asset data transfer object.
 *
 * @package query-monitor
 */

class QM_Data_Assets extends QM_Data {
	/**
	 * @var array<int, string>
	 */
	public $header;

	/**
	 * @var array<int, string>
	 */
	public $footer;

	/**
	 * @var bool
	 */
	public $is_ssl;

	/**
	 * @var string
	 */
	public $host;

	/**
	 * @var string
	 */
	public $default_version;

	/**
	 * @var string
	 */
	public $port;

	/**
	 * @var array<string, int>
	 */
	public $counts;

	/**
	 * @var array<int, string>
	 */
	public $broken;

	/**
	 * @var array<int, string>
	 */
	public $missing;

	/**
	 * @var array<string, array<string, array<string, mixed>>>
	 */
	public $assets;

	/**
	 * @var array<int, string>
	 */
	public $dependencies;

	/**
	 * @var array<int, string>
	 */
	public $dependents;

	/**
	 * @var array<string, true>
	 */
	public $missing_dependencies;
}
