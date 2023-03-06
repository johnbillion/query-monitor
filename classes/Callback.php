<?php declare(strict_types = 1);
/**
 * Class that represents a callback that may or may not be a valid callable.
 *
 * @package query-monitor
 */

final class QM_Callback {
	public $name;
	public $file;
	public $line;
	public $component;
	public $error;

	public static function from_callable(): self {
		return new self();
	}

	public function is_valid(): bool {
		return ! isset( $this->error );
	}

	private function __construct() {}
}
