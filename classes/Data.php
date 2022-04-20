<?php
/**
 * Abstract data transfer object.
 */

/**
 * @implements ArrayAccess<string,mixed>
 */
abstract class QM_Data implements \ArrayAccess {
	/**
	 * @var array<string, mixed>
	 */
	public $types = array();

	/**
	 * @var array<string, mixed>
	 */
	public $component_times = array();

	/**
	 * @param mixed $offset
	 * @param mixed $value
	 * @return void
	 */
	final public function offsetSet( $offset, $value ) {
		// @TODO might be able to no-op this
		if ( is_string( $offset ) ) {
			$this->$offset = $value;
		}
	}

	/**
	 * @param mixed $offset
	 * @return bool
	 */
	final public function offsetExists( $offset ) {
		return is_string( $offset ) && isset( $this->$offset );
	}

	/**
	 * @param mixed $offset
	 * @return void
	 */
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
	final public function offsetGet( $offset ) {
		return ( is_string( $offset ) && isset( $this->$offset ) ) ? $this->$offset : null;
	}
}
