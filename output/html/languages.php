<?php declare(strict_types = 1);
/**
 * Language and locale output for HTML pages.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QM_Output_Html_Languages extends QM_Output_Html {

	/**
	 * Collector instance.
	 *
	 * @var QM_Collector_Languages Collector.
	 */
	protected $collector;

	/**
	 * @var bool
	 */
	public static $client_side_rendered = true;

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 80 );
	}

	/**
	 * @return string
	 */
	public function name() {
		return __( 'Languages', 'query-monitor' );
	}

	/**
	 * @param array<string, mixed[]> $menu
	 * @return array<string, mixed[]>
	 */
	public function admin_menu( array $menu ) {
		$args = array(
			'title' => $this->name(),
		);

		$menu[ $this->collector->id() ] = $this->menu( $args );

		return $menu;

	}

}

/**
 * @param array<string, QM_Output> $output
 * @param QM_Collectors $collectors
 * @return array<string, QM_Output>
 */
function register_qm_output_html_languages( array $output, QM_Collectors $collectors ) {
	$collector = QM_Collectors::get( 'languages' );
	if ( $collector ) {
		$output['languages'] = new QM_Output_Html_Languages( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_languages', 81, 2 );
