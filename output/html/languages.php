<?php
/**
 * Language and locale output for HTML pages.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QM_Output_Html_Languages extends QM_Output_Html {

	/**
	 * Collector instance.
	 *
	 * @var QM_Collector_Languages Collector.
	 */
	protected $collector;

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 80 );
	}

	/**
	 * @return string
	 */
	public function name() {
		return __( 'Languages', 'query-monitor' );
	}

	/**
	 * @return void
	 */
	public function output() {

		$data = $this->collector->get_data();

		if ( empty( $data['languages'] ) ) {
			return;
		}

		$this->before_tabular_output();

		echo '<thead>';
		echo '<tr>';
		echo '<th scope="col">' . esc_html__( 'Text Domain', 'query-monitor' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'Type', 'query-monitor' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'Caller', 'query-monitor' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'Translation File', 'query-monitor' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'Size', 'query-monitor' ) . '</th>';
		echo '</tr>';
		echo '</thead>';

		echo '<tbody>';

		foreach ( $data['languages'] as $textdomain => $mofiles ) {
			foreach ( $mofiles as $mofile ) {
				echo '<tr>';

				if ( $mofile['handle'] ) {
					echo '<td class="qm-ltr">' . esc_html( $mofile['domain'] ) . ' (' . esc_html( $mofile['handle'] ) . ')</td>';
				} else {
					echo '<td class="qm-ltr">' . esc_html( $mofile['domain'] ) . '</td>';
				}

				echo '<td>' . esc_html( $mofile['type'] ) . '</td>';

				if ( self::has_clickable_links() ) {
					echo '<td class="qm-nowrap qm-ltr">';
					echo self::output_filename( $mofile['caller']['display'], $mofile['caller']['file'], $mofile['caller']['line'] ); // WPCS: XSS ok.
					echo '</td>';
				} else {
					echo '<td class="qm-nowrap qm-ltr qm-has-toggle">';
					echo self::build_toggler(); // WPCS: XSS ok;
					echo '<ol>';
					echo '<li>';
					echo self::output_filename( $mofile['caller']['display'], $mofile['caller']['file'], $mofile['caller']['line'] ); // WPCS: XSS ok.
					echo '</li>';
					echo '</ol></td>';
				}

				echo '<td class="qm-ltr">';
				if ( $mofile['file'] ) {
					if ( $mofile['found'] && 'jed' === $mofile['type'] && self::has_clickable_links() ) {
						echo self::output_filename( QM_Util::standard_dir( $mofile['file'], '' ), $mofile['file'], 1, true ); // WPCS: XSS ok.
					} else {
						echo esc_html( QM_Util::standard_dir( $mofile['file'], '' ) );
					}
				} else {
					echo '<em>' . esc_html__( 'None', 'query-monitor' ) . '</em>';
				}
				echo '</td>';

				echo '<td class="qm-nowrap">';

				if ( $mofile['found'] ) {
					echo esc_html( $mofile['found_formatted'] );
				} else {
					echo esc_html__( 'Not Found', 'query-monitor' );
				}

				echo '</td>';

				echo '</tr>';
			}
		}

		echo '</tbody>';

		$this->after_tabular_output();
	}

	/**
	 * @param array<string, mixed[]> $menu
	 * @return array<string, mixed[]>
	 */
	public function admin_menu( array $menu ) {

		$data = $this->collector->get_data();
		$args = array(
			'title' => esc_html( $this->name() ),
		);

		$menu[ $this->collector->id() ] = $this->menu( $args );

		return $menu;

	}

}

/**
 * @param array<string, QM_Output> $output
 * @param QM_Collectors $collectors
 * @return array<string, QM_Output>
 */
function register_qm_output_html_languages( array $output, QM_Collectors $collectors ) {
	$collector = QM_Collectors::get( 'languages' );
	if ( $collector ) {
		$output['languages'] = new QM_Output_Html_Languages( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_languages', 81, 2 );
