<?php declare(strict_types = 1);
/**
 * Class representing a deprecated function run.
 *
 * @package query-monitor
 */

/**
 * @extends QM_Wrong<array{
 *   function_name: string,
 *   version: string,
 *   replacement: string,
 * }>
 */
final class QM_Deprecated_Function_Run extends QM_Wrong {
	public function get_message(): string {
		[
			'function_name' => $function_name,
			'version' => $version,
			'replacement' => $replacement,
		] = $this->args;

		if ( $replacement ) {
			return sprintf(
				/* translators: 1: PHP function name, 2: Version number, 3: Alternative function name. */
				__( 'Function %1$s is deprecated since version %2$s! Use %3$s instead.', 'query-monitor' ),
				$function_name,
				$version,
				$replacement
			);
		}

		return sprintf(
			/* translators: 1: PHP function name, 2: Version number. */
			__( 'Function %1$s is deprecated since version %2$s with no alternative available.', 'query-monitor' ),
			$function_name,
			$version
		);
	}
}
