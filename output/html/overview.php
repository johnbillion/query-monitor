<?php declare(strict_types = 1);
/**
 * General overview output for HTML pages.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QM_Output_Html_Overview extends QM_Output_Html {

	/**
	 * Collector instance.
	 *
	 * @var QM_Collector_Overview Collector.
	 */
	protected $collector;

	/**
	 * @var bool
	 */
	public static $client_side_rendered = true;

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/title', array( $this, 'admin_title' ), 10 );
		add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 0 );
	}

	/**
	 * @return string
	 */
	public function name() {
		return __( 'Overview', 'query-monitor' );
	}

	/**
	 * @param array<int, string> $title
	 * @return array<int, string>
	 */
	public function admin_title( array $title ) {
		/** @var QM_Data_Overview $data */
		$data = $this->collector->get_data();

		if ( empty( $data->memory ) ) {
			$memory = '??';
		} else {
			$memory = number_format_i18n( ( $data->memory / 1024 / 1024 ), 1 );
		}

		$title[] = sprintf(
			/* translators: %s: A time in seconds with a decimal fraction. No space between value and unit symbol. */
			esc_html_x( '%ss', 'Time in seconds', 'query-monitor' ),
			number_format_i18n( $data->time_taken, 2 )
		);
		$title[] = preg_replace( '#\s?([^0-9,\.]+)#', '<small>$1</small>', sprintf(
			/* translators: %s: Memory usage in megabytes with a decimal fraction. Note the space between value and unit symbol. */
			esc_html__( '%s MB', 'query-monitor' ),
			$memory
		) );

		return $title;
	}

	/**
	 * @param array<string, mixed[]> $menu
	 * @return array<string, mixed[]>
	 */
	public function admin_menu( array $menu ) {
		$args = array(
			'title' => __( 'Timeline', 'query-monitor' ),
			'id' => 'timeline',
			'panel' => 'timeline',
			'new' => true,
		);

		$menu['timeline'] = $this->menu( $args );

		return $menu;

	}
}

/**
 * @param array<string, QM_Output> $output
 * @param QM_Collectors $collectors
 * @return array<string, QM_Output>
 */
function register_qm_output_html_overview( array $output, QM_Collectors $collectors ) {
	$collector = QM_Collectors::get( 'overview' );
	if ( $collector ) {
		$output['overview'] = new QM_Output_Html_Overview( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_overview', 10, 2 );
