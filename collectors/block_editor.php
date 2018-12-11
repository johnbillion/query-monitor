<?php
/**
 * Block editor (née Gutenberg) collector.
 *
 * @package query-monitor
 */

class QM_Collector_Block_Editor extends QM_Collector {

	public $id = 'block_editor';
	protected static $block_type_registry = null;

	public function name() {
		return __( 'Blocks', 'query-monitor' );
	}

	public function process() {
		$this->data['block_editor_enabled'] = self::wp_block_editor_enabled();

		if ( ! is_singular() ) {
			return;
		}

		$this->data['post_has_blocks']    = self::wp_has_blocks( get_post()->post_content );
		$this->data['post_blocks']        = self::wp_parse_blocks( get_post()->post_content );
		$this->data['all_dynamic_blocks'] = self::wp_get_dynamic_block_names();
		$this->data['total_blocks']       = 0;

		if ( $this->data['post_has_blocks'] ) {
			self::$block_type_registry = WP_Block_Type_Registry::get_instance();

			$this->data['post_blocks'] = array_values( array_filter( array_map( array( $this, 'process_block' ), $this->data['post_blocks'] ) ) );
		}
	}

	protected function process_block( array $block ) {
		// Remove empty blocks caused by two consecutive line breaks in content
		if ( ! $block['blockName'] && ! trim( $block['innerHTML'] ) ) {
			return null;
		}

		$this->data['total_blocks']++;

		$block_type = self::$block_type_registry->get_registered( $block['blockName'] );
		$dynamic    = false;
		$callback   = null;

		if ( $block_type && $block_type->is_dynamic() ) {
			$dynamic  = true;
			$callback = QM_Util::populate_callback( array(
				'function' => $block_type->render_callback,
			) );
		}

		$block['dynamic']  = $dynamic;
		$block['callback'] = $callback;
		$block['size']     = strlen( $block['innerHTML'] );

		if ( ! empty( $block['innerBlocks'] ) ) {
			$block['innerBlocks'] = array_values( array_filter( array_map( array( $this, 'process_block' ), $block['innerBlocks'] ) ) );
		}

		return $block;
	}

	protected static function wp_block_editor_enabled() {
		return ( function_exists( 'parse_blocks' ) || function_exists( 'gutenberg_parse_blocks' ) );
	}

	protected static function wp_has_blocks( $content ) {
		if ( function_exists( 'has_blocks' ) ) {
			return has_blocks( $content );
		} elseif ( function_exists( 'gutenberg_has_blocks' ) ) {
			return gutenberg_has_blocks( $content );
		}

		return false;
	}

	protected static function wp_parse_blocks( $content ) {
		if ( function_exists( 'parse_blocks' ) ) {
			return parse_blocks( $content );
		} elseif ( function_exists( 'gutenberg_parse_blocks' ) ) {
			return gutenberg_parse_blocks( $content );
		}

		return null;
	}

	protected static function wp_get_dynamic_block_names() {
		if ( function_exists( 'get_dynamic_block_names' ) ) {
			return get_dynamic_block_names();
		}

		return array();
	}

}

function register_qm_collector_block_editor( array $collectors, QueryMonitor $qm ) {
	$collectors['block_editor'] = new QM_Collector_Block_Editor();
	return $collectors;
}

add_filter( 'qm/collectors', 'register_qm_collector_block_editor', 10, 2 );
