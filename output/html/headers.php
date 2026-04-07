<?php declare(strict_types = 1);
/**
 * Request and response headers output for HTML pages.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QM_Output_Html_Headers extends QM_Output_Html {

	/**
	 * Collector instance.
	 *
	 * @var QM_Collector_Raw_Request Collector.
	 */
	protected $collector;

	/**
	 * @var bool
	 */
	public static $client_side_rendered = true;

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/panel_menus', array( $this, 'panel_menu' ), 20 );
	}

	/**
	 * Collector name.
	 *
	 * This is unused.
	 *
	 * @return string
	 */
	public function name() {
		return __( 'Request Data', 'query-monitor' );
	}

	/**
	 * @param array<string, mixed[]> $menu
	 * @return array<string, mixed[]>
	 */
	public function panel_menu( array $menu ) {
		if ( ! isset( $menu['qm-request'] ) ) {
			return $menu;
		}

		$ids = array(
			$this->collector->id => __( 'Request Headers', 'query-monitor' ),
			$this->collector->id . '-response' => __( 'Response Headers', 'query-monitor' ),
		);
		foreach ( $ids as $id => $title ) {
			$menu['qm-request']['children'][] = array(
				'id' => $id,
				'panel' => $id,
				'title' => esc_html( $title ),
			);
		}

		return $menu;
	}
}

/**
 * @param array<string, QM_Output> $output
 * @param QM_Collectors $collectors
 * @return array<string, QM_Output>
 */
function register_qm_output_html_headers( array $output, QM_Collectors $collectors ) {
	$collector = QM_Collectors::get( 'raw_request' );
	if ( $collector ) {
		$output['raw_request'] = new QM_Output_Html_Headers( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_headers', 100, 2 );
