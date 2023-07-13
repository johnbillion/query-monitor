<?php declare(strict_types = 1);
/**
 * Theme data transfer object.
 *
 * @package query-monitor
 */

class QM_Data_Theme extends QM_Data {
	/**
	 * @var bool
	 */
	public $is_child_theme;

	/**
	 * @var string
	 */
	public $stylesheet_theme_json;

	/**
	 * @var string
	 */
	public $template_theme_json;

	/**
	 * @var WP_Block_Template|null
	 */
	public $block_template;

	/**
	 * @var array<string, string>
	 */
	public $theme_dirs;

	/**
	 * @var array<string, string>
	 */
	public $theme_folders;

	/**
	 * @var string
	 */
	public $stylesheet;

	/**
	 * @var string
	 */
	public $template;

	/**
	 * @var string
	 */
	public $theme_template_file;

	/**
	 * @var string
	 */
	public $template_path;

	/**
	 * @var ?string
	 */
	public $template_file;

	/**
	 * @var ?array<int, string>
	 */
	public $template_hierarchy;

	/**
	 * @var ?array<int, string>
	 */
	public $timber_files;

	/**
	 * @var ?array<int, string>
	 */
	public $body_class;

	/**
	 * @var array<string|int, string>
	 */
	public $template_parts;

	/**
	 * @var array<string|int, string>
	 */
	public $theme_template_parts;

	/**
	 * @var array<string|int, int>
	 */
	public $count_template_parts;

	/**
	 * @var array<int, array<string, mixed>>
	 */
	public $unsuccessful_template_parts;

}
