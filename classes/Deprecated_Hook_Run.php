<?php declare(strict_types = 1);
/**
 * Class representing a deprecated hook being used.
 *
 * @package query-monitor
 */

/**
 * @extends QM_Wrong<array{
 *   hook: string,
 *   replacement: string,
 *   version: string,
 *   message: string,
 * }>
 */
final class QM_Deprecated_Hook_Run extends QM_Wrong {
	public function get_message(): string {
		[
			'hook' => $hook,
			'replacement' => $replacement,
			'version' => $version,
			'message' => $message,
		] = $this->args;

		if ( $replacement ) {
			return sprintf(
				/* translators: 1: WordPress hook name, 2: Version number, 3: Alternative hook name, 4: Optional message regarding the change. */
				__( 'Hook %1$s is deprecated since version %2$s! Use %3$s instead. %4$s', 'query-monitor' ),
				$hook,
				$version,
				$replacement,
				$message
			);
		}

		return sprintf(
			/* translators: 1: WordPress hook name, 2: Version number, 3: Optional message regarding the change. */
			__( 'Hook %1$s is deprecated since version %2$s with no alternative available. %3$s', 'query-monitor' ),
			$hook,
			$version,
			$message
		);
	}
}
