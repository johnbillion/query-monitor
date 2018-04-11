<?php
/**
 * Language and locale output for HTML pages.
 *
 * @package query-monitor
 */

class QM_Output_Html_Languages extends QM_Output_Html {

	public $id = 'languages';

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 80 );
	}

	public function output() {

		$data = $this->collector->get_data();

		if ( empty( $data['languages'] ) ) {
			return;
		}

		echo '<div class="qm" id="' . esc_attr( $this->collector->id() ) . '">';
		echo '<table>';
		echo '<caption>' . esc_html( sprintf(
			/* translators: %s: Name of current language */
			__( 'Language Setting: %s', 'query-monitor' ),
			$data['locale']
		) ) . '</caption>';
		echo '<thead>';
		echo '<tr>';
		echo '<th>' . esc_html__( 'Text Domain', 'query-monitor' ) . '</th>';
		echo '<th>' . esc_html__( 'Caller', 'query-monitor' ) . '</th>';
		echo '<th>' . esc_html__( 'MO File', 'query-monitor' ) . '</th>';
		echo '<th>' . esc_html__( 'Size', 'query-monitor' ) . '</th>';
		echo '</tr>';
		echo '</thead>';

		$not_found_class = ( substr( $data['locale'], 0, 3 ) === 'en_' ) ? '' : 'qm-warn';

		echo '<tbody>';

		foreach ( $data['languages'] as $textdomain => $mofiles ) {
			foreach ( $mofiles as $mofile ) {
				echo '<tr>';

				echo '<th class="qm-ltr">' . esc_html( $mofile['domain'] ) . '</th>';

				if ( self::has_clickable_links() ) {
					echo '<td class="qm-nowrap qm-ltr">';
					echo self::output_filename( $mofile['caller']['display'], $mofile['caller']['file'], $mofile['caller']['line'] ); // WPCS: XSS ok.
					echo '</td>';
				} else {
					echo '<td class="qm-nowrap qm-ltr qm-has-toggle"><ol class="qm-toggler">';
					echo self::build_toggler(); // WPCS: XSS ok;
					echo '<li>';
					echo self::output_filename( $mofile['caller']['display'], $mofile['caller']['file'], $mofile['caller']['line'] ); // WPCS: XSS ok.
					echo '</li>';
					echo '</ol></td>';
				}

				echo '<td class="qm-ltr">';
				echo esc_html( QM_Util::standard_dir( $mofile['mofile'], '' ) );
				echo '</td>';

				if ( $mofile['found'] ) {
					echo '<td class="qm-nowrap">';
					echo esc_html( size_format( $mofile['found'] ) );
					echo '</td>';
				} else {
					echo '<td class="' . esc_attr( $not_found_class ) . '">';
					echo esc_html__( 'Not Found', 'query-monitor' );
					echo '</td>';
				}

				echo '</tr>';
				$first = false;
			}
		}

		echo '</tbody>';

		echo '</table>';
		echo '</div>';

	}

	public function admin_menu( array $menu ) {

		$data = $this->collector->get_data();
		$args = array(
			'title' => esc_html( $this->collector->name() ),
		);

		$menu[] = $this->menu( $args );

		return $menu;

	}

}

function register_qm_output_html_languages( array $output, QM_Collectors $collectors ) {
	if ( $collector = QM_Collectors::get( 'languages' ) ) {
		$output['languages'] = new QM_Output_Html_Languages( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_languages', 81, 2 );
