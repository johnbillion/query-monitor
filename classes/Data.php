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
	 * @var array<string, array<string, mixed>>
	 * @phpstan-var array<string, array{
	 *   component: string,
	 *   ltime: float,
	 *   types: array<array-key, int>,
	 * }>
	 */
	public $component_times = array();

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
	final public function offsetGet( $offset ) {
		return ( is_string( $offset ) && isset( $this->$offset ) ) ? $this->$offset : null;
	}
}
