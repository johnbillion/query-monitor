<?php declare(strict_types = 1);
/**
 * Class representing a theme stylesheet component.
 *
 * @package query-monitor
 */

class QM_Component_Stylesheet extends QM_Component {
	public function get_name(): string {
		if ( is_child_theme() ) {
			return __( 'Child Theme', 'query-monitor' );
		}

		return __( 'Theme', 'query-monitor' );
	}
}
