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

class QM_Component_HTTP extends QM_Component {

	var $id   = 'http';

	function __construct() {

		parent::__construct();

		add_action( 'http_api_debug',      array( $this, 'http_debug' ),    99, 5 );
		add_filter( 'http_request_args',   array( $this, 'http_request' ),  99, 2 );
		add_filter( 'http_response',       array( $this, 'http_response' ), 99, 3 );
		# http://core.trac.wordpress.org/ticket/25747
		add_filter( 'pre_http_request',    array( $this, 'http_response' ), 99, 3 );
		add_filter( 'query_monitor_menus', array( $this, 'admin_menu' ), 60 );
		add_filter( 'query_monitor_class', array( $this, 'admin_class' ) );

	}

	function admin_class( array $class ) {

		if ( isset( $this->data['errors']['error'] ) )
			$class[] = 'qm-error';
		else if ( isset( $this->data['errors']['warning'] ) )
			$class[] = 'qm-warning';

		return $class;

	}

	function http_request( array $args, $url ) {
		$m_start = microtime( true );
		$key = $m_start . $url;
		$this->data['http'][$key] = array(
			'url'   => $url,
			'args'  => $args,
			'start' => $m_start,
			'trace' => new QM_Backtrace
		);
		$args['_qm_key'] = $key;
		return $args;
	}

	function http_debug( $param, $action ) {

		switch ( $action ) {

			case 'response':

				$fga = func_get_args();

				list( $response, $action, $class ) = $fga;

				# http://core.trac.wordpress.org/ticket/18732
				if ( isset( $fga[3] ) )
					$args = $fga[3];
				if ( isset( $fga[4] ) )
					$url = $fga[4];
				if ( !isset( $args['_qm_key'] ) )
					return;

				if ( !empty( $class ) )
					$this->data['http'][$args['_qm_key']]['transport'] = str_replace( 'wp_http_', '', strtolower( $class ) );
				else
					$this->data['http'][$args['_qm_key']]['transport'] = false;

				if ( is_wp_error( $response ) )
					$this->http_response( $response, $args, $url );

				break;

			case 'transports_list':
				# Nothing
				break;

		}

	}

	function http_response( $response, array $args, $url ) {
		$this->data['http'][$args['_qm_key']]['end']      = microtime( true );
		$this->data['http'][$args['_qm_key']]['response'] = $response;

		if ( is_wp_error( $response ) ) {
			$this->data['errors']['error'][] = $args['_qm_key'];
		} else {
			if ( intval( wp_remote_retrieve_response_code( $response ) ) >= 400 )
				$this->data['errors']['warning'][] = $args['_qm_key'];
		}
		return $response;
	}

	function admin_menu( array $menu ) {

		$count = isset( $this->data['http'] ) ? count( $this->data['http'] ) : 0;

		$title = ( empty( $count ) )
			? __( 'HTTP Requests', 'query-monitor' )
			: __( 'HTTP Requests (%s)', 'query-monitor' );

		$args = array(
			'title' => sprintf( $title, number_format_i18n( $count ) ),
		);

		if ( isset( $this->data['errors']['error'] ) )
			$args['meta']['classname'] = 'qm-error';
		else if ( isset( $this->data['errors']['warning'] ) )
			$args['meta']['classname'] = 'qm-warning';

		$menu[] = $this->menu( $args );

		return $menu;

	}

	function output_html( array $args, array $data ) {

		$total_time = 0;

		echo '<div class="qm" id="' . $args['id'] . '">';
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

			foreach ( $data['http'] as $row ) {
				$funcs = array();

				if ( isset( $row['response'] ) ) {

					$ltime = ( $row['end'] - $row['start'] );
					$total_time += $ltime;
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

				} else {

					# @TODO test if the timeout has actually passed. if not, the request was erroneous rather than timed out

					$total_time += $row['args']['timeout'];

					$ltime    = '';
					$stime    = number_format_i18n( $row['args']['timeout'], 4 );
					$response = __( 'Request timed out', 'query-monitor' );
					$css      = 'qm-warn';

				}

				$method = $row['args']['method'];
				if ( !$row['args']['blocking'] )
					$method .= '&nbsp;' . _x( '(non-blocking)', 'non-blocking HTTP transport', 'query-monitor' );
				$url = str_replace( array(
					'=',
					'&',
					'?',
				), array(
					'<span class="qm-param">=</span>',
					'<br /><span class="qm-param">&amp;</span>',
					'<br /><span class="qm-param">?</span>',
				), $row['url'] );

				if ( isset( $row['transport'] ) )
					$transport = $row['transport'];
				else
					$transport = '';

				$stack = $row['trace']->get_stack();

				foreach ( $stack as & $trace ) {
					foreach ( array( 'WP_Http', 'wp_remote_', 'fetch_rss', 'fetch_feed', 'SimplePie', 'download_url' ) as $skip ) {
						if ( 0 === strpos( $trace, $skip ) ) {
							$trace = sprintf( '<span class="qm-na">%s</span>', $trace );
							break;
						}
					}
				}

				$component = QM_Util::get_backtrace_component( $row['trace'] );

				$stack = implode( '<br />', $stack );
				echo "
					<tr class='{$css}'>\n
						<td valign='top' class='qm-url qm-ltr'>{$method}<br/>{$url}</td>\n
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

}

function register_qm_http( array $qm ) {
	$qm['http'] = new QM_Component_HTTP;
	return $qm;
}

add_filter( 'query_monitor_components', 'register_qm_http', 110 );
