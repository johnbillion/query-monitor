<?php declare(strict_types = 1);
/**
 * Abstract data transfer object.
 */

/**
 * @implements ArrayAccess<string,mixed>
 */
#[AllowDynamicProperties]
abstract class QM_Data implements \ArrayAccess {
	use QM_ArrayAccess;

	/**
	 * @var array<string, mixed>
	 */
	public $types = array();

	/**
	 * @var ?array<mixed>
	 */
	public $concerned_filters = null;

	/**
	 * @var ?array<mixed>
	 */
	public $concerned_actions = null;
}
