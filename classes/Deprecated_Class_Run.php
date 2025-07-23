<?php declare(strict_types = 1);
/**
 * Class representing a deprecated class being used.
 *
 * @package query-monitor
 */

/**
 * @extends QM_Wrong<array{
 *   class_name: string,
 *   replacement: string,
 *   version: string,
 * }>
 */
final class QM_Deprecated_Class_Run extends QM_Wrong {
	public function get_message(): string {
		[
			'class_name' => $class_name,
			'replacement' => $replacement,
			'version' => $version,
		] = $this->args;

		if ( $replacement ) {
			return sprintf(
				/* translators: 1: PHP class name, 2: Version number, 3: Alternative class or function name. */
				__( 'Class %1$s is deprecated since version %2$s! Use %3$s instead.', 'query-monitor' ),
				$class_name,
				$version,
				$replacement
			);
		}

		return sprintf(
			/* translators: 1: PHP class name, 2: Version number. */
			__( 'Class %1$s is deprecated since version %2$s with no alternative available.', 'query-monitor' ),
			$class_name,
			$version
		);
	}
}
