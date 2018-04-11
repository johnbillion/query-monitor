<?php
/**
 * 'Debug Bar' output for HTML pages.
 *
 * @package query-monitor
 */

class QM_Output_Html_Debug_Bar extends QM_Output_Html {

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 200 );
	}

	public function output() {

		$target = get_class( $this->collector->get_panel() );

		echo '<div class="qm qm-debug-bar" id="' . esc_attr( $this->collector->id() ) . '">';
		echo '<div id="debug-menu-target-' . esc_attr( $target ) . '" class="debug-menu-target qm-debug-bar-output">';

		$this->collector->render();

		echo '</div>';
		echo '</div>';

	}

}

function register_qm_output_html_debug_bar( array $output, QM_Collectors $collectors ) {
	global $debug_bar;

	if ( empty( $debug_bar ) ) {
		return $output;
	}

	foreach ( $debug_bar->panels as $panel ) {
		$panel_id  = strtolower( get_class( $panel ) );
		$collector = QM_Collectors::get( "debug_bar_{$panel_id}" );

		if ( $collector and $collector->is_visible() ) {
			$output[ "debug_bar_{$panel_id}" ] = new QM_Output_Html_Debug_Bar( $collector );
		}
	}

	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_debug_bar', 200, 2 );
