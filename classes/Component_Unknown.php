<?php declare(strict_types = 1);
/**
 * Class representing an unknown component.
 *
 * @package query-monitor
 */

final class QM_Component_Unknown extends QM_Component {
	public function get_name(): string {
		$name = __( 'Unknown', 'query-monitor' );
		$type = $this->type;

		/**
		 * Filters the user-facing name to display for a custom or unknown component.
		 *
		 * The dynamic portion of the hook name, `$type`, refers to the component identifier.
		 *
		 * See also the corresponding filters:
		 *
		 *  - `qm/component_dirs`
		 *  - `qm/component_type/{$type}`
		 *  - `qm/component_context/{$type}`
		 *
		 * @since 3.6.0
		 *
		 * @param string $name The component name.
		 * @param string $file The full file path for the file within the component.
		 */
		$name = apply_filters( "qm/component_name/{$type}", $name, $this->file );

		return $name;
	}
}
