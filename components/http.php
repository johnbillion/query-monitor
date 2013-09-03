<?php

class QM_Component_HTTP extends QM_Component {

	var $id   = 'http';
	var $http = array();

	function __construct() {

		parent::__construct();

		add_action( 'http_api_debug',      array( $this, 'http_debug' ),    99, 5 );
		add_filter( 'http_request_args',   array( $this, 'http_request' ),  99, 2 );
		add_filter( 'http_response',       array( $this, 'http_response' ), 99, 3 );
		add_filter( 'query_monitor_menus', array( $this, 'admin_menu' ), 60 );

	}

	function http_request( array $args, $url ) {
		$m_start = microtime( true );
		$key = $m_start . $url;
		$this->data['http'][$key] = array(
			'url'   => $url,
			'args'  => $args,
			'start' => $m_start,
			'trace' => QM_Util::backtrace()
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

				$this->data['http'][$args['_qm_key']]['transport'] = str_replace( 'wp_http_', '', strtolower( $class ) );

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
		return $response;
	}

	function admin_menu( array $menu ) {

		$count = isset( $this->data['http'] ) ? count( $this->data['http'] ) : 0;

		$title = ( empty( $count ) )
			? __( 'HTTP Requests', 'query-monitor' )
			: __( 'HTTP Requests (%s)', 'query-monitor' );

		$menu[] = $this->menu( array(
			'title' => sprintf( $title, number_format_i18n( $count ) )
		) );
		return $menu;

	}

	function output_html( array $args, array $data ) {

		$total_time = 0;

		echo '<div class="qm" id="' . $args['id'] . '">';
		echo '<table cellspacing="0">';
		echo '<thead>';
		echo '<tr>';
		echo '<th>' . __( 'HTTP Request', 'query-monitor' ) . '</th>';
		echo '<th>' . __( 'Method', 'query-monitor' ) . '</th>';
		echo '<th>' . __( 'Response', 'query-monitor' ) . '</th>';
		echo '<th>' . __( 'Transport', 'query-monitor' ) . '</th>';
		echo '<th>' . __( 'Call Stack', 'query-monitor' ) . '</th>';
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
					$method .= '<br />' . _x( '(non-blocking)', 'non-blocking HTTP transport', 'query-monitor' );
				$url = str_replace( array(
					'=',
					'&',
					'?',
				), array(
					'<span class="qm-param">=</span>',
					'<br /><span class="qm-param">&amp;</span>',
					'<br /><span class="qm-param">?</span>',
				), $row['url'] );

				$transports = apply_filters( 'http_api_transports', array(
					'curl', 'streams', 'fsockopen'
				), $row['args'], $row['url'] ); 

				if ( isset( $row['transport'] ) ) {
					foreach ( $transports as & $transport ) {
						if ( $row['transport'] == $transport )
							$transport = sprintf( '<span class="qm-true">%s</span>', $transport );
						else
							$transport = sprintf( '<span class="qm-false">%s</span>', $transport );
					}
					$transport = implode( '<br/>', $transports );
				} else {
					$transport = '';
				}

				unset( $row['trace'][0] ); # http_request_args filter

				foreach ( $row['trace'] as & $trace ) {
					foreach ( array( 'WP_Http', 'wp_remote_', 'fetch_rss', 'fetch_feed', 'SimplePie', 'download_url' ) as $skip ) {
						if ( 0 === strpos( $trace, $skip ) ) {
							$trace = sprintf( '<span class="qm-na">%s</span>', $trace );
							break;
						}
					}
				}

				$stack = implode( '<br />', $row['trace'] );
				echo "
					<tr class='{$css}'>\n
						<td valign='top' class='qm-url qm-ltr'>{$url}</td>\n
						<td valign='top'>{$method}</td>\n
						<td valign='top'>{$response}</td>\n
						<td valign='top'>{$transport}</td>\n
						<td valign='top' class='qm-ltr'>{$stack}</td>\n
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
