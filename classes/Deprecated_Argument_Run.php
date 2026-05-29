<?php declare(strict_types = 1);
/**
 * Class representing a deprecated argument being used.
 *
 * @package query-monitor
 */

/**
 * @extends QM_Wrong<array{
 *   function_name: string,
 *   message: string,
 *   version: string,
 * }>
 */
final class QM_Deprecated_Argument_Run extends QM_Wrong {
	public function get_message(): string {
		[
			'function_name' => $function_name,
			'message' => $message,
			'version' => $version,
		] = $this->args;

		if ( $message ) {
			return sprintf(
				/* translators: 1: PHP function name, 2: Version number, 3: Optional message regarding the change. */
				__( 'Function %1$s was called with an argument that is deprecated since version %2$s! %3$s', 'query-monitor' ),
				$function_name,
				$version,
				$message
			);
		}

		return sprintf(
			/* translators: 1: PHP function name, 2: Version number. */
			__( 'Function %1$s was called with an argument that is deprecated since version %2$s with no alternative available.', 'query-monitor' ),
			$function_name,
			$version
		);
	}
}
