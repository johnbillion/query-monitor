<?php declare(strict_types = 1);
/**
 * Multisite output for HTML pages.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QM_Output_Html_Multisite extends QM_Output_Html {

	/**
	 * Collector instance.
	 *
	 * @var QM_Collector_Multisite Collector.
	 */
	protected $collector;

	public static $client_side_rendered = true;

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 55 );
	}

	public function name() {
		return __( 'Multisite', 'query-monitor' );
	}
}

/**
 * @param array<string, QM_Output> $output
 * @param QM_Collectors $collectors
 * @return array<string, QM_Output>
 */
function register_qm_output_html_multisite( array $output, QM_Collectors $collectors ) {
	$collector = is_multisite() ? QM_Collectors::get( 'multisite' ) : null;

	if ( $collector ) {
		$output['multisite'] = new QM_Output_Html_Multisite( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_multisite', 65, 2 );
