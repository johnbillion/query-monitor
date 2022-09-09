<?php

declare(strict_types = 1);

namespace QM\Tests\Supports;

class TestBacktrace extends \QM_Backtrace {

	/**
	 * @param mixed[] $trace
	 * @return void
	 */
	public function set_trace( array $trace ) {
		$this->trace = $trace;
	}

}
