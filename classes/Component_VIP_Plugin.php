<?php declare(strict_types = 1);
/**
 * Class representing a WordPress VIP plugin component.
 *
 * @package query-monitor
 */

class QM_Component_VIP_Plugin extends QM_Component {
	public function get_name(): string {
		return sprintf(
			/* translators: %s: Plugin name */
			__( 'VIP Plugin: %s', 'query-monitor' ),
			$this->context
		);
	}
}
