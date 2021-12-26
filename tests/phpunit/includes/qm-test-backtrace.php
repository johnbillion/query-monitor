<?php

class QM_Test_Backtrace extends QM_Backtrace {

	/**
	 * @param mixed[] $trace
	 * @return void
	 */
	public function set_trace( array $trace ) {
		$this->trace = $trace;
	}

}
