<?php declare(strict_types = 1);
/**
 * Transient storage output for HTML pages.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QM_Output_Html_Transients extends QM_Output_Html {

	/**
	 * Collector instance.
	 *
	 * @var QM_Collector_Transients Collector.
	 */
	protected $collector;

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 100 );
	}

	/**
	 * @return string
	 */
	public function name() {
		return __( 'Transients', 'query-monitor' );
	}

	/**
	 * @return void
	 */
	public function output() {
		/** @var QM_Data_Transients $data */
		$data = $this->collector->get_data();

		if ( ! empty( $data->trans ) ) {

			$this->before_tabular_output();

			echo '<thead>';
			echo '<tr>';
			echo '<th scope="col">' . esc_html__( 'Updated Transient', 'query-monitor' ) . '</th>';
			if ( $data->has_type ) {
				echo '<th scope="col">' . esc_html_x( 'Type', 'transient type', 'query-monitor' ) . '</th>';
			}
			echo '<th scope="col">' . esc_html__( 'Expiration', 'query-monitor' ) . '</th>';
			echo '<th scope="col">' . esc_html_x( 'Size', 'size of transient value', 'query-monitor' ) . '</th>';
			echo '<th scope="col">' . esc_html__( 'Caller', 'query-monitor' ) . '</th>';
			echo '<th scope="col">' . esc_html__( 'Component', 'query-monitor' ) . '</th>';
			echo '</tr>';
			echo '</thead>';

			echo '<tbody>';

			foreach ( $data->trans as $row ) {
				$component = $row['component'];

				echo '<tr>';
				printf(
					'<td class="qm-ltr"><code>%s</code></td>',
					esc_html( $row['name'] )
				);
				if ( $data->has_type ) {
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
						esc_html( (string) $row['expiration'] ),
						esc_html( $row['exp_diff'] )
					);
				}

				printf(
					'<td class="qm-nowrap">~%s</td>',
					esc_html( $row['size_formatted'] )
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

			$this->after_tabular_output();
		} else {
			$this->before_non_tabular_output();

			$notice = __( 'No transients set.', 'query-monitor' );
			echo $this->build_notice( $notice ); // WPCS: XSS ok.

			$this->after_non_tabular_output();
		}
	}

	/**
	 * @param array<string, mixed[]> $menu
	 * @return array<string, mixed[]>
	 */
	public function admin_menu( array $menu ) {
		/** @var QM_Data_Transients $data */
		$data = $this->collector->get_data();
		$count = count( $data->trans );

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

/**
 * @param array<string, QM_Output> $output
 * @param QM_Collectors $collectors
 * @return array<string, QM_Output>
 */
function register_qm_output_html_transients( array $output, QM_Collectors $collectors ) {
	$collector = QM_Collectors::get( 'transients' );
	if ( $collector ) {
		$output['transients'] = new QM_Output_Html_Transients( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_transients', 100, 2 );
