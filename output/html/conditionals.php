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

	/**
	 * @var bool
	 */
	public static $client_side_rendered = true;

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 1000 );
	}

	/**
	 * @return string
	 */
	public function name() {
		return __( 'Conditionals', 'query-monitor' );
	}

	/**
	 * @param array<string, mixed[]> $menu
	 * @return array<string, mixed[]>
	 */
	public function admin_menu( array $menu ) {
		/** @var QM_Data_Conditionals $data */
		$data = $this->collector->get_data();

		foreach ( $data->conds['true'] as $cond ) {
			$id = 'conditionals-' . $cond;
			$menu[ $id ] = $this->menu( array(
				'id' => $id,
				'panel' => 'conditionals',
				'title' => $cond . '()',
			) );
		}

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
