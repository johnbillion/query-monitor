<?php declare(strict_types = 1);
/**
 * Class representing a theme template (parent theme) component.
 *
 * @package query-monitor
 */

class QM_Component_Template extends QM_Component {
	public function get_name(): string {
		return __( 'Parent Theme', 'query-monitor' );
	}
}
