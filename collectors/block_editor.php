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
	 * @var QM_Timer|null
	 */
	protected $block_timer = null;

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
	public function get_concerned_filters() {
		return array(
			'allowed_block_types',
			'allowed_block_types_all',
			'block_editor_settings_all',
			'block_type_metadata',
			'block_type_metadata_settings',
			'block_parser_class',
			'pre_render_block',
			'register_block_type_args',
			'render_block_context',
			'render_block_data',
			'render_block',
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
		$this->block_timer = new QM_Timer();
		$this->block_timer->start();

		return $block;
	}

	/**
	 * @param string $block_content
	 * @param mixed[] $block
	 * @return string
	 */
	public function filter_render_block( $block_content, array $block ) {
		if ( isset( $this->block_timer ) ) {
			$this->block_timing[] = $this->block_timer->stop();
		}

		return $block_content;
	}

	public function process() {
		global $_wp_current_template_content;

		if ( ! empty( $_wp_current_template_content ) ) {
			// Full site editor:
			$content = $_wp_current_template_content;
		} elseif ( is_singular() ) {
			// Post editor:
			$content = get_post( get_queried_object_id() )->post_content;
		} else {
			// Nada:
			return;
		}

		$this->data->post_has_blocks = has_blocks( $content );
		$this->data->post_blocks = array_values( parse_blocks( $content ) );
		$this->data->all_dynamic_blocks = get_dynamic_block_names();
		$this->data->total_blocks = 0;
		$this->data->has_block_context = false;
		$this->data->has_block_timing = false;

		if ( $this->data->post_has_blocks ) {
			$this->data->post_blocks = array_values( array_filter( array_map( array( $this, 'process_block' ), $this->data->post_blocks ) ) );
		}
	}

	/**
	 * @param mixed[] $block
	 * @return mixed[]|null
	 */
	protected function process_block( array $block ) {
		$context = array_shift( $this->block_context );
		$timing = array_shift( $this->block_timing );

		// Remove empty blocks caused by two consecutive line breaks in content
		if ( ! $block['blockName'] && ! trim( $block['innerHTML'] ) ) {
			return null;
		}

		$this->data->total_blocks++;

		$block_type = WP_Block_Type_Registry::get_instance()->get_registered( $block['blockName'] );
		$dynamic = false;
		$callback = null;

		if ( $block_type && $block_type->is_dynamic() ) {
			$dynamic = true;
			$callback = QM_Util::populate_callback( array(
				'function' => $block_type->render_callback,
			) );
		}

		$timing = array_shift( $this->block_timing );

		$block['dynamic'] = $dynamic;
		$block['callback'] = $callback;
		$block['innerHTML'] = trim( $block['innerHTML'] );
		$block['size'] = strlen( $block['innerHTML'] );

		if ( $context ) {
			$block['context'] = $context;
			$this->data->has_block_context = true;
		}

		if ( $timing ) {
			$block['timing'] = $timing->get_time();
			$this->data->has_block_timing = true;
		}

		if ( ! empty( $block['innerBlocks'] ) ) {
			$block['innerBlocks'] = array_values( array_filter( array_map( array( $this, 'process_block' ), $block['innerBlocks'] ) ) );
		}

		return $block;
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
