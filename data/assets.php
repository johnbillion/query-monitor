<?php declare(strict_types = 1);
/**
 * Asset data transfer object.
 *
 * @package query-monitor
 */

/**
 * @phpstan-type Asset array{
 *   host: string,
 *   port: string,
 *   source: string|WP_Error,
 *   local: bool,
 *   ver: string,
 *   warning: bool,
 *   display: string,
 *   dependents: array<string>,
 *   dependencies: array<string>,
 * }
 * @phpstan-type AssetList array<string, Asset>
 */
class QM_Data_Assets extends QM_Data {
	/**
	 * @var ?array<string, array<string, array<string, mixed>>>
	 * @phpstan-var ?array{
	 *   missing: AssetList,
	 *   broken: AssetList,
	 *   header: AssetList,
	 *   footer: AssetList,
	 * }
	 */
	public $assets;

	/**
	 * @var array<string, int>
	 * @phpstan-var array{
	 *   missing: int,
	 *   broken: int,
	 *   header: int,
	 *   footer: int,
	 *   total: int,
	 * }
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
	public $missing_dependencies;

	/**
	 * @var string
	 */
	public $port;
}
