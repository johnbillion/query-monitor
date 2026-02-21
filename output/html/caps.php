<?php declare(strict_types = 1);
/**
 * User capability checks output for HTML pages.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QM_Output_Html_Caps extends QM_Output_Html {

	/**
	 * Collector instance.
	 *
	 * @var QM_Collector_Caps Collector.
	 */
	protected $collector;

	/**
	 * @var bool
	 */
	public static $client_side_rendered = true;

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 105 );
	}

	/**
	 * @return string
	 */
	public function name() {
		return __( 'Capability Checks', 'query-monitor' );
	}

	/**
	 * @param array<string, mixed[]> $menu
	 * @return array<string, mixed[]>
	 */
	public function admin_menu( array $menu ) {
		$menu[ $this->collector->id() ] = $this->menu( array(
			'title' => $this->name(),
		) );
		return $menu;

	}

}

/**
 * @param array<string, QM_Output> $output
 * @param QM_Collectors $collectors
 * @return array<string, QM_Output>
 */
function register_qm_output_html_caps( array $output, QM_Collectors $collectors ) {
	$collector = QM_Collectors::get( 'caps' );
	if ( $collector ) {
		$output['caps'] = new QM_Output_Html_Caps( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_caps', 105, 2 );
