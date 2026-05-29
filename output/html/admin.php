<?php declare(strict_types = 1);
/**
 * Admin screen output for HTML pages.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QM_Output_Html_Admin extends QM_Output_Html {

	/**
	 * Collector instance.
	 *
	 * @var QM_Collector_Admin Collector.
	 */
	protected $collector;

	/**
	 * @var bool
	 */
	public static $client_side_rendered = true;

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 60 );
	}

	/**
	 * @return string
	 */
	public function name() {
		return __( 'Admin Screen', 'query-monitor' );
	}
}

/**
 * @param array<string, QM_Output> $output
 * @param QM_Collectors $collectors
 * @return array<string, QM_Output>
 */
function register_qm_output_html_admin( array $output, QM_Collectors $collectors ) {
	if ( ! is_admin() ) {
		return $output;
	}
	$collector = QM_Collectors::get( 'admin' );
	if ( $collector ) {
		$output['admin'] = new QM_Output_Html_Admin( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_admin', 70, 2 );
