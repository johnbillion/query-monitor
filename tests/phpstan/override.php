<?php declare(strict_types = 1);

// PHPStan running on PHP 7.4 parses `#[\Override]` as a comment, so it can only validate the attribute on PHP 8.
return [
	'parameters' => [
		'checkMissingOverrideMethodAttribute' => PHP_VERSION_ID >= 80000,
	],
];
