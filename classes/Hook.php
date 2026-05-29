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
	 * @param ?WP_Hook $hook
	 * @param bool $hide_qm
	 * @param bool $hide_core
	 * @phpstan-param 'action'|'filter' $type
	 * @return array{
	 *   name: string,
	 *   type: 'action'|'filter',
	 *   actions: list<array{
	 *     priority: int,
	 *     callback: QM_Data_Callback,
	 *   }>,
	 *   components: array<string, QM_Component>,
	 * }
	 */
	public static function process( $name, string $type, ?WP_Hook $hook, $hide_qm = false, $hide_core = false ) {

		$actions = array();
		$components = array();

		if ( $hook ) {
			foreach ( $hook as $priority => $callbacks ) {

				foreach ( $callbacks as $cb ) {

					$callback = QM_Util::determine_callback( $cb );

					if ( isset( $callback->component ) ) {
						if (
							( $hide_qm && 'query-monitor' === $callback->component->context )
							|| ( $hide_core && 'core' === $callback->component->context )
						) {
							continue;
						}

						$components[ $callback->component->get_id() ] = $callback->component;
					}

					$actions[] = array(
						'priority' => $priority,
						'callback' => $callback,
					);

				}
			}
		}

		return array(
			'name' => $name,
			'type' => $type,
			'actions' => $actions,
			'components' => $components,
		);

	}

}
