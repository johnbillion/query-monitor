<?php declare(strict_types = 1);
/**
 * Hook processor.
 *
 * @package query-monitor
 */

class QM_Hook {

	/**
	 * @param string $name
	 * @param ?WP_Hook $hook
	 * @param bool $hide_qm
	 * @param bool $hide_core
	 * @return array<int, array<string, mixed>>
	 * @phpstan-return array{
	 *   name: string,
	 *   actions: list<array{
	 *     priority: int,
	 *     callback: QM_Callback|WP_Error,
	 *   }>,
	 *   parts: list<string>,
	 *   components: list<string>,
	 * }
	 */
	public static function process( $name, WP_Hook $hook = null, $hide_qm = false, $hide_core = false ) {

		$actions = array();
		$components = array();

		if ( $hook instanceof \WP_Hook ) {
			foreach ( $hook as $priority => $callbacks ) {

				foreach ( $callbacks as $cb ) {
					try {
						$callback = QM_Callback::from_callable( $cb['function'] );
						if (
							( $hide_qm && 'query-monitor' === $callback->component->context )
							|| ( $hide_core && 'core' === $callback->component->context )
						) {
							continue;
						}

						$components[] = $callback->component->name;
					} catch ( QM_CallbackException $e ) {
						$callback = $e->to_wp_error();
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
			'actions' => $actions,
			'parts' => $parts,
			'components' => array_unique( $components ),
		);

	}

}
