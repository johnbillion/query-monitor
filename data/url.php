<?php declare(strict_types = 1);
/**
 * Parsed URL data object.
 *
 * @package query-monitor
 */

class QM_Data_URL implements JsonSerializable {
	/**
	 * @var string
	 */
	public $origin;

	/**
	 * @var string
	 */
	public $scheme;

	/**
	 * @var string
	 */
	public $hostname;

	/**
	 * @var string
	 */
	public $host;

	/**
	 * @var bool
	 */
	public $insecure;

	/**
	 * @var bool
	 */
	public $local;

	/**
	 * @var string
	 */
	public $absolute;

	/**
	 * @return array{host: string, local: bool, absolute: string}
	 */
	public function jsonSerialize(): array {
		return array(
			'host' => $this->host,
			'local' => $this->local,
			'absolute' => $this->absolute,
		);
	}
}
