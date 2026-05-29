<?php declare(strict_types = 1);
/**
 * Request data output for HTML pages.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QM_Output_Html_Request extends QM_Output_Html {

	/**
	 * Collector instance.
	 *
	 * @var QM_Collector_Request Collector.
	 */
	protected $collector;

	/**
	 * @var bool
	 */
	public static $client_side_rendered = true;

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 50 );
	}

	/**
	 * @return string
	 */
	public function name() {
		return __( 'Request', 'query-monitor' );
	}
}

/**
 * @param array<string, QM_Output> $output
 * @param QM_Collectors $collectors
 * @return array<string, QM_Output>
 */
function register_qm_output_html_request( array $output, QM_Collectors $collectors ) {
	$collector = QM_Collectors::get( 'request' );
	if ( $collector ) {
		$output['request'] = new QM_Output_Html_Request( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_request', 60, 2 );
