<?php
/**
 * Abstract output class for raw output encoded as JSON.
 *
 * @package query-monitor
 */

abstract class QM_Output_Raw extends QM_Output {

	public function output() {
		return $this->get_output();
	}

}
