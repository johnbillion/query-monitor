<?php

class QM_Test_Backtrace extends QM_Backtrace {

	public function set_trace( array $trace ) {
		$this->trace = $trace;
	}

}
