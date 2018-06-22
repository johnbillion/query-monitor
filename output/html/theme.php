<?php
/**
 * Template and theme output for HTML pages.
 *
 * @package query-monitor
 */

class QM_Output_Html_Theme extends QM_Output_Html {

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 60 );
		add_filter( 'qm/output/panel_menus', array( $this, 'panel_menu' ), 60 );
	}

	public function output() {
		$data = $this->collector->get_data();

		if ( empty( $data['stylesheet'] ) ) {
			return;
		}

		$this->before_non_tabular_output();

		echo '<div class="qm-section">';
		echo '<h3>' . esc_html__( 'Template File', 'query-monitor' ) . '</h3>';

		if ( ! empty( $data['template_path'] ) ) {
			if ( $data['is_child_theme'] ) {
				echo '<p class="qm-ltr">' . self::output_filename( $data['theme_template_file'], $data['template_path'], 0, true ) . '</p>'; // WPCS: XSS ok.
			} else {
				echo '<p class="qm-ltr">' . self::output_filename( $data['template_file'], $data['template_path'], 0, true ) . '</p>'; // WPCS: XSS ok.
			}
		} else {
			echo '<p><em>' . esc_html__( 'Unknown', 'query-monitor' ) . '</em></p>';
		}

		echo '</div>';

		if ( ! empty( $data['template_hierarchy'] ) ) {
			echo '<div class="qm-section">';
			echo '<h3>' . esc_html__( 'Template Hierarchy', 'query-monitor' ) . '</h3>';
			echo '<ol class="qm-ltr qm-numbered"><li>' . implode( '</li><li>', array_map( 'esc_html', $data['template_hierarchy'] ) ) . '</li></ol>';
			echo '</div>';
		}

		echo '<div class="qm-section">';
		echo '<h3>' . esc_html__( 'Template Parts', 'query-monitor' ) . '</h3>';

		if ( ! empty( $data['template_parts'] ) ) {

			if ( $data['is_child_theme'] ) {
				$parts = $data['theme_template_parts'];
			} else {
				$parts = $data['template_parts'];
			}

			echo '<ul class="qm-ltr">';

			foreach ( $parts as $filename => $display ) {
				echo '<li>' . self::output_filename( $display, $filename, 0, true ) . '</li>'; // WPCS: XSS ok.
			}

			echo '</ul>';

		} else {
			echo '<p><em>' . esc_html__( 'None', 'query-monitor' ) . '</em></p>';
		}

		echo '</div>';

		if ( ! empty( $data['timber_files'] ) ) {
			echo '<div class="qm-section">';
			echo '<h3>' . esc_html__( 'Timber Files', 'query-monitor' ) . '</h3>';
			echo '<ul class="qm-ltr">';

			foreach ( $data['timber_files'] as $filename ) {
				echo '<li>' . esc_html( $filename ) . '</li>';
			}

			echo '</ul>';
			echo '</div>';
		}

		echo '<div class="qm-section">';
		echo '<h3>' . esc_html__( 'Theme', 'query-monitor' ) . '</h3>';
		echo '<p>' . esc_html( $data['stylesheet'] ) . '</p>';

		if ( $data['is_child_theme'] ) {
			echo '<h3>' . esc_html__( 'Parent Theme:', 'query-monitor' ) . '</h3>';
			echo '<p>' . esc_html( $data['template'] ) . '</p>';
		}

		echo '</div>';

		if ( ! empty( $data['body_class'] ) ) {
			echo '<div class="qm-section">';

			echo '<h3>' . esc_html__( 'Body Classes', 'query-monitor' ) . '</h3>';
			echo '<ul class="qm-ltr">';

			foreach ( $data['body_class'] as $class ) {
				echo '<li>' . esc_html( $class ) . '</li>';
			}

			echo '</ul>';
			echo '</div>';
		}

		$this->after_non_tabular_output();
	}

	public function admin_menu( array $menu ) {

		$data = $this->collector->get_data();

		if ( isset( $data['template_file'] ) ) {
			$menu['theme'] = $this->menu( array(
				'title' => esc_html( sprintf(
					/* translators: %s: Template file name */
					__( 'Template: %s', 'query-monitor' ),
					( $data['is_child_theme'] ? $data['theme_template_file'] : $data['template_file'] )
				) ),
			) );
		}
		return $menu;

	}

	public function panel_menu( array $menu ) {
		if ( isset( $menu['theme'] ) ) {
			$menu['theme']['title'] = __( 'Template', 'query-monitor' );
		}

		return $menu;
	}

}

function register_qm_output_html_theme( array $output, QM_Collectors $collectors ) {
	if ( ! is_admin() && $collector = QM_Collectors::get( 'response' ) ) {
		$output['response'] = new QM_Output_Html_Theme( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_theme', 70, 2 );
