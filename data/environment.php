<?php declare(strict_types = 1);
/**
 * Environment data transfer object.
 *
 * @package query-monitor
 */

class QM_Data_Environment extends QM_Data {
	/**
	 * @TODO data class
	 * @var array<string, mixed>
	 * @phpstan-var array{
	 *   variables: array<string, string|null>,
	 *   version: string|false,
	 *   sapi: string|false,
	 *   user: string,
	 *   old: bool,
	 *   extensions: array<string, string>,
	 *   error_reporting: int,
	 *   error_levels: array<string, bool>,
	 * }
	 */
	public $php;

	/**
	 * @TODO data class
	 * @var ?array<string, array<string, mixed>>
	 * @phpstan-var ?array<string, array{
	 *   info: array{
	 *     server-version: string,
	 *     extension: string|null,
	 *     client-version: string|null,
	 *     user: string,
	 *     host: string,
	 *     database: string,
	 *   },
	 *   vars: array<string, bool|string>,
	 *   variables: list<stdClass>,
	 * }>
	 */
	public $db;

	/**
	 * @TODO data class
	 * @var array<string, mixed>
	 * @phpstan-var array{
	 *   version: string,
	 *   environment_type?: string,
	 *   constants: array<string, string>,
	 * }>
	 */
	public $wp;

	/**
	 * @TODO data class
	 * @var array<string, mixed>
	 * @phpstan-var array{
	 *   name: string,
	 *   version: string|null,
	 *   address: string|null,
	 *   host: string|null,
	 *   OS: string|null,
	 *   arch: string|null,
	 * }>
	 */
	public $server;
}
