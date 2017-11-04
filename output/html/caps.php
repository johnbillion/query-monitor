<?php
/*
Copyright 2009-2017 John Blackbourn

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

*/

class QM_Output_Html_Caps extends QM_Output_Html {

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 105 );
	}

	public function output() {

		$data = $this->collector->get_data();

		echo '<div class="qm" id="' . esc_attr( $this->collector->id() ) . '">';
		echo '<table cellspacing="0">';

		if ( ! empty( $data['caps'] ) ) {

			$results = array(
				'true',
				'false',
			);
			$show_user = ( count( $data['users'] ) > 1 );

			echo '<caption class="screen-reader-text">' . esc_html( $this->collector->name() ) . '</caption>';

			echo '<thead>';
			echo '<tr>';
			echo '<th scope="col">';
			echo $this->build_filter( 'name', $data['parts'], __( 'Capability Check', 'query-monitor' ) ); // WPCS: XSS ok;
			echo '</th>';

			if ( $show_user ) {
				echo '<th scope="col">';
				echo $this->build_filter( 'user', $data['users'], __( 'User', 'query-monitor' ) ); // WPCS: XSS ok;
				echo '</th>';
			}

			echo '<th scope="col">';
			echo $this->build_filter( 'result', $results, __( 'Result', 'query-monitor' ) ); // WPCS: XSS ok;
			echo '</th>';
			echo '<th scope="col">' . esc_html__( 'Caller', 'query-monitor' ) . '</th>';
			echo '<th scope="col">';
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
					'<td class="qm-ltr qm-nowrap">%s</td>',
					$name
				);

				if ( $show_user ) {
					printf(
						'<td class="qm-num">%s</td>',
						esc_html( $row['user'] )
					);
				}

				$result = ( $row['result'] ) ? '<span class="qm-true">true&nbsp;&#x2713;</span>' : '<span class="qm-false">false</span>';
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
					echo $this->build_toggler(); // WPCS: XSS ok;
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
