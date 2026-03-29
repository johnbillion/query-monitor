<?php declare(strict_types = 1);

namespace QM\Tests\Supports;

class TestBacktrace extends \QM_Backtrace {

	/**
	 * @param mixed[] $trace
	 * @param array<string, mixed> $args
	 */
	public function __construct( array $trace = array(), array $args = array() ) {
		parent::__construct( $args, $trace );
	}

}
