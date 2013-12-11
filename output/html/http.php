<?php
/*

Copyright 2013 John Blackbourn

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
		add_filter( 'query_monitor_menus', array( $this, 'admin_menu' ), 90 );
		add_filter( 'query_monitor_class', array( $this, 'admin_class' ) );
	}

	public function output() {

		$data = $this->collector->get_data();

		$total_time = 0;

		echo '<div class="qm" id="' . $this->collector->id() . '">';
		echo '<table cellspacing="0">';
		echo '<thead>';
		echo '<tr>';
		echo '<th>' . __( 'HTTP Request', 'query-monitor' ) . '</th>';
		echo '<th>' . __( 'Response', 'query-monitor' ) . '</th>';
		echo '<th>' . __( 'Transport', 'query-monitor' ) . '</th>';
		echo '<th>' . __( 'Call Stack', 'query-monitor' ) . '</th>';
		echo '<th>' . __( 'Component', 'query-monitor' ) . '</th>';
		echo '<th>' . __( 'Timeout', 'query-monitor' ) . '</th>';
		echo '<th>' . __( 'Time', 'query-monitor' ) . '</th>';
		echo '</tr>';
		echo '</thead>';

		if ( !empty( $data['http'] ) ) {

			echo '<tbody>';

			foreach ( $data['http'] as $key => $row ) {
				$funcs = array();

				$ltime = ( $row['end'] - $row['start'] );
				$total_time += $ltime;

				if ( empty( $ltime ) )
					$stime = '';
				else
					$stime = number_format_i18n( $ltime, 4 );

				$ltime = number_format_i18n( $ltime, 10 );

				if ( is_wp_error( $row['response'] ) ) {
					$response = $row['response']->get_error_message();
					$css      = 'qm-warn';
				} else {
					$response = wp_remote_retrieve_response_code( $row['response'] );
					$msg      = wp_remote_retrieve_response_message( $row['response'] );
					$css      = '';

					if ( empty( $response ) )
						$response = __( 'n/a', 'query-monitor' );
					else
						$response = esc_html( $response . ' ' . $msg );

					if ( intval( $response ) >= 400 )
						$css = 'qm-warn';

				}

				$method = $row['args']['method'];
				if ( !$row['args']['blocking'] )
					$method .= '&nbsp;' . _x( '(non-blocking)', 'non-blocking HTTP transport', 'query-monitor' );
				$url = QM_Util::format_url( $row['url'] );

				if ( isset( $row['transport'] ) )
					$transport = $row['transport'];
				else
					$transport = '';

				$stack = $row['trace']->get_stack();

				foreach ( $stack as & $frame ) {
					foreach ( array( 'WP_Http', 'wp_remote_', 'fetch_rss', 'fetch_feed', 'SimplePie', 'download_url' ) as $skip ) {
						if ( 0 === strpos( $frame, $skip ) ) {
							$frame = sprintf( '<span class="qm-na">%s</span>', $frame );
							break;
						}
					}
				}

				$component = $row['trace']->get_component();

				$stack = implode( '<br>', $stack );
				echo "
					<tr class='{$css}'>\n
						<td valign='top' class='qm-url qm-ltr'>{$method}<br>{$url}</td>\n
						<td valign='top'>{$response}</td>\n
						<td valign='top'>{$transport}</td>\n
						<td valign='top' class='qm-ltr'>{$stack}</td>\n
						<td valign='top'>{$component->name}</td>\n
						<td valign='top'>{$row['args']['timeout']}</td>\n
						<td valign='top' title='{$ltime}'>{$stime}</td>\n
					</tr>\n
				";
			}

			echo '</tbody>';
			echo '<tfoot>';

			$total_stime = number_format_i18n( $total_time, 4 );
			$total_ltime = number_format_i18n( $total_time, 10 );

			echo '<tr>';
			echo '<td colspan="6">&nbsp;</td>';
			echo "<td title='{$total_ltime}'>{$total_stime}</td>";
			echo '</tr>';
			echo '</tfoot>';

		} else {

			echo '<tbody>';
			echo '<tr>';
			echo '<td colspan="7" style="text-align:center !important"><em>' . __( 'none', 'query-monitor' ) . '</em></td>';
			echo '</tr>';
			echo '</tbody>';
		
		}

		echo '</table>';
		echo '</div>';

	}

	public function admin_class( array $class ) {

		$data = $this->collector->get_data();

		if ( isset( $data['errors']['error'] ) )
			$class[] = 'qm-error';
		else if ( isset( $data['errors']['warning'] ) )
			$class[] = 'qm-warning';

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

		if ( isset( $data['errors']['error'] ) )
			$args['meta']['classname'] = 'qm-error';
		else if ( isset( $data['errors']['warning'] ) )
			$args['meta']['classname'] = 'qm-warning';

		$menu[] = $this->menu( $args );

		return $menu;

	}

}

function register_qm_output_html_http( QM_Output $output = null, QM_Collector $collector ) {
	return new QM_Output_Html_HTTP( $collector );
}

add_filter( 'query_monitor_output_html_http', 'register_qm_output_html_http', 10, 2 );
