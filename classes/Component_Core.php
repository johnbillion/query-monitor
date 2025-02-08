<?php declare(strict_types = 1);
/**
 * Class representing the WordPress core component.
 *
 * @package query-monitor
 */

class QM_Component_Core extends QM_Component {
	public function get_name(): string {
		return __( 'WordPress Core', 'query-monitor' );
	}
}
