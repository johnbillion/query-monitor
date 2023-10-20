<?php declare(strict_types = 1);
/**
 * HTTP API request output for HTML pages.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QM_Output_Html_HTTP extends QM_Output_Html {

	/**
	 * Collector instance.
	 *
	 * @var QM_Collector_HTTP Collector.
	 */
	protected $collector;

	/**
	 * @var bool
	 */
	public static $client_side_rendered = true;

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 90 );
		add_filter( 'qm/output/menu_class', array( $this, 'admin_class' ) );
	}

	/**
	 * @return string
	 */
	public function name() {
		return __( 'HTTP API Calls', 'query-monitor' );
	}

	/**
	 * @param array<int, string> $class
	 * @return array<int, string>
	 */
	public function admin_class( array $class ) {
		/** @var QM_Data_HTTP $data */
		$data = $this->collector->get_data();

		if ( isset( $data->errors['alert'] ) ) {
			$class[] = 'qm-alert';
		}
		if ( isset( $data->errors['warning'] ) ) {
			$class[] = 'qm-warning';
		}

		return $class;

	}

	/**
	 * @param array<string, mixed[]> $menu
	 * @return array<string, mixed[]>
	 */
	public function admin_menu( array $menu ) {
		/** @var QM_Data_HTTP $data */
		$data = $this->collector->get_data();

		$count = ! empty( $data->http ) ? count( $data->http ) : 0;

		$title = ( empty( $count ) )
			? __( 'HTTP API Calls', 'query-monitor' )
			/* translators: %s: Number of calls to the HTTP API */
			: __( 'HTTP API Calls (%s)', 'query-monitor' );

		$args = array(
			'title' => sprintf(
				$title,
				number_format_i18n( $count )
			),
		);

		if ( isset( $data->errors['alert'] ) ) {
			$args['meta']['classname'] = 'qm-alert';
		}
		if ( isset( $data->errors['warning'] ) ) {
			$args['meta']['classname'] = 'qm-warning';
		}

		$menu[ $this->collector->id() ] = $this->menu( $args );

		return $menu;

	}

}

/**
 * @param array<string, QM_Output> $output
 * @param QM_Collectors $collectors
 * @return array<string, QM_Output>
 */
function register_qm_output_html_http( array $output, QM_Collectors $collectors ) {
	$collector = QM_Collectors::get( 'http' );
	if ( $collector ) {
		$output['http'] = new QM_Output_Html_HTTP( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_http', 90, 2 );
