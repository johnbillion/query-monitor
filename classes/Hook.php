<?php
/**
 * Hook processor.
 *
 * @package query-monitor
 */

class QM_Hook {

	public static function process( $name, array $wp_filter, $hide_qm = false, $hide_core = false ) {

		$actions    = array();
		$components = array();

		if ( isset( $wp_filter[ $name ] ) ) {

			# http://core.trac.wordpress.org/ticket/17817
			$action = $wp_filter[ $name ];

			foreach ( $action as $priority => $callbacks ) {

				foreach ( $callbacks as $callback ) {

					$callback = QM_Util::populate_callback( $callback );

					if ( isset( $callback['component'] ) ) {
						if (
							( $hide_qm && 'query-monitor' === $callback['component']->context )
							|| ( $hide_core && 'core' === $callback['component']->context )
						) {
							continue;
						}

						$components[ $callback['component']->name ] = $callback['component']->name;
					}

					// This isn't used and takes up a ton of memory:
					unset( $callback['function'] );

					$actions[] = array(
						'priority' => $priority,
						'callback' => $callback,
					);

				}
			}
		}

		$parts = array_filter( preg_split( '#[_/-]#', $name ) );

		return array(
			'name'       => $name,
			'actions'    => $actions,
			'parts'      => $parts,
			'components' => $components,
		);

	}

}
