<?php
/**
 * Template and theme output for HTML pages.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QM_Output_Html_Theme extends QM_Output_Html {

	/**
	 * Collector instance.
	 *
	 * @var QM_Collector_Theme Collector.
	 */
	protected $collector;

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 60 );
		add_filter( 'qm/output/panel_menus', array( $this, 'panel_menu' ), 60 );
	}

	/**
	 * @return string
	 */
	public function name() {
		return __( 'Theme', 'query-monitor' );
	}

	/**
	 * @return void
	 */
	public function output() {
		$data = $this->collector->get_data();

		if ( empty( $data['stylesheet'] ) ) {
			return;
		}

		$this->before_non_tabular_output();

		echo '<section>';
		echo '<h3>' . esc_html__( 'Theme', 'query-monitor' ) . '</h3>';
		echo '<p>' . esc_html( $data['stylesheet'] ) . '</p>';

		if ( $data['is_child_theme'] ) {
			echo '<h3>' . esc_html__( 'Parent Theme', 'query-monitor' ) . '</h3>';
			echo '<p>' . esc_html( $data['template'] ) . '</p>';
		}

		echo '</section>';

		echo '<section>';
		echo '<h3>' . esc_html__( 'Template File', 'query-monitor' ) . '</h3>';

		if ( ! empty( $data['template_path'] ) ) {
			if ( $data['is_child_theme'] ) {
				$display = $data['theme_template_file'];
			} else {
				$display = $data['template_file'];
			}
			if ( self::has_clickable_links() ) {
				$file = $data['template_path'];
			} else {
				$file = false;
			}
			echo '<p class="qm-ltr">' . self::output_filename( $display, $file, 0, true ) . '</p>'; // WPCS: XSS ok.
		} else {
			echo '<p><em>' . esc_html__( 'Unknown', 'query-monitor' ) . '</em></p>';
		}

		if ( ! empty( $data['template_hierarchy'] ) ) {
			echo '<h3>' . esc_html__( 'Template Hierarchy', 'query-monitor' ) . '</h3>';
			echo '<ol class="qm-ltr"><li>' . implode( '</li><li>', array_map( 'esc_html', $data['template_hierarchy'] ) ) . '</li></ol>';
		}

		echo '</section>';

		echo '<section>';
		echo '<h3>' . esc_html__( 'Template Parts', 'query-monitor' ) . '</h3>';

		if ( ! empty( $data['template_parts'] ) ) {

			if ( $data['is_child_theme'] ) {
				$parts = $data['theme_template_parts'];
			} else {
				$parts = $data['template_parts'];
			}

			echo '<ul class="qm-ltr">';

			foreach ( $parts as $filename => $display ) {
				echo '<li>';

				if ( is_numeric( $filename ) ) {
					printf(
						'<a href="%1$s">%2$s</a>',
						esc_url( get_edit_post_link( $filename ) ),
						esc_html( $display )
					);
				} elseif ( self::has_clickable_links() ) {
					echo self::output_filename( $display, $filename, 0, true ); // WPCS: XSS ok.
				} else {
					echo esc_html( $display );
				}

				if ( $data['count_template_parts'][ $filename ] > 1 ) {
					$count = sprintf(
						/* translators: %s: The number of times that a template part file was included in the page */
						_nx( 'Included %s time', 'Included %s times', $data['count_template_parts'][ $filename ], 'template parts', 'query-monitor' ),
						esc_html( number_format_i18n( $data['count_template_parts'][ $filename ] ) )
					);
					echo '<br><span class="qm-info qm-supplemental">' . esc_html( $count ) . '</span>';
				}
				echo '</li>';
			}

			echo '</ul>';

		} else {
			echo '<p><em>' . esc_html__( 'None', 'query-monitor' ) . '</em></p>';
		}

		if ( $data['has_template_part_action'] ) {
			echo '<h4>' . esc_html__( 'Not Loaded', 'query-monitor' ) . '</h4>';

			if ( ! empty( $data['unsuccessful_template_parts'] ) ) {
				echo '<ul>';

				foreach ( $data['unsuccessful_template_parts'] as $requested ) {
					if ( $requested['name'] ) {
						echo '<li>';
						$text = $requested['slug'] . '-' . $requested['name'] . '.php';
						echo self::output_filename( $text, $requested['caller']['file'], $requested['caller']['line'], true ); // WPCS: XSS ok.
						echo '</li>';
					}

					echo '<li>';
					$text = $requested['slug'] . '.php';
					echo self::output_filename( $text, $requested['caller']['file'], $requested['caller']['line'], true ); // WPCS: XSS ok.
					echo '</li>';
				}

				echo '</ul>';
			} else {
				echo '<p><em>' . esc_html__( 'None', 'query-monitor' ) . '</em></p>';
			}
		}

		echo '</section>';

		if ( ! empty( $data['timber_files'] ) ) {
			echo '<section>';
			echo '<h3>' . esc_html__( 'Twig Template Files', 'query-monitor' ) . '</h3>';
			echo '<ul class="qm-ltr">';

			foreach ( $data['timber_files'] as $filename ) {
				echo '<li>' . esc_html( $filename ) . '</li>';
			}

			echo '</ul>';
			echo '</section>';
		}

		if ( ! empty( $data['body_class'] ) ) {
			echo '<section>';

			echo '<h3>' . esc_html__( 'Body Classes', 'query-monitor' ) . '</h3>';
			echo '<ul class="qm-ltr">';

			foreach ( $data['body_class'] as $class ) {
				echo '<li>' . esc_html( $class ) . '</li>';
			}

			echo '</ul>';
			echo '</section>';
		}

		$this->after_non_tabular_output();
	}

	/**
	 * @param array<string, mixed[]> $menu
	 * @return array<string, mixed[]>
	 */
	public function admin_menu( array $menu ) {

		$data = $this->collector->get_data();

		if ( isset( $data['template_file'] ) ) {
			$name = ( $data['is_child_theme'] ) ? $data['theme_template_file'] : $data['template_file'];
		} else {
			$name = __( 'Unknown', 'query-monitor' );
		}

		$menu[ $this->collector->id() ] = $this->menu( array(
			'title' => esc_html( sprintf(
				/* translators: %s: Template file name */
				__( 'Template: %s', 'query-monitor' ),
				$name
			) ),
		) );

		return $menu;

	}

	/**
	 * @param array<string, mixed[]> $menu
	 * @return array<string, mixed[]>
	 */
	public function panel_menu( array $menu ) {
		if ( isset( $menu[ $this->collector->id() ] ) ) {
			$menu[ $this->collector->id() ]['title'] = __( 'Template', 'query-monitor' );
		}

		return $menu;
	}

}

/**
 * @param array<string, QM_Output> $output
 * @param QM_Collectors $collectors
 * @return array<string, QM_Output>
 */
function register_qm_output_html_theme( array $output, QM_Collectors $collectors ) {
	if ( is_admin() ) {
		return $output;
	}
	$collector = QM_Collectors::get( 'response' );
	if ( $collector ) {
		$output['response'] = new QM_Output_Html_Theme( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_theme', 70, 2 );
