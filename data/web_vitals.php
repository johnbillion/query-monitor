<?php declare(strict_types = 1);
/**
 * Web_Vitals data transfer object.
 *
 * @package query-monitor
 */

class QM_Data_Web_Vitals extends QM_Data {
	/**
	 * Not used, data is generated for each load and not stored.
	 */
	public $web_vitals;

	/**
	 * @var array<int, string>
	 */
	public $parts;

	/**
	 * @var array<string, string>
	 */
	public $components;

	/**
	 * @var bool
	 */
	public $all_web_vitals;
}
