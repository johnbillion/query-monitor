<?php
/**
 * Block editor data output for HTML pages.
 *
 * @package query-monitor
 */

class QM_Output_Html_Block_Editor extends QM_Output_Html {

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 55 );
	}

	public function output() {
		$data = $this->collector->get_data();
		$i    = 0;

		if ( empty( $data['block_editor_enabled'] ) || empty( $data['post_blocks'] ) ) {
			return;
		}

		if ( ! $data['post_has_blocks'] ) {
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
		echo '<th scope="col">' . esc_html__( 'Render Callback', 'query-monitor' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'Inner HTML', 'query-monitor' ) . '</th>';
		echo '</tr>';
		echo '</thead>';

		echo '<tbody>';

		foreach ( $data['post_blocks'] as $block ) {
			$inner_html = trim( $block['innerHTML'] );

			// Don't display empty blocks caused by two consecutive line breaks in content
			if ( ! $block['blockName'] && ! $inner_html ) {
				continue;
			}

			$block_error   = ( empty( $block['blockName'] ) && ! empty( $inner_html ) );
			$row_class     = '';
			$reused_post   = null;
			$reused_type   = null;
			$reused_pto    = null;
			$error_message = null;

			if ( 'core/block' === $block['blockName'] && ! empty( $block['attrs']['ref'] ) ) {
				$reused_post = get_post( $block['attrs']['ref'] );

				if ( ! $reused_post ) {
					$block_error   = true;
					$error_message = esc_html__( 'Referenced block does not exist.', 'query-monitor' );
				} else {
					$reused_type = get_post( $block['attrs']['ref'] )->post_type;
					$reused_pto  = get_post_type_object( $reused_type );
					if ( 'wp_block' !== $reused_type ) {
						$block_error   = true;
						$error_message = sprintf(
							/* translators: %1$s: Erroneous post type name, %2$s: WordPress block post type name */
							esc_html__( 'Referenced post is of type %1$s instead of %2$s.', 'query-monitor' ),
							'<code>' . esc_html( $reused_type ) . '</code>',
							'<code>wp_block</code>'
						);
					}
				}
			}

			if ( $block_error ) {
				$row_class = 'qm-warn';
			}

			echo '<tr class="' . esc_attr( $row_class ) . '">';

			echo '<th scope="row" class="qm-row-num qm-num"><span class="qm-sticky">' . absint( ++$i ) . '</span></th>';

			echo '<td class="qm-row-block-name"><span class="qm-sticky">';

			if ( $block_error ) {
				echo '<span class="dashicons dashicons-warning" aria-hidden="true"></span>';
			}

			if ( $block['blockName'] ) {
				echo esc_html( $block['blockName'] );
			} else {
				echo '<em>' . esc_html__( 'None', 'query-monitor' ) . '</em>';
			}

			if ( $error_message ) {
				echo '<br>';
				echo $error_message; // WPCS: XSS ok;
			}

			if ( 'core/block' === $block['blockName'] && ! empty( $block['attrs']['ref'] ) && ! empty( $reused_pto ) ) {
				echo '<br>';
				echo '<a href="' . esc_url( get_edit_post_link( $block['attrs']['ref'] ) ) . '" class="qm-link">' . esc_html( $reused_pto->labels->edit_item ) . '</a>';
			}

			echo '</span></td>';

			echo '<td class="qm-row-block-attrs">';
			if ( $block['attrs'] ) {
				$json = json_encode( $block['attrs'], JSON_PRETTY_PRINT );
				echo '<pre>' . esc_html( $json ) . '</pre>';
			}
			echo '</td>';

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
						echo '<td class="qm-nowrap qm-ltr qm-has-toggle' . esc_attr( $class ) . '"><ol class="qm-toggler">';
						echo self::build_toggler(); // WPCS: XSS ok;
						echo '<li>';
						echo self::output_filename( $block['callback']['name'], $block['callback']['file'], $block['callback']['line'] ); // WPCS: XSS ok.
						echo '</li>';
						echo '</ol></td>';
					}
				} else {
					echo '<td class="qm-ltr qm-nowrap' . esc_attr( $class ) . '">';
					echo '<code>' . esc_html( $block['callback']['name'] ) . '</code>';

					if ( isset( $block['callback']['error'] ) ) {
						echo '<br>';
						echo esc_html( sprintf(
							/* translators: %s: Error message text */
							__( 'Error: %s', 'query-monitor' ),
							$block['callback']['error']->get_error_message()
						) );
					}

					echo '</td>';
				}
			} else {
				echo '<td></td>';
			}

			echo '<td class="qm-row-block-html">';
			if ( $block['innerHTML'] ) {
				echo esc_html( $block['innerHTML'] );
			}
			echo '</td>';

			echo '</tr>';
		}

		echo '</tbody>';

		$this->after_tabular_output();
	}

	public function admin_menu( array $menu ) {
		$data = $this->collector->get_data();

		if ( empty( $data['block_editor_enabled'] ) || empty( $data['post_blocks'] ) ) {
			return $menu;
		}

		$menu[] = $this->menu( array(
			'title' => esc_html__( 'Blocks', 'query-monitor' ),
		) );

		return $menu;
	}

}

function register_qm_output_html_block_editor( array $output, QM_Collectors $collectors ) {
	if ( $collector = QM_Collectors::get( 'block_editor' ) ) {
		$output['block_editor'] = new QM_Output_Html_Block_Editor( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_block_editor', 60, 2 );
