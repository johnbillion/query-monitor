<?php
/**
 * Block editor (née Gutenberg) collector.
 *
 * @package query-monitor
 */

class QM_Collector_Block_Editor extends QM_Collector {

	public $id = 'block_editor';

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

		if ( $this->data['post_has_blocks'] ) {
			$block_type_registry = WP_Block_Type_Registry::get_instance();

			foreach ( $this->data['post_blocks'] as $i => $block ) {
				$block_type = $block_type_registry->get_registered( $block['blockName'] );
				$dynamic    = false;
				$callback   = null;

				if ( $block_type && $block_type->is_dynamic() ) {
					$dynamic  = true;
					$callback = QM_Util::populate_callback( array(
						'function' => $block_type->render_callback,
					) );
				}

				$this->data['post_blocks'][ $i ]['dynamic']  = $dynamic;
				$this->data['post_blocks'][ $i ]['callback'] = $callback;
			}
		}
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
