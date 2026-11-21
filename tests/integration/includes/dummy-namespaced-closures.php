<?php declare(strict_types = 1);

namespace QM\Tests\Closures;

/**
 * @return mixed[]
 */
function get_trace(): array {
	$outer = function (): array {
		$inner = function (): array {
			return debug_backtrace();
		};

		return $inner();
	};

	return $outer();
}
