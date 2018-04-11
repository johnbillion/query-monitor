<?php
/**
 * User capability checks output for HTML pages.
 *
 * @package query-monitor
 */

class QM_Output_Html_Caps extends QM_Output_Html {

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 105 );
	}

	public function output() {
		if ( ! defined( 'QM_ENABLE_CAPS_PANEL' ) ) {
			echo '<div class="qm qm-non-tabular" id="' . esc_attr( $this->collector->id() ) . '">';
			echo '<div class="qm-boxed qm-boxed-wrap">';
			echo '<div class="qm-section">';
			echo '<h2>' . esc_html( $this->collector->name() ) . '</h2>';
			echo '<p class="qm-warn">';
			printf(
				/* translators: %s: Configuration file name. */
				esc_html__( 'For performance reasons, this panel is not enabled by default. To enable it, add the following code to your %s file:', 'query-monitor' ),
				'<code>wp-config.php</code>'
			);
			echo '</p>';
			echo "<p><code>define( 'QM_ENABLE_CAPS_PANEL', true );</code></p>";
			echo '</div>';
			echo '</div>';
			echo '</div>';

			return;
		}

		$data = $this->collector->get_data();

		echo '<div class="qm" id="' . esc_attr( $this->collector->id() ) . '">';
		echo '<table>';

		if ( ! empty( $data['caps'] ) ) {

			$results = array(
				'true',
				'false',
			);
			$show_user = ( count( $data['users'] ) > 1 );

			echo '<caption class="screen-reader-text">' . esc_html( $this->collector->name() ) . '</caption>';

			echo '<thead>';
			echo '<tr>';
			echo '<th scope="col" class="qm-filterable-column">';
			echo $this->build_filter( 'name', $data['parts'], __( 'Capability Check', 'query-monitor' ) ); // WPCS: XSS ok;
			echo '</th>';

			if ( $show_user ) {
				echo '<th scope="col" class="qm-filterable-column qm-num">';
				echo $this->build_filter( 'user', $data['users'], __( 'User', 'query-monitor' ) ); // WPCS: XSS ok;
				echo '</th>';
			}

			echo '<th scope="col" class="qm-filterable-column">';
			echo $this->build_filter( 'result', $results, __( 'Result', 'query-monitor' ) ); // WPCS: XSS ok;
			echo '</th>';
			echo '<th scope="col">' . esc_html__( 'Caller', 'query-monitor' ) . '</th>';
			echo '<th scope="col" class="qm-filterable-column">';
			echo $this->build_filter( 'component', $data['components'], __( 'Component', 'query-monitor' ) ); // WPCS: XSS ok.
			echo '</th>';
			echo '</tr>';
			echo '</thead>';

			echo '<tbody>';

			foreach ( $data['caps'] as $row ) {
				$component = $row['trace']->get_component();

				$row_attr = array();
				$row_attr['data-qm-name']      = implode( ' ', $row['parts'] );
				$row_attr['data-qm-user']      = $row['user'];
				$row_attr['data-qm-component'] = $component->name;
				$row_attr['data-qm-result']    = ( $row['result'] ) ? 'true' : 'false';

				if ( 'core' !== $component->context ) {
					$row_attr['data-qm-component'] .= ' non-core';
				}

				$attr = '';

				foreach ( $row_attr as $a => $v ) {
					$attr .= ' ' . $a . '="' . esc_attr( $v ) . '"';
				}

				printf( // WPCS: XSS ok.
					'<tr %s>',
					$attr
				);

				$name = esc_html( $row['name'] );

				if ( ! empty( $row['args'] ) ) {
					foreach ( $row['args'] as $arg ) {
						$name .= '<br>' . esc_html( QM_Util::display_variable( $arg ) );
					}
				}

				printf( // WPCS: XSS ok.
					'<td class="qm-ltr qm-nowrap"><code>%s</code></td>',
					$name
				);

				if ( $show_user ) {
					printf(
						'<td class="qm-num">%s</td>',
						esc_html( $row['user'] )
					);
				}

				$result = ( $row['result'] ) ? '<span class="qm-true">true&nbsp;&#x2713;</span>' : 'false';
				printf( // WPCS: XSS ok.
					'<td class="qm-ltr qm-nowrap">%s</td>',
					$result
				);

				$stack          = array();
				$trace          = $row['trace']->get_trace();
				$filtered_trace = $row['trace']->get_display_trace();

				$last = end( $filtered_trace );
				if ( isset( $last['function'] ) && 'map_meta_cap' === $last['function'] ) {
					array_pop( $filtered_trace ); // remove the map_meta_cap() call
				}

				array_pop( $filtered_trace ); // remove the WP_User->has_cap() call
				array_pop( $filtered_trace ); // remove the *_user_can() call

				if ( ! count( $filtered_trace ) ) {
					$responsible_name = QM_Util::standard_dir( $trace[1]['file'], '' ) . ':' . $trace[1]['line'];

					$responsible_item = $trace[1];
					$responsible_item['display'] = $responsible_name;
					$responsible_item['calling_file'] = $trace[1]['file'];
					$responsible_item['calling_line'] = $trace[1]['line'];
					array_unshift( $filtered_trace, $responsible_item );
				}

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

			echo '</tbody>';

			echo '<tfoot>';

			$colspan = ( $show_user ) ? 5 : 4;

			echo '<tr>';
			printf(
				'<td colspan="%1$d">%2$s</td>',
				esc_attr( $colspan ),
				esc_html( sprintf(
					/* translators: %s: Number of user capability checks */
					__( 'Total Checks: %s', 'query-monitor' ),
					number_format_i18n( count( $data['caps'] ) )
				) )
			);
			echo '</tr>';
			echo '</tfoot>';

		} else {

			echo '<caption>' . esc_html( $this->collector->name() ) . '</caption>';

			echo '<tbody>';
			echo '<tr>';
			echo '<td>';
			esc_html_e( 'No capability checks were recorded.', 'query-monitor' );
			echo '</td>';
			echo '</tr>';
			echo '</tbody>';

		}

		echo '</table>';
		echo '</div>';

	}

	public function admin_menu( array $menu ) {
		$menu[] = $this->menu( array(
			'title' => $this->collector->name(),
		) );
		return $menu;

	}

}

function register_qm_output_html_caps( array $output, QM_Collectors $collectors ) {
	if ( $collector = QM_Collectors::get( 'caps' ) ) {
		$output['caps'] = new QM_Output_Html_Caps( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_caps', 105, 2 );
