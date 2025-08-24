<?php declare(strict_types = 1);
/**
 * Class representing a doing it wrong run.
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
final class QM_Doing_It_Wrong_Run extends QM_Wrong {
	public function get_message(): string {
		[
			'function_name' => $function_name,
			'message' => $message,
			'version' => $version,
		] = $this->args;

		if ( $version ) {
			$version = sprintf(
				/* translators: %s: Version number. */
				__( '(This message was added in version %s.)', 'query-monitor' ),
				$version
			);
		}

		return sprintf(
			/* translators: Developer debugging message. 1: PHP function name, 2: Explanatory message, 3: WordPress version number. */
			__( 'Function %1$s was called incorrectly. %2$s %3$s', 'query-monitor' ),
			$function_name,
			$message,
			$version
		);
	}
}
