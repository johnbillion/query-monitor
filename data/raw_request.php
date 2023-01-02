<?php declare(strict_types = 1);
/**
 * Raw request data transfer object.
 *
 * @package query-monitor
 */

class QM_Data_Raw_Request extends QM_Data {
	/**
	 * @var array<string, mixed>
	 */
	public $request;

	/**
	 * @var array<string, mixed>
	 */
	public $response;
}
