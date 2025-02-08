<?php declare(strict_types = 1);
/**
 * Class representing a drop-in plugin component.
 *
 * @package query-monitor
 */

class QM_Component_Dropin extends QM_Component {
	public function get_name(): string {
		return sprintf(
			/* translators: %s: Drop-in plugin file name */
			__( 'Drop-in: %s', 'query-monitor' ),
			$this->context
		);
	}
}
