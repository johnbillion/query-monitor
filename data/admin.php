<?php
/**
 * Admin screen data transfer object.
 *
 * @package query-monitor
 */

class QM_Data_Admin extends QM_Data {
	/**
	 * @var string
	 */
	public $base;

	/**
	 * @var string
	 */
	public $pagenow;

	/**
	 * @var string
	 */
	public $typenow;

	/**
	 * @var string
	 */
	public $taxnow;

	/**
	 * @var string
	 */
	public $hook_suffix;

	/**
	 * @var ?WP_Screen
	 */
	public $current_screen;

	/**
	 * @var array<string, string>
	 */
	public $list_table;
}
