<?php
/**
 * Template conditionals output for HTML pages.
 *
 * @package query-monitor
 */

class QM_Output_Html_Conditionals extends QM_Output_Html {

	/**
	 * Collector instance.
	 *
	 * @var QM_Collector_Conditionals Collector.
	 */
	protected $collector;

	public static $client_side_rendered = true;

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 1000 );
		add_filter( 'qm/output/panel_menus', array( $this, 'panel_menu' ), 1000 );
	}

	public function name() {
		return __( 'Conditionals', 'query-monitor' );
	}

	public function admin_menu( array $menu ) {

		$data = $this->collector->get_data();

		foreach ( $data['conds']['true'] as $cond ) {
			$id          = $this->collector->id() . '-' . $cond;
			$menu[ $id ] = $this->menu( array(
				'title' => $cond . '()',
				'id'    => 'query-monitor-conditionals-' . $cond,
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
			$id = $this->collector->id() . '-' . $cond;
			unset( $menu[ $id ] );
		}

		$menu[ $this->collector->id() ] = $this->menu( array(
			'title' => esc_html__( 'Conditionals', 'query-monitor' ),
		) );

		return $menu;

	}


}

function register_qm_output_html_conditionals( array $output, QM_Collectors $collectors ) {
	$collector = QM_Collectors::get( 'conditionals' );
	if ( $collector ) {
		$output['conditionals'] = new QM_Output_Html_Conditionals( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_conditionals', 50, 2 );
