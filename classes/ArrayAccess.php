<?php declare(strict_types = 1);
/**
 * Array access trait for data transfer objects.
 *
 * Provides backwards compatibility for third party code that accesses
 * QM data objects using array syntax.
 */

/**
 * @implements \ArrayAccess<string,mixed>
 */
trait QM_ArrayAccess {
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
