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

class QM_Output_Html_Timing extends QM_Output_Html {

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 15 );
	}

	public function output() {

		$data = $this->collector->get_data();

		echo '<div class="qm" id="' . esc_attr( $this->collector->id() ) . '">';
		echo '<table>';

		if ( ! empty( $data['timing'] ) || ! empty( $data['warning'] ) ) {

			echo '<caption class="screen-reader-text">' . esc_html__( 'Function Timing', 'query-monitor' ) . '</caption>';

			echo '<thead>';
			echo '<tr>';
			echo '<th scope="col">' . esc_html__( 'Tracked function', 'query-monitor' ) . '</th>';
			echo '<th scope="col" class="qm-num">' . esc_html__( 'Time', 'query-monitor' ) . '</th>';
			echo '<th scope="col" class="qm-num">' . esc_html__( '~kB', 'query-monitor' ) . '</th>';
			echo '<th scope="col">' . esc_html__( 'Component', 'query-monitor' ) . '</th>';
			echo '</tr>';
			echo '</thead>';

			echo '<tbody>';
			if ( ! empty( $data['timing'] ) ) {
				foreach ( $data['timing'] as $row ) {

					$component = $row['trace']->get_component();
					$trace     = $row['trace']->get_filtered_trace();
					$file      = self::output_filename( $row['function'], $trace[0]['file'], $trace[0]['line'] );

					echo '<tr>';
					printf( // WPCS: XSS ok;
						'<td class="qm-ltr">%s</td>',
						$file
					);
					printf(
						'<td class="qm-num">%s</td>',
						esc_html( number_format_i18n( $row['function_time'] * 1000, 4 ) )
					);
					printf(
						'<td class="qm-num">%s</td>',
						esc_html( number_format_i18n( $row['function_memory'] / 1024 ) )
					);
					printf(
						'<td class="qm-nowrap">%s</td>',
						esc_html( $component->name )
					);

					echo '</tr>';

				}
			}
			if ( ! empty( $data['warning'] ) ) {
				foreach ( $data['warning'] as $row ) {
					$component = $row['trace']->get_component();
					$trace     = $row['trace']->get_filtered_trace();
					$file      = self::output_filename( $row['function'], $trace[0]['file'], $trace[0]['line'] );

					echo '<tr>';
					printf( // WPCS: XSS ok;
						'<td class="qm-ltr">%s</td>',
						$file
					);

					printf(
						'<td class="qm-warn" colspan="2">%s</td>',
						esc_html( $row['message'] )
					);

					printf(
						'<td class="qm-nowrap">%s</td>',
						esc_html( $component->name )
					);
				}
			}
			echo '</tbody>';
		} else {
			echo '<thead>';
			echo '<tr>';
			echo '<th>' . esc_html__( 'Function timing', 'query-monitor' ) . '</th>';
			echo '</tr>';
			echo '</thead>';

			echo '<tbody>';
			echo '<tr>';
			echo '<td style="text-align:center !important"><em>' . esc_html__( 'none', 'query-monitor' ) . '</em></td>';
			echo '</tr>';
			echo '</tbody>';

		}

		echo '</table>';
		echo '</div>';

	}

	public function admin_menu( array $menu ) {
		$data = $this->collector->get_data();

		if ( ! empty( $data['timing'] ) || ! empty( $data['warning'] ) ) {
			$count = 0;
			if ( ! empty( $data['timing'] ) ) {
				$count += count( $data['timing'] );
			}
			if ( ! empty( $data['warning'] ) ) {
				$count += count( $data['warning'] );
			}
			/* translators: %s: Number of function timing results that are available */
			$label = _n( 'Timings (%s)', 'Timings (%s)', $count, 'query-monitor' );
			$menu[] = $this->menu( array(
				'title' => esc_html( sprintf(
					$label,
					number_format_i18n( $count )
				) ),
			) );
		}

		return $menu;
	}

}

function register_qm_output_html_timing( array $output, QM_Collectors $collectors ) {
	if ( $collector = QM_Collectors::get( 'timing' ) ) {
		$output['timing'] = new QM_Output_Html_Timing( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_timing', 15, 2 );
