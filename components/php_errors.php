<?php

class QM_Component_PHP_Errors extends QM_Component {

	var $id = 'php_errors';
	var $all_errors = array();

	function __construct() {

		parent::__construct();
		set_error_handler( array( $this, 'error_handler' ) );
		add_filter( 'query_monitor_menus', array( $this, 'admin_menu' ), 20 );
		add_filter( 'query_monitor_class', array( $this, 'admin_class' ) );

	}

	function admin_class( array $class ) {

		if ( isset( $this->data['errors']['warning'] ) )
			$class[] = 'qm-warning';
		else if ( isset( $this->data['errors']['notice'] ) )
			$class[] = 'qm-notice';
		else if ( isset( $this->data['errors']['strict'] ) )
			$class[] = 'qm-strict';

		return $class;

	}

	function admin_menu( array $menu ) {

		if ( isset( $this->data['errors']['warning'] ) ) {
			$menu[] = $this->menu( array(
				'id'    => 'query-monitor-warnings',
				'title' => sprintf( __( 'PHP Warnings (%s)', 'query-monitor' ), number_format_i18n( count( $this->data['errors']['warning'] ) ) )
			) );
		}
		if ( isset( $this->data['errors']['notice'] ) ) {
			$menu[] = $this->menu( array(
				'id'    => 'query-monitor-notices',
				'title' => sprintf( __( 'PHP Notices (%s)', 'query-monitor' ), number_format_i18n( count( $this->data['errors']['notice'] ) ) )
			) );
		}
		if ( isset( $this->data['errors']['strict'] ) ) {
			$menu[] = $this->menu( array(
				'id'    => 'query-monitor-stricts',
				'title' => sprintf( __( 'PHP Stricts (%s)', 'query-monitor' ), number_format_i18n( count( $this->data['errors']['strict'] ) ) )
			) );
		}
		return $menu;

	}

	function output( array $args, array $data ) {

		if ( empty( $data['errors'] ) )
			return;

		echo '<div class="qm" id="' . $args['id'] . '">';
		echo '<table cellspacing="0">';
		echo '<thead>';
		echo '<tr>';
		echo '<th colspan="2">' . __( 'PHP Error', 'query-monitor' ) . '</th>';
		echo '<th>' . __( 'File', 'query-monitor' ) . '</th>';
		echo '<th>' . __( 'Line', 'query-monitor' ) . '</th>';
		echo '<th>' . __( 'Call Stack', 'query-monitor' ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		$types = array(
			'warning' => __( 'Warning', 'query-monitor' ),
			'notice'  => __( 'Notice', 'query-monitor' ),
			'strict'  => __( 'Strict', 'query-monitor' ),
		);

		foreach ( $types as $type => $title ) {

			if ( isset( $data['errors'][$type] ) ) {

				echo '<tr>';
				echo '<td rowspan="' . count( $data['errors'][$type] ) . '">' . $title . '</td>';
				$first = true;

				foreach ( $data['errors'][$type] as $error ) {

					if ( !$first )
						echo '<tr>';

					$funca = $error->funcs;
					unset( $funca[0], $funca[1] );

					$funca   = implode( ', ', array_reverse( $funca ) );
					$stack   = $error->funcs;
					unset( $stack[0], $stack[1] ); # QM functions
					$stack   = implode( '<br />', $stack );
					$message = str_replace( "href='function.", "target='_blank' href='http://php.net/function.", $error->message );

					echo '<td>' . $message . '</td>';
					echo '<td title="' . esc_attr( $error->file ) . '">' . esc_html( $error->filename ) . '</td>';
					echo '<td>' . esc_html( $error->line ) . '</td>';
					echo '<td class="qm-ltr">' . $stack . '</td>';
					echo '</tr>';

					$first = false;

				}

			}

		}

		echo '</tbody>';
		echo '</table>';
		echo '</div>';

	}

	function error_handler( $type, $message, $file = null, $line = null ) {

		global $querymonitor;

		switch ( $type ) {

			case E_WARNING:
			case E_USER_WARNING:
				$type = 'warning';
				break;

			case E_NOTICE:
			case E_USER_NOTICE:
				$type = 'notice';
				break;

			case E_STRICT:
				$type = 'strict';
				break;

			default:
				return false;
				break;

		}

		if ( error_reporting() > 0 ) {

			$funcs = QM_Util::backtrace();

			if ( !isset( $funcs[0] ) )
				$funcs[0] = '';

			$key = md5( $message . $file . $line . $funcs[0] );

			$filename = QM_Util::standard_dir( $file );
			$path     = QM_Util::standard_dir( ABSPATH );
			$filename = str_replace( $path, '', $filename );

			if ( isset( $this->data['errors'][$type][$key] ) ) {
				$this->data['errors'][$type][$key]->calls++;
			} else {
				$this->data['errors'][$type][$key] = (object) array(
					'type'     => $type,
					'message'  => $message,
					'file'     => $file,
					'filename' => $filename,
					'line'     => $line,
					'funcs'    => $funcs,
					'calls'    => 1
				);
			}

			if ( QM_Util::is_ajax() and !headers_sent() and $querymonitor->show_query_monitor() ) {

				$this->all_errors[$key] = $key;

				header( sprintf( 'X-QM-Errors: %s',
					json_encode( $this->all_errors )
				), true );

				header( sprintf( 'X-QM-Error-%s: %s',
					$key,
					json_encode( $this->data['errors'][$type][$key] )
				), true );

			}

		}

		return true;

	}

}

function register_qm_php_errors( array $qm ) {

	# Don't handle errors if Xdebug is installed:
	$handle = apply_filters( 'qm_handle_php_errors', !function_exists( 'xdebug_start_error_collection' ) );

	if ( $handle )
		$qm['php_errors'] = new QM_Component_PHP_Errors;

	return $qm;

}

add_filter( 'query_monitor_components', 'register_qm_php_errors', 120 );

?>
