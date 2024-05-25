<?php declare(strict_types = 1);
/**
 * Languages data transfer object.
 *
 * @package query-monitor
 */

class QM_Data_Languages extends QM_Data {
	/**
	 * @var array<string, array<string, array<string, mixed>>>
	 * @phpstan-var array<string, array<string, array{
	 *   caller: mixed,
	 *   domain: string,
	 *   file: string|false,
	 *   found: int|false,
	 *   handle: string|null,
	 *   type: 'gettext'|'jed'|'php'|'unknown',
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

	/**
	 * @var string
	 */
	public $determined_locale;

	/**
	 * @var string
	 */
	public $language_attributes;

	/**
	 * @var string
	 */
	public $mlp_language;

	/**
	 * @var string
	 */
	public $pll_language;

	/**
	 * @var int
	 */
	public $total_size;
}
