<?php
/**
 * Languages data transfer object.
 *
 * @package query-monitor
 */

class QM_Data_Languages extends QM_Data {
	/**
	 * @var array<string, array<int, array<string, mixed>>>
	 * @phpstan-var array<string, array<int, array{
	 *   caller: mixed,
	 *   domain: string,
	 *   file: string|false,
	 *   found: int|false,
	 *   found_formatted: string,
	 *   handle: string|null,
	 *   type: 'gettext'|'jed',
	 * }>>
	 */
	public $languages;

	/**
	 * @var string
	 */
	public $locale;

	/**
	 * @var string
	 */
	public $user_locale;
}
