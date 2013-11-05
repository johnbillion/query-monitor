<?php

class QM_Component_PHP_Errors extends QM_Component {

	var $id = 'php_errors';

	function __construct() {

		parent::__construct();
		set_error_handler( array( $this, 'error_handler' ) );
		add_filter( 'query_monitor_menus', array( $this, 'admin_menu' ), 10 );
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

	function output_headers( array $args, array $data ) {

		if ( empty( $data['errors'] ) )
			return;

		$all_errors = array();

		foreach ( $data['errors'] as $type => $errors ) {

			foreach ( $errors as $key => $error ) {

				$all_errors[$key] = $key;

				header( sprintf( 'X-QM-Error-%s: %s',
					$key,
					json_encode( $error )
				) );

			}

		}

		header( sprintf( 'X-QM-Errors: %s',
			json_encode( $all_errors )
		) );

	}

	function output_html( array $args, array $data ) {

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
		echo '<th>' . __( 'Component', 'query-monitor' ) . '</th>';
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
				if ( count( $data['errors'][$type] ) > 1 )
					echo '<td rowspan="' . count( $data['errors'][$type] ) . '">' . $title . '</td>';
				else
					echo '<td>' . $title . '</td>';
				$first = true;

				foreach ( $data['errors'][$type] as $error ) {

					if ( !$first )
						echo '<tr>';

					$stack = $error->trace->get_stack();
					$component = QM_Util::get_backtrace_component( $error->trace );

					if ( empty( $stack ) )
						$stack = '<em>' . __( 'none', 'query-monitor' ) . '</em>';
					else
						$stack = implode( '<br />', $stack );

					$message = str_replace( "href='function.", "target='_blank' href='http://php.net/function.", $error->message );

					echo '<td>' . $message . '</td>';
					echo '<td title="' . esc_attr( $error->file ) . '">' . esc_html( $error->filename ) . '</td>';
					echo '<td>' . esc_html( $error->line ) . '</td>';
					echo '<td class="qm-ltr">' . $stack . '</td>';
					echo '<td>' . $component->name . '</td>';
					echo '</tr>';

					$first = false;

				}

			}

		}

		echo '</tbody>';
		echo '</table>';
		echo '</div>';

	}

	function error_handler( $errno, $message, $file = null, $line = null ) {

		#if ( !( error_reporting() & $errno ) )
		#	return false;

		switch ( $errno ) {

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

			$trace = new QM_Backtrace;
			$func  = reset( $trace->get_stack() );
			$key   = md5( $message . $file . $line . $func );

			$filename = QM_Util::standard_dir( $file, '' );

			if ( isset( $this->data['errors'][$type][$key] ) ) {
				$this->data['errors'][$type][$key]->calls++;
			} else {
				$this->data['errors'][$type][$key] = (object) array(
					'errno'    => $errno,
					'type'     => $type,
					'message'  => $message,
					'file'     => $file,
					'filename' => $filename,
					'line'     => $line,
					'trace'    => $trace,
					'calls'    => 1
				);
			}

		}

		# If Xdebug is enabled we'll return false so Xdebug's error handler can do its thing.
		if ( function_exists( 'xdebug_is_enabled' ) and xdebug_is_enabled() )
			return false;
		else
			return true;

	}

}

function register_qm_php_errors( array $qm ) {
	$qm['php_errors'] = new QM_Component_PHP_Errors;
	return $qm;
}

add_filter( 'query_monitor_components', 'register_qm_php_errors', 120 );
