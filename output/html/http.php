<?php
/*
Copyright 2009-2015 John Blackbourn

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

*/

class QM_Output_Html_HTTP extends QM_Output_Html {

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 90 );
		add_filter( 'qm/output/menu_class', array( $this, 'admin_class' ) );
	}

	public function output() {

		$data = $this->collector->get_data();

		$total_time = 0;

		echo '<div class="qm" id="' . esc_attr( $this->collector->id() ) . '">';
		echo '<table cellspacing="0" class="qm-sortable">';
		echo '<thead>';
		echo '<tr>';
		echo '<th class="qm-sorted-asc">&nbsp;' . $this->build_sorter() . '</th>';
		echo '<th scope="col">' . __( 'HTTP Request', 'query-monitor' ) . '</th>';
		echo '<th scope="col">' . __( 'Response', 'query-monitor' ) . $this->build_filter( 'type', array_keys( $data['types'] ) ) . '</th>';
		echo '<th scope="col">' . __( 'Transport', 'query-monitor' ) . '</th>';
		echo '<th scope="col">' . __( 'Call Stack', 'query-monitor' ) . '</th>';
		echo '<th scope="col">' . __( 'Component', 'query-monitor' ) . $this->build_filter( 'component', wp_list_pluck( $data['component_times'], 'component' ) ) . '</th>';
		echo '<th scope="col" class="qm-num">' . __( 'Timeout', 'query-monitor' ) . $this->build_sorter() . '</th>';
		echo '<th scope="col" class="qm-num">' . __( 'Time', 'query-monitor' ) . $this->build_sorter() . '</th>';
		echo '</tr>';
		echo '</thead>';

		$vars = '';

		if ( !empty( $data['vars'] ) ) {
			$vars = array();
			foreach ( $data['vars'] as $key => $value ) {
				$vars[] = $key . ': ' . esc_html( $value );
			}
			$vars = implode( '<br>', $vars );
		}

		if ( !empty( $data['http'] ) ) {

			echo '<tbody>';
			$i = 0;

			foreach ( $data['http'] as $key => $row ) {
				$ltime = $row['ltime'];
				$i++;

				$row_attr = array();

				if ( empty( $ltime ) ) {
					$stime = '';
				} else {
					$stime = number_format_i18n( $ltime, 4 );
				}

				if ( is_wp_error( $row['response'] ) ) {
					$response = esc_html( $row['response']->get_error_message() );
					$css      = 'qm-warn';
				} else {
					$response = wp_remote_retrieve_response_code( $row['response'] );
					$msg      = wp_remote_retrieve_response_message( $row['response'] );
					$css      = '';

					if ( empty( $response ) ) {
						$response = __( 'n/a', 'query-monitor' );
					} else {
						$response = esc_html( $response . ' ' . $msg );
					}

					if ( intval( $response ) >= 400 ) {
						$css = 'qm-warn';
					}

				}

				$method = $row['args']['method'];
				if ( !$row['args']['blocking'] ) {
					$method .= '&nbsp;' . _x( '(non-blocking)', 'non-blocking HTTP transport', 'query-monitor' );
				}
				$url = self::format_url( $row['url'] );

				if ( isset( $row['transport'] ) ) {
					$transport = $row['transport'];
				} else {
					$transport = '';
				}

				$stack     = $row['trace']->get_stack();
				$component = $row['component'];

				$row_attr['data-qm-component'] = $component->name;
				$row_attr['data-qm-type']      = $row['type'];

				$attr = '';
				foreach ( $row_attr as $a => $v ) {
					$attr .= ' ' . $a . '="' . esc_attr( $v ) . '"';
				}

				$stack = implode( '<br>', $stack );
				echo "
					<tr{$attr} class='{$css}'>\n
						<td valign='top' class='qm-num'>{$i}</td>
						<td valign='top' class='qm-url qm-ltr qm-wrap'>{$method}<br>{$url}</td>\n
						<td valign='top'>{$response}</td>\n
						<td valign='top'>{$transport}</td>\n
						<td valign='top' class='qm-nowrap qm-ltr'>{$stack}</td>\n
						<td valign='top' class='qm-nowrap'>{$component->name}</td>\n
						<td valign='top' class='qm-num'>{$row['args']['timeout']}</td>\n
						<td valign='top' class='qm-num'>{$stime}</td>\n
					</tr>\n
				";
			}

			echo '</tbody>';
			echo '<tfoot>';

			$total_stime = number_format_i18n( $data['ltime'], 4 );

			echo '<tr>';
			echo '<td colspan="7">' . $vars . '</td>';
			echo "<td class='qm-num'>{$total_stime}</td>";
			echo '</tr>';
			echo '</tfoot>';

		} else {

			echo '<tbody>';
			echo '<tr>';
			echo '<td colspan="8" style="text-align:center !important"><em>' . __( 'none', 'query-monitor' ) . '</em></td>';
			echo '</tr>';
			if ( !empty( $vars ) ) {
				echo '<tr>';
				echo '<td colspan="8">' . $vars . '</td>';
				echo '</tr>';
			}
			echo '</tbody>';
		
		}

		echo '</table>';
		echo '</div>';

	}

	public function admin_class( array $class ) {

		$data = $this->collector->get_data();

		if ( isset( $data['errors']['error'] ) ) {
			$class[] = 'qm-error';
		} else if ( isset( $data['errors']['warning'] ) ) {
			$class[] = 'qm-warning';
		}

		return $class;

	}

	public function admin_menu( array $menu ) {

		$data = $this->collector->get_data();

		$count = isset( $data['http'] ) ? count( $data['http'] ) : 0;

		$title = ( empty( $count ) )
			? __( 'HTTP Requests', 'query-monitor' )
			: __( 'HTTP Requests (%s)', 'query-monitor' );

		$args = array(
			'title' => sprintf( $title, number_format_i18n( $count ) ),
		);

		if ( isset( $data['errors']['error'] ) ) {
			$args['meta']['classname'] = 'qm-error';
		} else if ( isset( $data['errors']['warning'] ) ) {
			$args['meta']['classname'] = 'qm-warning';
		}

		$menu[] = $this->menu( $args );

		return $menu;

	}

}

function register_qm_output_html_http( array $output, QM_Collectors $collectors ) {
	if ( $collector = QM_Collectors::get( 'http' ) ) {
		$output['http'] = new QM_Output_Html_HTTP( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_http', 90, 2 );
