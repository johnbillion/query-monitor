<?php
/**
 * Block editor data transfer object.
 *
 * @package query-monitor
 */

class QM_Data_Block_Editor extends QM_Data {
	/**
	 * @var array<int, string>
	 */
	public $all_dynamic_blocks = array();

	/**
	 * @var bool
	 */
	public $block_editor_enabled = false;

	/**
	 * @var bool
	 */
	public $has_block_context = false;

	/**
	 * @var bool
	 */
	public $has_block_timing = false;

	/**
	 * @var array<int, mixed>|null
	 */
	public $post_blocks = array();

	/**
	 * @var bool
	 */
	public $post_has_blocks = false;

	/**
	 * @var int
	 */
	public $total_blocks = 0;

}
