<?php declare(strict_types = 1);
/**
 * Class that represents a generic error exception.
 *
 * @package query-monitor
 */

abstract class QM_Exception extends Exception {
	/**
	 * @var mixed
	 */
	protected $data = '';

	final public function to_wp_error(): WP_Error {
		return new WP_Error( 'qm-' . $this->getCode(), $this->getMessage(), $this->data );
	}

	/**
	 * @param mixed $data
	 */
	final public function add_data( $data ): self {
		$this->data = $data;

		return $this;
	}
}
