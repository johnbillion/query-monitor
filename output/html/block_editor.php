<?php declare(strict_types = 1);
/**
 * Block editor data output for HTML pages.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QM_Output_Html_Block_Editor extends QM_Output_Html {

	/**
	 * Collector instance.
	 *
	 * @var QM_Collector_Block_Editor Collector.
	 */
	protected $collector;

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 55 );
	}

	/**
	 * @return string
	 */
	public function name() {
		return __( 'Blocks', 'query-monitor' );
	}

	/**
	 * @return void
	 */
	public function output() {
		/** @var QM_Data_Block_Editor */
		$data = $this->collector->get_data();

		if ( empty( $data->block_editor_enabled ) || empty( $data->post_blocks ) ) {
			return;
		}

		if ( ! $data->post_has_blocks ) {
			$this->before_non_tabular_output();

			$notice = __( 'This post contains no blocks.', 'query-monitor' );
			echo $this->build_notice( $notice ); // WPCS: XSS ok.

			$this->after_non_tabular_output();

			return;
		}

		$this->before_tabular_output();

		echo '<thead>';
		echo '<tr>';
		echo '<th scope="col">#</th>';
		echo '<th scope="col">' . esc_html__( 'Block Name', 'query-monitor' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'Attributes', 'query-monitor' ) . '</th>';

		if ( $data->has_block_context ) {
			echo '<th scope="col">' . esc_html__( 'Context', 'query-monitor' ) . '</th>';
		}

		echo '<th scope="col">' . esc_html__( 'Render Callback', 'query-monitor' ) . '</th>';

		if ( $data->has_block_timing ) {
			echo '<th scope="col" class="qm-num">' . esc_html__( 'Render Time', 'query-monitor' ) . '</th>';
		}

		echo '<th scope="col">' . esc_html__( 'Inner HTML', 'query-monitor' ) . '</th>';
		echo '</tr>';
		echo '</thead>';

		echo '<tbody>';

		foreach ( $data->post_blocks as $i => $block ) {
			self::render_block( ++$i, $block, $data );
		}

		echo '</tbody>';

		echo '<tfoot>';
		echo '<tr>';

		$colspan = 5;

		if ( $data->has_block_context ) {
			$colspan++;
		}

		if ( $data->has_block_timing ) {
			$colspan++;
		}

		printf(
			'<td colspan="%1$d">%2$s</td>',
			intval( $colspan ),
			sprintf(
				/* translators: %s: Total number of content blocks used */
				esc_html( _nx( 'Total: %s', 'Total: %s', $data->total_blocks, 'Content blocks used', 'query-monitor' ) ),
				'<span class="qm-items-number">' . esc_html( number_format_i18n( $data->total_blocks ) ) . '</span>'
			)
		);
		echo '</tr>';
		echo '</tfoot>';

		$this->after_tabular_output();
	}

	/**
	 * @param int|string $i
	 * @param array<string, mixed> $block
	 * @param QM_Data_Block_Editor $data
	 * @return void
	 */
	protected static function render_block( $i, array $block, QM_Data_Block_Editor $data ) {
		$block_error = false;
		$row_class = '';
		$referenced_post = null;
		$referenced_type = null;
		$referenced_template_part = null;
		$referenced_pto = null;
		$error_message = null;

		if ( 'core/block' === $block['blockName'] && ! empty( $block['attrs']['ref'] ) ) {
			$referenced_post = get_post( $block['attrs']['ref'] );

			if ( ! $referenced_post ) {
				$block_error = true;
				$error_message = esc_html__( 'Referenced block does not exist.', 'query-monitor' );
			} else {
				$referenced_type = $referenced_post->post_type;
				$referenced_pto = get_post_type_object( $referenced_type );
				if ( 'wp_block' !== $referenced_type ) {
					$block_error = true;
					$error_message = sprintf(
						/* translators: %1$s: Erroneous post type name, %2$s: WordPress block post type name */
						esc_html__( 'Referenced post is of type %1$s instead of %2$s.', 'query-monitor' ),
						'<code>' . esc_html( $referenced_type ) . '</code>',
						'<code>wp_block</code>'
					);
				}
			}
		}

		$media_blocks = array(
			'core/audio' => 'id',
			'core/cover' => 'id',
			'core/cover-image' => 'id',
			'core/file' => 'id',
			'core/image' => 'id',
			'core/media-text' => 'mediaId', // (╯°□°）╯︵ ┻━┻
			'core/video' => 'id',
		);

		if ( isset( $media_blocks[ $block['blockName'] ] ) && is_array( $block['attrs'] ) && ! empty( $block['attrs'][ $media_blocks[ $block['blockName'] ] ] ) ) {
			$referenced_post = get_post( $block['attrs'][ $media_blocks[ $block['blockName'] ] ] );

			if ( ! $referenced_post ) {
				$block_error = true;
				$error_message = esc_html__( 'Referenced media does not exist.', 'query-monitor' );
			} else {
				$referenced_type = $referenced_post->post_type;
				$referenced_pto = get_post_type_object( $referenced_type );
				if ( 'attachment' !== $referenced_type ) {
					$block_error = true;
					$error_message = sprintf(
						/* translators: %1$s: Erroneous post type name, %2$s: WordPress attachment post type name */
						esc_html__( 'Referenced media is of type %1$s instead of %2$s.', 'query-monitor' ),
						'<code>' . esc_html( $referenced_type ) . '</code>',
						'<code>attachment</code>'
					);
				}
			}
		}

		$template_part_blocks = array(
			'core/template-part' => true,
		);

		if ( isset( $template_part_blocks[ $block['blockName'] ] ) && is_array( $block['attrs'] ) && ! empty( $block['attrs']['slug'] ) && ! empty( $block['attrs']['theme'] ) ) {
			$referenced_template_part = sprintf(
				'%s//%s',
				$block['attrs']['theme'],
				$block['attrs']['slug']
			);
			$referenced_pto = get_post_type_object( 'wp_template_part' );
		}

		if ( $block_error ) {
			$row_class = 'qm-warn';
		}

		echo '<tr class="' . esc_attr( $row_class ) . '">';

		echo '<th scope="row" class="qm-row-num qm-num"><span class="qm-sticky">' . esc_html( $i ) . '</span></th>';

		echo '<td class="qm-row-block-name"><span class="qm-sticky">';

		if ( $block['blockName'] ) {
			echo esc_html( $block['blockName'] );
		} else {
			echo '<em>' . esc_html__( 'None (Classic block)', 'query-monitor' ) . '</em>';
		}

		if ( $error_message ) {
			echo '<br>';
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo QueryMonitor::icon( 'warning' );
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $error_message;
		}

		if ( ! empty( $referenced_post ) && ! empty( $referenced_pto ) ) {
			echo '<br>';
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo self::build_link( get_edit_post_link( $referenced_post ), esc_html( $referenced_pto->labels->edit_item ) );
		}

		if ( ! empty( $referenced_template_part ) ) {
			echo '<br>';
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo self::build_link( QM_Util::get_site_editor_url( $referenced_template_part ), esc_html( $referenced_pto->labels->edit_item ) );
		}

		echo '</span></td>';

		echo '<td class="qm-row-block-attrs">';
		if ( $block['attrs'] && is_array( $block['attrs'] ) ) {
			echo '<pre class="qm-pre-wrap"><code>' . esc_html( QM_Util::json_format( $block['attrs'] ) ) . '</code></pre>';
		}
		echo '</td>';

		if ( $data->has_block_context ) {
			echo '<td class="qm-row-block-context">';
			if ( isset( $block['context'] ) ) {
				echo '<pre class="qm-pre-wrap"><code>' . esc_html( QM_Util::json_format( $block['context'] ) ) . '</code></pre>';
			}
			echo '</td>';
		}

		if ( isset( $block['callback']['error'] ) ) {
			$class = ' qm-warn';
		} else {
			$class = '';
		}

		if ( $block['dynamic'] ) {
			if ( isset( $block['callback']['file'] ) ) {
				if ( self::has_clickable_links() ) {
					echo '<td class="qm-nowrap qm-ltr' . esc_attr( $class ) . '">';
					echo self::output_filename( $block['callback']['name'], $block['callback']['file'], $block['callback']['line'] ); // WPCS: XSS ok.
					echo '</td>';
				} else {
					echo '<td class="qm-nowrap qm-ltr qm-has-toggle' . esc_attr( $class ) . '">';
					echo self::build_toggler(); // WPCS: XSS ok;
					echo '<ol>';
					echo '<li>';
					echo self::output_filename( $block['callback']['name'], $block['callback']['file'], $block['callback']['line'] ); // WPCS: XSS ok.
					echo '</li>';
					echo '</ol></td>';
				}
			} else {
				echo '<td class="qm-ltr qm-nowrap' . esc_attr( $class ) . '">';
				echo '<code>' . esc_html( $block['callback']['name'] ) . '</code>';

				if ( isset( $block['callback']['error'] ) ) {
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo '<br>' . QueryMonitor::icon( 'warning' );
					echo esc_html( sprintf(
						/* translators: %s: Error message text */
						__( 'Error: %s', 'query-monitor' ),
						$block['callback']['error']->get_error_message()
					) );
				}

				echo '</td>';
			}

			if ( $data->has_block_timing ) {
				echo '<td class="qm-num">';
				if ( isset( $block['timing'] ) ) {
					echo esc_html( number_format_i18n( $block['timing'], 4 ) );
				}
				echo '</td>';
			}
		} else {
			echo '<td></td>';

			if ( $data->has_block_timing ) {
				echo '<td></td>';
			}
		}

		$inner_html = $block['innerHTML'];

		if ( $block['size'] > 300 ) {
			echo '<td class="qm-ltr qm-has-toggle qm-row-block-html">';
			echo self::build_toggler(); // WPCS: XSS ok;
			echo '<div class="qm-inverse-toggled"><pre class="qm-pre-wrap"><code>';
			echo esc_html( substr( $inner_html, 0, 200 ) ) . '&nbsp;&hellip;';
			echo '</code></pre></div>';
			echo '<div class="qm-toggled"><pre class="qm-pre-wrap"><code>';
			echo esc_html( $inner_html );
			echo '</code></pre></div>';
			echo '</td>';
		} else {
			echo '<td class="qm-row-block-html"><pre class="qm-pre-wrap"><code>';
			echo esc_html( $inner_html );
			echo '</code></pre></td>';
		}

		echo '</tr>';

		if ( ! empty( $block['innerBlocks'] ) ) {
			foreach ( $block['innerBlocks'] as $j => $inner_block ) {
				$x = ++$j;
				self::render_block( "{$i}.{$x}", $inner_block, $data );
			}
		}
	}

	/**
	 * @param array<string, mixed[]> $menu
	 * @return array<string, mixed[]>
	 */
	public function admin_menu( array $menu ) {
		/** @var QM_Data_Block_Editor */
		$data = $this->collector->get_data();

		if ( empty( $data->block_editor_enabled ) || empty( $data->post_blocks ) ) {
			return $menu;
		}

		$menu[ $this->collector->id() ] = $this->menu( array(
			'title' => esc_html__( 'Blocks', 'query-monitor' ),
		) );

		return $menu;
	}

}

/**
 * @param array<string, QM_Output> $output
 * @param QM_Collectors $collectors
 * @return array<string, QM_Output>
 */
function register_qm_output_html_block_editor( array $output, QM_Collectors $collectors ) {
	$collector = QM_Collectors::get( 'block_editor' );
	if ( $collector ) {
		$output['block_editor'] = new QM_Output_Html_Block_Editor( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_block_editor', 60, 2 );
