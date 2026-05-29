<?php declare(strict_types = 1);
/**
 * Block editor (née Gutenberg) collector.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @extends QM_DataCollector<QM_Data_Block_Editor>
 */
class QM_Collector_Block_Editor extends QM_DataCollector {

	public $id = 'block_editor';

	/**
	 * @var array<int, mixed[]>
	 */
	protected $block_context = array();

	/**
	 * @var array<int, QM_Timer|false>
	 */
	protected $block_timing = array();

	/**
	 * @var array<int, QM_Timer>
	 */
	protected $block_timer_stack = array();

	public function get_storage(): QM_Data {
		return new QM_Data_Block_Editor();
	}

	/**
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		add_filter( 'pre_render_block', array( $this, 'filter_pre_render_block' ), 9999, 2 );
		add_filter( 'render_block_context', array( $this, 'filter_render_block_context' ), -9999, 2 );
		add_filter( 'render_block_data', array( $this, 'filter_render_block_data' ), -9999 );
		add_filter( 'render_block', array( $this, 'filter_render_block' ), 9999, 2 );
	}

	/**
	 * @return void
	 */
	public function tear_down() {
		remove_filter( 'pre_render_block', array( $this, 'filter_pre_render_block' ), 9999 );
		remove_filter( 'render_block_context', array( $this, 'filter_render_block_context' ), -9999 );
		remove_filter( 'render_block_data', array( $this, 'filter_render_block_data' ), -9999 );
		remove_filter( 'render_block', array( $this, 'filter_render_block' ), 9999 );

		parent::tear_down();
	}

	/**
	 * @return array<int, string>
	 */
	public function get_concerned_actions() {
		return array(
			'enqueue_block_assets',
			'enqueue_block_editor_assets',
		);
	}

	/**
	 * @return array<int, string>
	 */
	public function get_concerned_filters() {
		return array(
			'allowed_block_types',
			'allowed_block_types_all',
			'block_categories_all',
			'block_editor_settings_all',
			'block_type_metadata',
			'block_type_metadata_settings',
			'block_parser_class',
			'pre_render_block',
			'register_block_type_args',
			'render_block_context',
			'render_block_data',
			'render_block',
			'should_load_separate_core_block_assets',
			'use_block_editor_for_post',
			'use_block_editor_for_post_type',
			'use_widgets_block_editor',
		);
	}

	/**
	 * @param string|null $pre_render
	 * @param mixed[] $block
	 * @return string|null
	 */
	public function filter_pre_render_block( $pre_render, array $block ) {
		if ( null !== $pre_render ) {
			$this->block_timing[] = false;
		}

		return $pre_render;
	}

	/**
	 * @param mixed[] $context
	 * @param mixed[] $block
	 * @return mixed[]
	 */
	public function filter_render_block_context( array $context, array $block ) {
		$this->block_context[] = $context;

		return $context;
	}

	/**
	 * @param mixed[] $block
	 * @return mixed[]
	 */
	public function filter_render_block_data( array $block ) {
		$timer = new QM_Timer();
		$timer->start();

		$this->block_timer_stack[] = $timer;
		$this->block_timing[] = $timer;

		return $block;
	}

	/**
	 * @param string $block_content
	 * @param mixed[] $block
	 * @return string
	 */
	public function filter_render_block( $block_content, array $block ) {
		$timer = array_pop( $this->block_timer_stack );

		if ( $timer instanceof QM_Timer ) {
			$timer->stop();
		}

		return $block_content;
	}

	public function process() {
		/** @var ?string $_wp_current_template_content */
		global $_wp_current_template_content;

		if ( ! empty( $_wp_current_template_content ) ) {
			// Full site editor:
			$content = $_wp_current_template_content;
		} elseif ( is_singular() ) {
			// Post editor:
			$post = get_post( get_queried_object_id() );

			if ( ! $post ) {
				return;
			}

			$content = $post->post_content;
		} else {
			// Nada:
			return;
		}

		$this->data->post_has_blocks = has_blocks( $content );

		if ( $this->data->post_has_blocks ) {
			$blocks = array_values( parse_blocks( $content ) );
			$this->data->post_blocks = array_values( array_filter( array_map( array( $this, 'process_block' ), $blocks ) ) );
		}
	}

	/**
	 * @phpstan-param array{
	 *   blockName: string|null,
	 *   attrs: mixed[],
	 *   innerBlocks: mixed[],
	 *   innerHTML: string,
	 *   innerContent: array<int, string|null>,
	 * } $block
	 * @param mixed[] $block
	 */
	protected function process_block( array $block ) : ?QM_Data_Post_Block {
		$context = array_shift( $this->block_context );
		$timing = array_shift( $this->block_timing );

		// Remove empty blocks caused by two consecutive line breaks in content
		if ( ! $block['blockName'] && ! trim( $block['innerHTML'] ) ) {
			return null;
		}

		$block_type = WP_Block_Type_Registry::get_instance()->get_registered( $block['blockName'] );
		$dynamic = false;
		$callback = null;

		if ( $block_type && $block_type->is_dynamic() ) {
			$dynamic = true;
			$callback = QM_Util::determine_callback( array(
				'function' => $block_type->render_callback,
			) );
		}

		// Strip multiple consecutive line breaks that end up in parsed block content.
		$inner_html = preg_replace( '/(\r?\n){2,}/', "\n", trim( $block['innerHTML'] ) );

		$result = new QM_Data_Post_Block();
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Matches WP block parser format.
		$result->blockName = $block['blockName'];
		$result->attrs = $block['attrs'];
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Matches WP block parser format.
		$result->innerContent = $block['innerContent'];
		$result->dynamic = $dynamic;
		$result->callback = $callback;
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Matches WP block parser format.
		$result->innerHTML = $inner_html;
		$result->context = $context;
		$result->timing = $timing ? $timing->get_time() : null;

		if ( ! empty( $block['innerBlocks'] ) ) {
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Matches WP block parser format.
			$result->innerBlocks = array_values( array_filter( array_map( array( $this, 'process_block' ), $block['innerBlocks'] ) ) );
		} else {
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Matches WP block parser format.
			$result->innerBlocks = array();
		}

		return $result;
	}
}

/**
 * @param array<string, QM_Collector> $collectors
 * @param QueryMonitor $qm
 * @return array<string, QM_Collector>
 */
function register_qm_collector_block_editor( array $collectors, QueryMonitor $qm ) {
	$collectors['block_editor'] = new QM_Collector_Block_Editor();
	return $collectors;
}

add_filter( 'qm/collectors', 'register_qm_collector_block_editor', 10, 2 );
