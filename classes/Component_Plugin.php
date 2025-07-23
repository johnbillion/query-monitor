<?php declare(strict_types = 1);
/**
 * Class representing a plugin component.
 *
 * @package query-monitor
 */

class QM_Component_Plugin extends QM_Component {
	public function get_name(): string {
		return sprintf(
			/* translators: %s: Plugin name */
			__( 'Plugin: %s', 'query-monitor' ),
			$this->context
		);
	}
}
