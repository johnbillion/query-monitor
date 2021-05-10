<?php
/**
 * 'Debug Bar' output for HTML pages.
 *
 * @package query-monitor
 */

defined( 'ABSPATH' ) || exit;

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

	public function name() {
		$title = $this->collector->get_panel()->title();

		return sprintf(
			/* translators: Debug Bar add-on name */
			__( 'Debug Bar: %s', 'query-monitor' ),
			$title
		);
	}

	public function output() {
		$target = sanitize_html_class( get_class( $this->collector->get_panel() ) );

		$this->before_debug_bar_output();

		echo '<div id="debug-menu-target-' . esc_attr( $target ) . '" class="debug-menu-target qm-debug-bar-output">';

		ob_start();
		$this->collector->render();
		$panel = ob_get_clean();

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

function register_qm_output_html_debug_bar( array $output, QM_Collectors $collectors ) {
	global $debug_bar;

	if ( empty( $debug_bar ) ) {
		return $output;
	}

	foreach ( $debug_bar->panels as $panel ) {
		$panel_id  = strtolower( sanitize_html_class( get_class( $panel ) ) );
		$collector = QM_Collectors::get( "debug_bar_{$panel_id}" );

		if ( $collector && $collector->is_visible() ) {
			$output[ "debug_bar_{$panel_id}" ] = new QM_Output_Html_Debug_Bar( $collector );
		}
	}

	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_debug_bar', 200, 2 );
