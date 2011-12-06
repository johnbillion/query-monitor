<?php

class QM_HTTP extends QM {

	var $id   = 'http';
	var $http = array();

	function __construct() {

		parent::__construct();

		add_action( 'http_api_debug',      array( $this, 'http_debug' ),    99, 5 );
		add_filter( 'http_request_args',   array( $this, 'http_request' ),  99, 2 );
		add_filter( 'http_response',       array( $this, 'http_response' ), 99, 3 );
		add_filter( 'query_monitor_menus', array( $this, 'admin_menu' ), 40 );

	}

	function http_request( $args, $url ) {
		$m_start = microtime( true );
		$key = $m_start;
		$this->data['http'][$key] = array(
			'url'   => $url,
			'args'  => $args,
			'start' => $m_start,
			'trace' => $this->backtrace()
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

	function http_response( $response, $args, $url ) {
		$this->data['http'][$args['_qm_key']]['end']      = microtime( true );
		$this->data['http'][$args['_qm_key']]['response'] = $response;
		return $response;
	}

	function admin_menu( $menu ) {

		$count = isset( $this->data['http'] ) ? count( $this->data['http'] ) : 0;

		$title = ( empty( $count ) )
			? __( 'HTTP Requests', 'query_monitor' )
			: _n( 'HTTP Requests (%s)', 'HTTP Requests (%s)', $count, 'query_monitor' );

		$menu[] = $this->menu( array(
			'title' => sprintf( $title, number_format_i18n( $count ) )
		) );
		return $menu;

	}

	function output( $args, $data ) {

		$total_time = 0;

		echo '<table class="qm" cellspacing="0" id="' . $args['id'] . '">';
		echo '<thead>';
		echo '<tr>';
		echo '<th>' . __( 'HTTP Request', 'query_monitor' ) . '</th>';
		echo '<th>' . __( 'Method', 'query_monitor' ) . '</th>';
		echo '<th>' . __( 'Response', 'query_monitor' ) . '</th>';
		echo '<th>' . __( 'Function', 'query_monitor' ) . '</th>';
		echo '<th>' . __( 'Timeout', 'query_monitor' ) . '</th>';
		echo '<th>' . __( 'Time', 'query_monitor' ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		if ( !empty( $data['http'] ) ) {

			foreach ( $data['http'] as $row ) {
				$funcs = array();

				if ( isset( $row['response'] ) ) {

					$ltime = ( $row['end'] - $row['start'] );
					$total_time += $ltime;
					$stime = number_format_i18n( $ltime, 4 );
					$ltime = number_format_i18n( $ltime, 10 );

					if ( is_wp_error( $row['response'] ) ) {
						$response = $row['response']->get_error_message();
						$css = 'qm-warn';
					} else {
						$response = wp_remote_retrieve_response_code( $row['response'] );
						$msg = wp_remote_retrieve_response_message( $row['response'] );
						if ( 200 === intval( $response ) )
							$response = esc_html( $response . ' ' . $msg );
						else if ( !empty( $response ) )
							$response = '<span class="qm-warn">' . esc_html( $response . ' ' . $msg ) . '</span>';
						else
							$response = __( 'n/a', 'query_monitor' );
						$css = '';
					}

				} else {

					$ltime = '';
					$total_time += $row['args']['timeout'];
					$stime = number_format_i18n( $row['args']['timeout'], 4 );
					$response = __( 'Request timed out', 'query_monitor' );
					$css = 'qm-warn';

				}

				$method = $row['args']['method'];
				if ( isset( $row['transport'] ) )
					$method .= '<br />' . sprintf( _x( '(using %s)', 'using HTTP transport', 'query_monitor' ), $row['transport'] );
				if ( !$row['args']['blocking'] )
					$method .= '<br />' . _x( '(non-blocking)', 'non-blocking HTTP transport', 'query_monitor' );
				$url = str_replace( array(
					'&',
					'?',
					'=',
				), array(
					'<br /><span>&nbsp;&amp;&nbsp;</span>',
					'<br /><span>&nbsp;?&nbsp;</span>',
					'<span>&nbsp;=&nbsp;</span>',
				), $row['url'] );
				unset( $row['trace'][0], $row['trace'][1], $row['trace'][2], $row['trace'][3] );
				$f = 4;
				$func = $row['trace'][$f];
				if ( 0 === strpos( $func, 'SimplePie' ) )
					$func = $row['trace'][++$f];
				if ( 0 === strpos( $func, 'fetch_feed' ) )
					$func = $row['trace'][++$f];
				$funcs = esc_attr( implode( ', ', array_reverse( $row['trace'] ) ) );
				echo "
					<tr class='{$css}'>\n
						<td valign='top' class='qm-url qm-ltr'>{$url}</td>\n
						<td valign='top'>{$method}</td>\n
						<td valign='top'>{$response}</td>\n
						<td valign='top' title='{$funcs}' class='qm-ltr'>{$func}</td>\n
						<td valign='top'>{$row['args']['timeout']}</td>\n
						<td valign='top' title='{$ltime}'>{$stime}</td>\n
					</tr>\n
				";
			}

			$total_stime = number_format_i18n( $total_time, 4 );
			$total_ltime = number_format_i18n( $total_time, 10 );

			echo '<tr>';
			echo '<td colspan="5">&nbsp;</td>';
			echo "<td title='{$total_ltime}'>{$total_stime}</td>";
			echo '</tr>';

		} else {

			echo '<tr>';
			echo '<td colspan="6" style="text-align:center !important"><em>' . __( 'none', 'query_monitor' ) . '</em></td>';
			echo '</tr>';
	
		}

		echo '</tbody>';
		echo '</table>';

	}

}

function register_qm_http( $qm ) {
	$qm['http'] = new QM_HTTP;
	return $qm;
}

add_filter( 'query_monitor_components', 'register_qm_http', 110 );

?>