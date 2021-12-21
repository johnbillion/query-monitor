<?php
/**
 * User capability checks output for HTML pages.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QM_Output_Html_Caps extends QM_Output_Html {

	/**
	 * Collector instance.
	 *
	 * @var QM_Collector_Caps Collector.
	 */
	protected $collector;

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 105 );
	}

	/**
	 * @return string
	 */
	public function name() {
		return __( 'Capability Checks', 'query-monitor' );
	}

	/**
	 * @return void
	 */
	public function output() {
		$collector = $this->collector;

		if ( ! $collector::enabled() ) {
			$this->before_non_tabular_output();

			echo '<section>';
			echo '<div class="qm-notice">';
			echo '<p>';
			printf(
				/* translators: %s: Configuration file name. */
				esc_html__( 'For performance reasons, this panel is not enabled by default. To enable it, add the following code to your %s file:', 'query-monitor' ),
				'<code>wp-config.php</code>'
			);
			echo '</p>';
			echo "<p><code>define( 'QM_ENABLE_CAPS_PANEL', true );</code></p>";
			echo '</div>';
			echo '</section>';

			$this->after_non_tabular_output();

			return;
		}

		$data = $this->collector->get_data();

		if ( ! empty( $data['caps'] ) ) {
			$this->before_tabular_output();

			$results = array(
				'true',
				'false',
			);
			$show_user = ( count( $data['users'] ) > 1 );
			$parts = $data['parts'];
			$components = $data['components'];

			usort( $parts, 'strcasecmp' );
			usort( $components, 'strcasecmp' );

			echo '<thead>';
			echo '<tr>';
			echo '<th scope="col" class="qm-filterable-column">';
			echo $this->build_filter( 'name', $parts, __( 'Capability Check', 'query-monitor' ) ); // WPCS: XSS ok;
			echo '</th>';

			$users = $data['users'];

			usort( $users, 'strcasecmp' );

			echo '<th scope="col" class="qm-filterable-column qm-num">';
			echo $this->build_filter( 'user', $users, __( 'User', 'query-monitor' ) ); // WPCS: XSS ok;
			echo '</th>';

			echo '<th scope="col" class="qm-filterable-column">';
			echo $this->build_filter( 'result', $results, __( 'Result', 'query-monitor' ) ); // WPCS: XSS ok;
			echo '</th>';
			echo '<th scope="col">' . esc_html__( 'Caller', 'query-monitor' ) . '</th>';
			echo '<th scope="col" class="qm-filterable-column">';
			echo $this->build_filter( 'component', $components, __( 'Component', 'query-monitor' ) ); // WPCS: XSS ok.
			echo '</th>';
			echo '</tr>';
			echo '</thead>';

			echo '<tbody>';

			foreach ( $data['caps'] as $row ) {
				$component = $row['component'];

				$row_attr = array();
				$row_attr['data-qm-name'] = implode( ' ', $row['parts'] );
				$row_attr['data-qm-user'] = $row['user'];
				$row_attr['data-qm-component'] = $component->name;
				$row_attr['data-qm-result'] = ( $row['result'] ) ? 'true' : 'false';

				if ( 'core' !== $component->context ) {
					$row_attr['data-qm-component'] .= ' non-core';
				}

				if ( '' === $row['name'] ) {
					$row_attr['class'] = 'qm-warn';
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
						$name .= ',&nbsp;' . esc_html( QM_Util::display_variable( $arg ) );
					}
				}

				printf( // WPCS: XSS ok.
					'<td class="qm-ltr qm-nowrap"><code>%s</code></td>',
					$name
				);

				printf(
					'<td class="qm-num">%s</td>',
					esc_html( $row['user'] )
				);

				$result = ( $row['result'] ) ? '<span class="qm-true">true&nbsp;&#x2713;</span>' : 'false';
				printf( // WPCS: XSS ok.
					'<td class="qm-nowrap">%s</td>',
					$result
				);

				$stack = array();

				foreach ( $row['filtered_trace'] as $frame ) {
					$stack[] = self::output_filename( $frame['display'], $frame['calling_file'], $frame['calling_line'] );
				}

				$caller = array_shift( $stack );

				echo '<td class="qm-has-toggle qm-nowrap qm-ltr">';

				if ( ! empty( $stack ) ) {
					echo self::build_toggler(); // WPCS: XSS ok;
				}

				echo '<ol>';

				echo "<li>{$caller}</li>"; // WPCS: XSS ok.

				if ( ! empty( $stack ) ) {
					echo '<div class="qm-toggled"><li>' . implode( '</li><li>', $stack ) . '</li></div>'; // WPCS: XSS ok.
				}

				echo '</ol></td>';

				printf(
					'<td class="qm-nowrap">%s</td>',
					esc_html( $component->name )
				);

				echo '</tr>';

			}

			echo '</tbody>';

			echo '<tfoot>';

			$count = count( $data['caps'] );

			echo '<tr>';
			echo '<td colspan="5">';
			printf(
				/* translators: %s: Number of user capability checks */
				esc_html( _nx( 'Total: %s', 'Total: %s', $count, 'User capability checks', 'query-monitor' ) ),
				'<span class="qm-items-number">' . esc_html( number_format_i18n( $count ) ) . '</span>'
			);
			echo '</td>';
			echo '</tr>';
			echo '</tfoot>';

			$this->after_tabular_output();
		} else {
			$this->before_non_tabular_output();

			$notice = __( 'No capability checks were recorded.', 'query-monitor' );
			echo $this->build_notice( $notice ); // WPCS: XSS ok.

			$this->after_non_tabular_output();
		}
	}

	/**
	 * @param array<string, mixed[]> $menu
	 * @return array<string, mixed[]>
	 */
	public function admin_menu( array $menu ) {
		$menu[ $this->collector->id() ] = $this->menu( array(
			'title' => $this->name(),
		) );
		return $menu;

	}

}

/**
 * @param array<string, QM_Output> $output
 * @param QM_Collectors $collectors
 * @return array<string, QM_Output>
 */
function register_qm_output_html_caps( array $output, QM_Collectors $collectors ) {
	$collector = QM_Collectors::get( 'caps' );
	if ( $collector ) {
		$output['caps'] = new QM_Output_Html_Caps( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_caps', 105, 2 );
