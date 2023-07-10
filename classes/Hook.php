<?php declare(strict_types = 1);
/**
 * Hook processor.
 *
 * @package query-monitor
 */

class QM_Hook {

	/**
	 * @param string $name
	 * @param string $type
	 * @param array<string, WP_Hook> $wp_filter
	 * @param bool $hide_qm
	 * @param bool $hide_core
	 * @return array<int, array<string, mixed>>
	 * @phpstan-param 'action'|'filter' $type
	 * @phpstan-return array{
	 *   name: string,
	 *   type: 'action'|'filter',
	 *   actions: list<array{
	 *     priority: int,
	 *     callback: array<string, mixed>,
	 *   }>,
	 *   parts: list<string>,
	 *   components: array<string, string>,
	 * }
	 */
	public static function process( $name, string $type, array $wp_filter, $hide_qm = false, $hide_core = false ) {

		$actions = array();
		$components = array();

		if ( isset( $wp_filter[ $name ] ) ) {

			# http://core.trac.wordpress.org/ticket/17817
			$action = $wp_filter[ $name ];

			foreach ( $action as $priority => $callbacks ) {

				foreach ( $callbacks as $cb ) {

					$callback = QM_Util::populate_callback( $cb );

					if ( isset( $callback['component'] ) ) {
						if (
							( $hide_qm && 'query-monitor' === $callback['component']->context )
							|| ( $hide_core && 'core' === $callback['component']->context )
						) {
							continue;
						}

						$components[ $callback['component']->name ] = $callback['component']->name;
					}

					$actions[] = array(
						'priority' => $priority,
						'callback' => $callback,
					);

				}
			}
		}

		$parts = array_values( array_filter( (array) preg_split( '#[_/.-]#', $name ) ) );

		return array(
			'name' => $name,
			'type' => $type,
			'actions' => $actions,
			'parts' => $parts,
			'components' => $components,
		);

	}

}
