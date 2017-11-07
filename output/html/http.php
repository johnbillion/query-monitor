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

		$vars = array();

		if ( ! empty( $data['vars'] ) ) {
			foreach ( $data['vars'] as $key => $value ) {
				$vars[] = $key . ': ' . $value;
			}
		}

		if ( ! empty( $data['http'] ) ) {

			echo '<caption class="screen-reader-text">' . esc_html__( 'HTTP API Calls', 'query-monitor' ) . '</caption>';

			echo '<thead>';
			echo '<tr>';
			echo '<th scope="col" class="qm-sorted-asc">&nbsp;';
			echo $this->build_sorter(); // WPCS: XSS ok.
			echo '</th>';
			echo '<th scope="col">' . esc_html__( 'HTTP Request', 'query-monitor' ) . '</th>';
			echo '<th scope="col">';
			echo $this->build_filter( 'type', array_keys( $data['types'] ), __( 'Status', 'query-monitor' ) ); // WPCS: XSS ok.
			echo '</th>';
			echo '<th scope="col">' . esc_html__( 'Caller', 'query-monitor' ) . '</th>';
			echo '<th scope="col">';
			echo $this->build_filter( 'component', wp_list_pluck( $data['component_times'], 'component' ), __( 'Component', 'query-monitor' ) ); // WPCS: XSS ok.
			echo '</th>';
			echo '<th scope="col" class="qm-num">' . esc_html__( 'Timeout', 'query-monitor' );
			echo $this->build_sorter(); // WPCS: XSS ok.
			echo '</th>';
			echo '<th scope="col" class="qm-num">' . esc_html__( 'Time', 'query-monitor' );
			echo $this->build_sorter(); // WPCS: XSS ok.
			echo '</th>';
			echo '</tr>';
			echo '</thead>';

			echo '<tbody>';
			$i = 0;

			foreach ( $data['http'] as $key => $row ) {
				$ltime = $row['ltime'];
				$i++;
				$is_error = false;

				$row_attr = array();

				if ( is_wp_error( $row['response'] ) ) {
					$response = $row['response']->get_error_message();
					$is_error = true;
				} else {
					$code     = wp_remote_retrieve_response_code( $row['response'] );
					$msg      = wp_remote_retrieve_response_message( $row['response'] );
					$css      = '';

					if ( intval( $code ) >= 400 ) {
						$is_error = true;
					}

					$response = $code . ' ' . $msg;

				}

				if ( $is_error ) {
					$css = 'qm-warn';
				}

				$method = esc_html( $row['args']['method'] );

				if ( empty( $row['args']['blocking'] ) ) {
					$method .= '<br><span class="qm-info">' . esc_html( sprintf(
						/* translators: A non-blocking HTTP API request. %s: Relevant argument name */
						__( '(Non-blocking request: %s)', 'query-monitor' ),
						'blocking=false'
					) ) . '</span>';
				}

				$url = self::format_url( $row['url'] );

				if ( 'https' === parse_url( $row['url'], PHP_URL_SCHEME ) ) {
					if ( empty( $row['args']['sslverify'] ) && empty( $row['args']['local'] ) ) {
						$method .= '<br><span class="qm-warn">' . esc_html( sprintf(
							/* translators: An HTTP API request has disabled certificate verification. 1: Relevant argument name */
							__( '(Certificate verification disabled: %s)', 'query-monitor' ),
							'sslverify=false'
						) ) . '</span>';
					} elseif ( ! $is_error ) {
						$url = preg_replace( '|^https:|', '<span class="qm-true">https</span>:', $url );
					}
				}

				$component = $row['component'];

				$stack          = array();
				$filtered_trace = $row['trace']->get_display_trace();
				array_pop( $filtered_trace ); // remove WP_Http->request()
				array_pop( $filtered_trace ); // remove WP_Http->{$method}()
				array_pop( $filtered_trace ); // remove wp_remote_{$method}()

				foreach ( $filtered_trace as $item ) {
					$stack[] = self::output_filename( $item['display'], $item['calling_file'], $item['calling_line'] );
				}

				$row_attr['data-qm-component'] = $component->name;
				$row_attr['data-qm-type']      = $row['type'];

				if ( 'core' !== $component->context ) {
					$row_attr['data-qm-component'] .= ' non-core';
				}

				$attr = '';
				foreach ( $row_attr as $a => $v ) {
					$attr .= ' ' . $a . '="' . esc_attr( $v ) . '"';
				}

				printf( // WPCS: XSS ok.
					'<tr %s class="%s">',
					$attr,
					esc_attr( $css )
				);
				printf(
					'<td class="qm-num">%s</td>',
					intval( $i )
				);
				printf( // WPCS: XSS ok.
					'<td class="qm-url qm-ltr qm-wrap">%s<br>%s</td>',
					$method,
					$url
				);
				printf(
					'<td>%s</td>',
					esc_html( $response )
				);

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
				printf(
					'<td class="qm-num">%s</td>',
					esc_html( $row['args']['timeout'] )
				);

				if ( empty( $ltime ) ) {
					$stime = '';
				} else {
					$stime = number_format_i18n( $ltime, 4 );
				}

				printf(
					'<td class="qm-num" data-qm-sort-weight="%s">%s</td>',
					esc_attr( $ltime ),
					esc_html( $stime )
				);
				echo '</tr>';
			}

			echo '</tbody>';
			echo '<tfoot>';

			$total_stime = number_format_i18n( $data['ltime'], 4 );

			echo '<tr>';
			printf(
				'<td colspan="6">%s</td>',
				implode( '<br>', array_map( 'esc_html', $vars ) )
			);
			echo '<td class="qm-num">' . esc_html( $total_stime ) . '</td>';
			echo '</tr>';
			echo '</tfoot>';

		} else {

			echo '<thead>';
			echo '<tr>';
			echo '<th scope="col">' . esc_html__( 'HTTP Requests', 'query-monitor' ) . '</th>';
			echo '</tr>';
			echo '</thead>';

			echo '<tbody>';
			echo '<tr>';
			echo '<td style="text-align:center !important"><em>' . esc_html__( 'none', 'query-monitor' ) . '</em></td>';
			echo '</tr>';
			if ( ! empty( $vars ) ) {
				echo '<tr>';
				printf(
					'<td>%s</td>',
					implode( '<br>', array_map( 'esc_html', $vars ) )
				);
				echo '</tr>';
			}
			echo '</tbody>';

		}

		echo '</table>';
		echo '</div>';

	}

	public function admin_class( array $class ) {

		$data = $this->collector->get_data();

		if ( isset( $data['errors']['alert'] ) ) {
			$class[] = 'qm-alert';
		}
		if ( isset( $data['errors']['warning'] ) ) {
			$class[] = 'qm-warning';
		}

		return $class;

	}

	public function admin_menu( array $menu ) {

		$data = $this->collector->get_data();

		$count = isset( $data['http'] ) ? count( $data['http'] ) : 0;

		$title = ( empty( $count ) )
			? __( 'HTTP API Calls', 'query-monitor' )
			/* translators: %s: Number of calls to the HTTP API */
			: __( 'HTTP API Calls (%s)', 'query-monitor' );

		$args = array(
			'title' => esc_html( sprintf(
				$title,
				number_format_i18n( $count )
			) ),
		);

		if ( isset( $data['errors']['alert'] ) ) {
			$args['meta']['classname'] = 'qm-alert';
		} elseif ( isset( $data['errors']['warning'] ) ) {
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
