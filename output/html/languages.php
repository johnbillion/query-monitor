<?php declare(strict_types = 1);
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
		/** @var QM_Data_Languages $data */
		$data = $this->collector->get_data();

		$this->before_non_tabular_output();

		echo '<section>';
		echo '<h3><code>get_locale()</code></h3>';
		echo '<p>' . esc_html( $data->locale ) . '</p>';
		echo '</section>';

		echo '<section>';
		echo '<h3><code>get_user_locale()</code></h3>';
		echo '<p>' . esc_html( $data->user_locale ) . '</p>';
		echo '</section>';

		echo '<section>';
		echo '<h3><code>determine_locale()</code></h3>';
		echo '<p>' . esc_html( $data->determined_locale ) . '</p>';
		echo '</section>';

		if ( isset( $data->mlp_language ) ) {
			echo '<section>';
			echo '<h3>';
			printf(
				/* translators: %s: Name of a multilingual plugin */
				esc_html__( '%s Language', 'query-monitor' ),
				'MultilingualPress'
			);
			echo '</h3>';
			echo '<p>' . esc_html( $data->mlp_language ) . '</p>';
			echo '</section>';
		}

		if ( isset( $data->pll_language ) ) {
			echo '<section>';
			echo '<h3>';
			printf(
				/* translators: %s: Name of a multilingual plugin */
				esc_html__( '%s Language', 'query-monitor' ),
				'Polylang'
			);
			echo '</h3>';
			echo '<p>' . esc_html( $data->pll_language ) . '</p>';
			echo '</section>';
		}

		echo '<section>';
		echo '<h3><code>get_language_attributes()</code></h3>';
		echo '<p><code>' . esc_html( $data->language_attributes ) . '</code></p>';
		echo '</section>';

		if ( ! empty( $data->languages ) ) {
			echo '<table class="qm-full-width">';
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

			foreach ( $data->languages as $textdomain => $mofiles ) {
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
						// undefined:
						echo self::output_filename( $mofile['caller']['display'], $mofile['caller']['file'], $mofile['caller']['line'] ); // WPCS: XSS ok.
						echo '</li>';
						echo '</ol></td>';
					}

					echo '<td class="qm-ltr">';
					if ( $mofile['file'] ) {
						if ( $mofile['found'] && 'gettext' !== $mofile['type'] && self::has_clickable_links() ) {
							echo self::output_filename( QM_Util::standard_dir( $mofile['file'], '' ), $mofile['file'], 1, true ); // WPCS: XSS ok.
						} else {
							echo esc_html( QM_Util::standard_dir( $mofile['file'], '' ) );
						}
					} else {
						echo '<em>' . esc_html__( 'None', 'query-monitor' ) . '</em>';
					}
					echo '</td>';

					if ( $mofile['found'] ) {
						echo '<td class="qm-nowrap qm-num">';
						echo esc_html( sprintf(
							/* translators: %s: Memory used in kilobytes */
							__( '%s kB', 'query-monitor' ),
							number_format_i18n( $mofile['found'] / 1024, 1 )
						) );
						echo '</td>';
					} else {
						echo '<td class="qm-nowrap">';
						echo esc_html__( 'Not Found', 'query-monitor' );
						echo '</td>';
					}

					echo '</tr>';
				}
			}

			echo '</tbody>';

			echo '<tfoot>';
			echo '<tr>';
			echo '<td colspan="4">&nbsp;</td>';
			echo '<td class="qm-num">';

			echo esc_html( sprintf(
				/* translators: %s: Memory used in kilobytes */
				__( '%s kB', 'query-monitor' ),
				number_format_i18n( $data->total_size / 1024, 1 )
			) );

			echo '</td>';
			echo '</tr>';
			echo '</tfoot>';

			echo '</table>';
		}

		$this->after_non_tabular_output();
	}

	/**
	 * @param array<string, mixed[]> $menu
	 * @return array<string, mixed[]>
	 */
	public function admin_menu( array $menu ) {
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
