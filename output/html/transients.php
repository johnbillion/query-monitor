<?php declare(strict_types = 1);
/**
 * Transient storage output for HTML pages.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QM_Output_Html_Transients extends QM_Output_Html {

	/**
	 * Collector instance.
	 *
	 * @var QM_Collector_Transients Collector.
	 */
	protected $collector;

	/**
	 * @var bool
	 */
	public static $client_side_rendered = true;

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 100 );
	}

	/**
	 * @return string
	 */
	public function name() {
		return __( 'Transients', 'query-monitor' );
	}

	/**
	 * @param array<string, mixed[]> $menu
	 * @return array<string, mixed[]>
	 */
	public function admin_menu( array $menu ) {
		/** @var QM_Data_Transients $data */
		$data = $this->collector->get_data();
		$count = count( $data->trans );

		$title = ( empty( $count ) )
			? __( 'Transient Updates', 'query-monitor' )
			/* translators: %s: Number of transient values that were updated */
			: __( 'Transient Updates (%s)', 'query-monitor' );

		$menu[ $this->collector->id() ] = $this->menu( array(
			'title' => sprintf(
				$title,
				number_format_i18n( $count )
			),
		) );
		return $menu;

	}

}

/**
 * @param array<string, QM_Output> $output
 * @param QM_Collectors $collectors
 * @return array<string, QM_Output>
 */
function register_qm_output_html_transients( array $output, QM_Collectors $collectors ) {
	$collector = QM_Collectors::get( 'transients' );
	if ( $collector ) {
		$output['transients'] = new QM_Output_Html_Transients( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_transients', 100, 2 );
