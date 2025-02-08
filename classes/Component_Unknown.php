<?php declare(strict_types = 1);
/**
 * Class representing an unknown component.
 *
 * @package query-monitor
 */

final class QM_Component_Unknown extends QM_Component {
	public function get_name(): string {
		// @TODO this needs the filter logic
		return __( 'Unknown', 'query-monitor' );
	}
}
