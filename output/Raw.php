<?php
/**
 * Abstract output class for raw output encoded as JSON.
 *
 * @package query-monitor
 */

abstract class QM_Output_Raw extends QM_Output {
	/**
	 * @return array<string, mixed>
	 */
	abstract public function get_output();
}
