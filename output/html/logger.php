<?php
/**
 * PSR-3 compatible logging output for HTML pages.
 *
 * @package query-monitor
 */

class QM_Output_Html_Logger extends QM_Output_Html {

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 12 );
		add_filter( 'qm/output/menu_class', array( $this, 'admin_class' ) );
	}

	public function output() {

		$data = $this->collector->get_data();

		if ( empty( $data['logs'] ) ) {
			return;
		}

		$levels = array_map( 'ucfirst', $this->collector->get_levels() );

		$this->before_tabular_output();

		echo '<thead>';
		echo '<tr>';
		echo '<th scope="col" class="qm-filterable-column">';
		echo $this->build_filter( 'type', $levels, __( 'Level', 'query-monitor' ) ); // WPCS: XSS ok.
		echo '</th>';
		echo '<th scope="col" class="qm-col-message">' . esc_html__( 'Message', 'query-monitor' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'Caller', 'query-monitor' ) . '</th>';
		echo '<th scope="col" class="qm-filterable-column">';
		echo $this->build_filter( 'component', $data['components'], __( 'Component', 'query-monitor' ) ); // WPCS: XSS ok.
		echo '</th>';
		echo '</tr>';
		echo '</thead>';

		echo '<tbody>';

		foreach ( $data['logs'] as $row ) {
			$component = $row['trace']->get_component();

			$row_attr                      = array();
			$row_attr['data-qm-component'] = $component->name;
			$row_attr['data-qm-type']      = ucfirst( $row['level'] );

			$attr = '';

			foreach ( $row_attr as $a => $v ) {
				$attr .= ' ' . $a . '="' . esc_attr( $v ) . '"';
			}

			$is_warning = in_array( $row['level'], $this->collector->get_warning_levels(), true );

			if ( $is_warning ) {
				$class = 'qm-warn';
			} else {
				$class = '';
			}

			echo '<tr' . $attr . ' class="' . esc_attr( $class ) . '">'; // WPCS: XSS ok.

			echo '<td scope="row" class="qm-nowrap">';

			if ( $is_warning ) {
				echo '<span class="dashicons dashicons-warning" aria-hidden="true"></span>';
			} else {
				echo '<span class="dashicons" aria-hidden="true"></span>';
			}

			echo esc_html( ucfirst( $row['level'] ) );
			echo '</td>';

			printf(
				'<td><pre>%s</pre></td>',
				esc_html( $row['message'] )
			);

			$stack          = array();
			$filtered_trace = $row['trace']->get_display_trace();

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

		$this->after_tabular_output();
	}

	public function admin_class( array $class ) {
		$data = $this->collector->get_data();

		if ( empty( $data['logs'] ) ) {
			return $class;
		}

		foreach ( $data['logs'] as $log ) {
			if ( in_array( $log['level'], $this->collector->get_warning_levels(), true ) ) {
				$class[] = 'qm-warning';
				break;
			}
		}

		return $class;
	}

	public function admin_menu( array $menu ) {
		$data = $this->collector->get_data();

		if ( empty( $data['logs'] ) ) {
			return $menu;
		}

		$key = 'log';

		foreach ( $data['logs'] as $log ) {
			if ( in_array( $log['level'], $this->collector->get_warning_levels(), true ) ) {
				$key = 'warning';
				break;
			}
		}

		$menu[ $this->collector->id() ] = $this->menu( array(
			'id'    => "query-monitor-logger-{$key}",
			'title' => esc_html__( 'Logs', 'query-monitor' ),
		) );

		return $menu;
	}

}

function register_qm_output_html_logger( array $output, QM_Collectors $collectors ) {
	$collector = $collectors::get( 'logger' );
	if ( $collector ) {
		$output['logger'] = new QM_Output_Html_Logger( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_logger', 12, 2 );
