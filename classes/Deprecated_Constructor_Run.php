<?php declare(strict_types = 1);
/**
 * Class representing a deprecated constructor run.
 *
 * @package query-monitor
 */

/**
 * @extends QM_Wrong<array{
 *   class_name: string,
 *   parent_class: string,
 *   version: string,
 * }>
 */
final class QM_Deprecated_Constructor_Run extends QM_Wrong {
	public function get_message(): string {
		[
			'class_name' => $class_name,
			'parent_class' => $parent_class,
			'version' => $version,
		] = $this->args;

		if ( $parent_class ) {
			return sprintf(
				/* translators: 1: PHP class name, 2: PHP parent class name, 3: Version number, 4: __construct() method. */
				__( 'The called constructor method for %1$s class in %2$s is deprecated since version %3$s! Use %4$s instead.', 'query-monitor' ),
				$class_name,
				$parent_class,
				$version,
				'<code>__construct()</code>'
			);
		}

		return sprintf(
			/* translators: 1: PHP class name, 2: Version number, 3: __construct() method. */
			__( 'The called constructor method for %1$s class is deprecated since version %2$s! Use %3$s instead.', 'query-monitor' ),
			$class_name,
			$version,
			'<code>__construct()</code>'
		);
	}
}
