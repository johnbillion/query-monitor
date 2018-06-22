<?php
/**
 * Template conditionals output for HTML pages.
 *
 * @package query-monitor
 */

class QM_Output_Html_Conditionals extends QM_Output_Html {

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 1000 );
		add_filter( 'qm/output/panel_menus', array( $this, 'panel_menu' ), 1000 );
	}

	public function output() {
		$data = $this->collector->get_data();

		$this->before_non_tabular_output();

		echo '<div class="qm-section">';
		echo '<h3>' . esc_html__( 'True Conditionals', 'query-monitor' ) . '</h3>';

		foreach ( $data['conds']['true'] as $cond ) {
			echo '<p class="qm-item qm-ltr qm-true"><code>' . esc_html( $cond ) . '()</code></p>';
		}

		echo '</div>';
		echo '<div class="qm-section">';
		echo '<h3>' . esc_html__( 'False Conditionals', 'query-monitor' ) . '</h3>';

		foreach ( $data['conds']['false'] as $cond ) {
			echo '<p class="qm-item qm-ltr qm-false"><code>' . esc_html( $cond ) . '()</code></p>';
		}

		echo '</div>';

		$this->after_non_tabular_output();
	}

	public function admin_menu( array $menu ) {

		$data = $this->collector->get_data();

		foreach ( $data['conds']['true'] as $cond ) {
			$menu[ "conditionals-{$cond}" ] = $this->menu( array(
				'title' => esc_html( $cond . '()' ),
				'id'    => 'query-monitor-conditionals-' . esc_attr( $cond ),
				'meta'  => array(
					'classname' => 'qm-true qm-ltr',
				),
			) );
		}

		return $menu;

	}

	public function panel_menu( array $menu ) {

		$data = $this->collector->get_data();

		foreach ( $data['conds']['true'] as $cond ) {
			unset( $menu[ "conditionals-{$cond}" ] );
		}

		$menu['conditionals'] = $this->menu( array(
			'title' => esc_html__( 'Conditionals', 'query-monitor' ),
			'id'    => 'query-monitor-conditionals',
		) );

		return $menu;

	}


}

function register_qm_output_html_conditionals( array $output, QM_Collectors $collectors ) {
	if ( $collector = QM_Collectors::get( 'conditionals' ) ) {
		$output['conditionals'] = new QM_Output_Html_Conditionals( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_conditionals', 50, 2 );
