<?php declare(strict_types = 1);
/**
 * Abstract data transfer object.
 */

/**
 * @implements ArrayAccess<string,mixed>
 */
#[AllowDynamicProperties]
abstract class QM_Data implements \ArrayAccess {
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

	/**
	 * @param mixed $offset
	 * @param mixed $value
	 * @return void
	 */
	#[ReturnTypeWillChange]
	final public function offsetSet( $offset, $value ) {
		if ( is_string( $offset ) ) {
			$this->$offset = $value;
		}
	}

	/**
	 * @param mixed $offset
	 * @return bool
	 */
	#[ReturnTypeWillChange]
	final public function offsetExists( $offset ) {
		return is_string( $offset ) && isset( $this->$offset );
	}

	/**
	 * @param mixed $offset
	 * @return void
	 */
	#[ReturnTypeWillChange]
	final public function offsetUnset( $offset ) {
		// @TODO might be able to no-op this
		if ( is_string( $offset ) ) {
			unset( $this->$offset );
		}
	}

	/**
	 * @param mixed $offset
	 * @return mixed
	 */
	#[ReturnTypeWillChange]
	final public function &offsetGet( $offset ) {
		if ( is_string( $offset ) ) {
			return $this->$offset;
		}
		$null = null;
		return $null;
	}
}
