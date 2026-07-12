<?php declare(strict_types = 1);
/**
 * Doing it Wrong output for HTML pages.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QM_Output_Html_Doing_It_Wrong extends QM_Output_Html {

	/**
	 * Collector instance.
	 *
	 * @var QM_Collector_Doing_It_Wrong Collector.
	 */
	protected $collector;

	public static $client_side_rendered = true;

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 15 );
	}

	/**
	 * @return string
	 */
	public function name() {
		return __( 'Doing it Wrong', 'query-monitor' );
	}

	/**
	 * @param array<string, mixed[]> $menu
	 * @return array<string, mixed[]>
	 */
	public function admin_menu( array $menu ) {
		/** @var QM_Data_Doing_It_Wrong $data */
		$data = $this->collector->get_data();

		if ( empty( $data->actions ) ) {
			return $menu;
		}

		$menu[ $this->collector->id() ] = $this->menu( array(
			'title' => __( 'Doing it Wrong', 'query-monitor' ),
			'notice_count' => count( $data->actions ),
		) );

		return $menu;
	}

}

/**
 * @param array<string, QM_Output> $output
 * @param QM_Collectors $collectors
 * @return array<string, QM_Output>
 */
function register_qm_output_html_doing_it_wrong( array $output, QM_Collectors $collectors ) {
	$collector = QM_Collectors::get( 'doing_it_wrong' );
	if ( $collector ) {
		$output['doing_it_wrong'] = new QM_Output_Html_Doing_It_Wrong( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_doing_it_wrong', 110, 2 );
