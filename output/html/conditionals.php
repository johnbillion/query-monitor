<?php declare(strict_types = 1);
/**
 * Template conditionals output for HTML pages.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QM_Output_Html_Conditionals extends QM_Output_Html {

	/**
	 * Collector instance.
	 *
	 * @var QM_Collector_Conditionals Collector.
	 */
	protected $collector;

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 1000 );
		add_filter( 'qm/output/panel_menus', array( $this, 'panel_menu' ), 1000 );
	}

	/**
	 * @return string
	 */
	public function name() {
		return __( 'Conditionals', 'query-monitor' );
	}

	/**
	 * @return void
	 */
	public function output() {
		/** @var QM_Data_Conditionals $data */
		$data = $this->collector->get_data();

		$this->before_non_tabular_output();

		echo '<section>';
		echo '<h3>' . esc_html__( 'True Conditionals', 'query-monitor' ) . '</h3>';

		echo '<ul>';
		foreach ( $data->conds['true'] as $cond ) {
			echo '<li class="qm-ltr qm-true"><code>' . esc_html( $cond ) . '() </code></li>';
		}
		echo '</ul>';

		echo '</section>';
		echo '</div>';

		echo '<div class="qm-boxed">';
		echo '<section>';
		echo '<h3>' . esc_html__( 'False Conditionals', 'query-monitor' ) . '</h3>';

		echo '<ul>';
		foreach ( $data->conds['false'] as $cond ) {
			echo '<li class="qm-ltr qm-false"><code>' . esc_html( $cond ) . '() </code></li>';
		}
		echo '</ul>';

		echo '</section>';

		$this->after_non_tabular_output();
	}

	/**
	 * @param array<string, mixed[]> $menu
	 * @return array<string, mixed[]>
	 */
	public function admin_menu( array $menu ) {
		/** @var QM_Data_Conditionals $data */
		$data = $this->collector->get_data();

		foreach ( $data->conds['true'] as $cond ) {
			$id = $this->collector->id() . '-' . $cond;
			$menu[ $id ] = $this->menu( array(
				'title' => esc_html( $cond . '()' ),
				'id' => 'query-monitor-conditionals-' . esc_attr( $cond ),
				'meta' => array(
					'classname' => 'qm-true qm-ltr',
				),
			) );
		}

		return $menu;

	}

	/**
	 * @param array<string, mixed[]> $menu
	 * @return array<string, mixed[]>
	 */
	public function panel_menu( array $menu ) {
		/** @var QM_Data_Conditionals $data */
		$data = $this->collector->get_data();

		foreach ( $data->conds['true'] as $cond ) {
			$id = $this->collector->id() . '-' . $cond;
			unset( $menu[ $id ] );
		}

		$menu[ $this->collector->id() ] = $this->menu( array(
			'title' => esc_html__( 'Conditionals', 'query-monitor' ),
			'id' => 'query-monitor-conditionals',
		) );

		return $menu;

	}


}

/**
 * @param array<string, QM_Output> $output
 * @param QM_Collectors $collectors
 * @return array<string, QM_Output>
 */
function register_qm_output_html_conditionals( array $output, QM_Collectors $collectors ) {
	$collector = QM_Collectors::get( 'conditionals' );
	if ( $collector ) {
		$output['conditionals'] = new QM_Output_Html_Conditionals( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_conditionals', 50, 2 );
