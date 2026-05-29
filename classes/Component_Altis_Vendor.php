<?php declare(strict_types = 1);
/**
 * Class representing an Altis vendor dependency component.
 *
 * @package query-monitor
 */

class QM_Component_Altis_Vendor extends QM_Component {
	public function get_name(): string {
		return sprintf(
			/* translators: %s: Dependency name */
			__( 'Dependency: %s', 'query-monitor' ),
			$this->context
		);
	}
}
