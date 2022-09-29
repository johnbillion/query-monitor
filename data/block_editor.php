<?php declare(strict_types = 1);
/**
 * Block editor data transfer object.
 *
 * @package query-monitor
 */

class QM_Data_Block_Editor extends QM_Data {
	/**
	 * @var array<int, string>
	 */
	public $all_dynamic_blocks;

	/**
	 * @var bool
	 */
	public $block_editor_enabled;

	/**
	 * @var bool
	 */
	public $has_block_context;

	/**
	 * @var bool
	 */
	public $has_block_timing;

	/**
	 * @var array<int, mixed>|null
	 */
	public $post_blocks;

	/**
	 * @var bool
	 */
	public $post_has_blocks;

	/**
	 * @var int
	 */
	public $total_blocks;

}
