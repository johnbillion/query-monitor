<?php declare(strict_types = 1);
/**
 * 'Debug Bar' output for HTML pages.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QM_Output_Html_Debug_Bar extends QM_Output_Html {

	/**
	 * Collector instance.
	 *
	 * @var QM_Collector_Debug_Bar Collector.
	 */
	protected $collector;

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 200 );
	}

	/**
	 * @return string
	 */
	public function name() {
		/** @var string */
		$title = $this->collector->get_panel()->title();

		return sprintf(
			/* translators: Debug Bar add-on name */
			__( 'Debug Bar: %s', 'query-monitor' ),
			$title
		);
	}

	/**
	 * @return void
	 */
	public function output() {
		$class = get_class( $this->collector->get_panel() );

		if ( ! $class ) {
			return;
		}

		$target = sanitize_html_class( $class );

		$this->before_debug_bar_output();

		echo '<div id="debug-menu-target-' . esc_attr( $target ) . '" class="debug-menu-target qm-debug-bar-output">';

		ob_start();
		$this->collector->render();
		$panel = (string) ob_get_clean();

		$panel = str_replace( array(
			'<h4',
			'<h3',
			'<h2',
			'<h1',
			'</h4>',
			'</h3>',
			'</h2>',
			'</h1>',
		), array(
			'<h5',
			'<h4',
			'<h3',
			'<h2',
			'</h5>',
			'</h4>',
			'</h3>',
			'</h2>',
		), $panel );

		echo $panel; // phpcs:ignore

		echo '</div>';

		$this->after_debug_bar_output();
	}

}

/**
 * @param array<string, QM_Output> $output
 * @param QM_Collectors $collectors
 * @return array<string, QM_Output>
 */
function register_qm_output_html_debug_bar( array $output, QM_Collectors $collectors ) {
	global $debug_bar;

	if ( empty( $debug_bar ) ) {
		return $output;
	}

	foreach ( $debug_bar->panels as $panel ) {
		$class = get_class( $panel );

		if ( ! $class ) {
			continue;
		}

		$panel_id = strtolower( sanitize_html_class( $class ) );
		/** @var QM_Collector_Debug_Bar|null */
		$collector = QM_Collectors::get( "debug_bar_{$panel_id}" );

		if ( $collector && $collector->is_visible() ) {
			$output[ "debug_bar_{$panel_id}" ] = new QM_Output_Html_Debug_Bar( $collector );
		}
	}

	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_debug_bar', 200, 2 );
