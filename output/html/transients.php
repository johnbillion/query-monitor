<?php
/**
 * Transient storage output for HTML pages.
 *
 * @package query-monitor
 */

class QM_Output_Html_Transients extends QM_Output_Html {

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 100 );
	}

	public function output() {

		$data = $this->collector->get_data();

		if ( ! empty( $data['trans'] ) ) {

			$this->before_tabular_output();

			echo '<thead>';
			echo '<tr>';
			echo '<th scope="col">' . esc_html__( 'Updated Transient', 'query-monitor' ) . '</th>';
			if ( is_multisite() ) {
				echo '<th scope="col">' . esc_html_x( 'Type', 'transient type', 'query-monitor' ) . '</th>';
			}
			echo '<th scope="col">' . esc_html__( 'Expiration', 'query-monitor' ) . '</th>';
			echo '<th scope="col">' . esc_html_x( 'Size', 'size of transient value', 'query-monitor' ) . '</th>';
			echo '<th scope="col">' . esc_html__( 'Caller', 'query-monitor' ) . '</th>';
			echo '<th scope="col">' . esc_html__( 'Component', 'query-monitor' ) . '</th>';
			echo '</tr>';
			echo '</thead>';

			echo '<tbody>';

			foreach ( $data['trans'] as $row ) {
				$transient = str_replace( array(
					'_site_transient_',
					'_transient_',
				), '', $row['transient'] );

				$component = $row['trace']->get_component();

				echo '<tr>';
				printf(
					'<td class="qm-ltr"><code>%s</code></td>',
					esc_html( $transient )
				);
				if ( is_multisite() ) {
					printf(
						'<td class="qm-ltr qm-nowrap">%s</td>',
						esc_html( $row['type'] )
					);
				}

				if ( 0 === $row['expiration'] ) {
					printf(
						'<td class="qm-nowrap"><em>%s</em></td>',
						esc_html__( 'none', 'query-monitor' )
					);
				} else {
					printf(
						'<td class="qm-nowrap">%s <span class="qm-info">(~%s)</span></td>',
						esc_html( $row['expiration'] ),
						esc_html( human_time_diff( 0, $row['expiration'] ) )
					);
				}

				printf(
					'<td class="qm-nowrap">~%s</td>',
					esc_html( size_format( $row['size'] ) )
				);

				$stack          = array();
				$filtered_trace = $row['trace']->get_display_trace();
				array_pop( $filtered_trace ); // remove do_action('setted_(site_)?transient')
				array_pop( $filtered_trace ); // remove set_(site_)?transient()

				foreach ( $filtered_trace as $item ) {
					$stack[] = self::output_filename( $item['display'], $item['calling_file'], $item['calling_line'] );
				}

				echo '<td class="qm-has-toggle qm-nowrap qm-ltr"><ol class="qm-toggler qm-numbered">';

				$caller = array_pop( $stack );

				if ( ! empty( $stack ) ) {
					echo self::build_toggler(); // WPCS: XSS ok;
					echo '<div class="qm-toggled"><li>' . implode( '</li><li>', $stack ) . '</li></div>'; // WPCS: XSS ok.
				}

				echo "<li>{$caller}</li>"; // WPCS: XSS ok.
				echo '</ol></td>';

				printf(
					'<td class="qm-nowrap">%s</td>',
					esc_html( $component->name )
				);

				echo '</tr>';

			}

			$this->after_tabular_output();
		} else {
			$this->before_non_tabular_output();

			$notice = __( 'No transients set.', 'query-monitor' );
			echo $this->build_notice( $notice ); // WPCS: XSS ok.

			$this->after_non_tabular_output();
		}
	}

	public function admin_menu( array $menu ) {

		$data  = $this->collector->get_data();
		$count = isset( $data['trans'] ) ? count( $data['trans'] ) : 0;

		$title = ( empty( $count ) )
			? __( 'Transient Updates', 'query-monitor' )
			/* translators: %s: Number of transient values that were updated */
			: __( 'Transient Updates (%s)', 'query-monitor' );

		$menu[ $this->collector->id() ] = $this->menu( array(
			'title' => esc_html( sprintf(
				$title,
				number_format_i18n( $count )
			) ),
		) );
		return $menu;

	}

}

function register_qm_output_html_transients( array $output, QM_Collectors $collectors ) {
	$collector = QM_Collectors::get( 'transients' );
	if ( $collector ) {
		$output['transients'] = new QM_Output_Html_Transients( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_transients', 100, 2 );
