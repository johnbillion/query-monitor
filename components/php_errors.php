<?php

class QM_PHP_Errors extends QM {

	var $id = 'php_errors';

	function __construct() {

		parent::__construct();
		set_error_handler( array( $this, 'error_handler' ) );
		add_filter( 'query_monitor_menus', array( $this, 'admin_menu' ), 20 );
		add_filter( 'query_monitor_class', array( $this, 'admin_class' ) );

	}

	function admin_class( $class ) {

		if ( isset( $this->data['errors']['notice'] ) )
			$class[] = 'qm-notice';
		if ( isset( $this->data['errors']['warning'] ) )
			$class[] = 'qm-warning';
		return $class;

	}

	function admin_menu( $menu ) {

		if ( isset( $this->data['errors']['warning'] ) ) {
			$menu[] = $this->menu( array(
				'id'    => 'query_monitor_warnings',
				'title' => sprintf( __( 'PHP Warnings (%s)', 'query_monitor' ), number_format_i18n( count( $this->data['errors']['warning'] ) ) )
			) );
		}
		if ( isset( $this->data['errors']['notice'] ) ) {
			$menu[] = $this->menu( array(
				'id'    => 'query_monitor_notices',
				'title' => sprintf( __( 'PHP Notices (%s)', 'query_monitor' ), number_format_i18n( count( $this->data['errors']['notice'] ) ) )
			) );
		}
		return $menu;

	}

	function output( $args, $data ) {

		if ( empty( $this->data['errors'] ) )
			return;

		echo '<div class="qm" id="' . $args['id'] . '">';
		echo '<table cellspacing="0">';
		echo '<thead>';
		echo '<tr>';
		echo '<th colspan="2">' . __( 'PHP Error', 'query_monitor' ) . '</th>';
		echo '<th>' . __( 'File', 'query_monitor' ) . '</th>';
		echo '<th>' . __( 'Line', 'query_monitor' ) . '</th>';
		echo '<th>' . __( 'Function', 'query_monitor' ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		$types = array(
			'warning' => __( 'Warning', 'query_monitor' ),
			'notice'  => __( 'Notice', 'query_monitor' )
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
					$func    = $error->funcs[2];
					$message = str_replace( "href='function.", "target='_blank' href='http://php.net/function.", $error->message );

					echo '<td>' . $message . '</td>';
					echo '<td title="' . esc_attr( $error->file ) . '">' . esc_html( $error->filename ) . '</td>';
					echo '<td>' . esc_html( $error->line ) . '</td>';
					echo '<td title="' . esc_attr( $funca ) . '" class="qm-ltr">' . esc_html( $func ) . '</td>';
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

		switch ( $type ) {

			case E_WARNING:
			case E_USER_WARNING:
				$type = 'warning';
				break;

			case E_NOTICE:
			case E_USER_NOTICE:
				$type = 'notice';
				break;

			default:
				return false;
				break;

		}

		if ( error_reporting() > 0 ) {

			$funcs = $this->backtrace();

			if ( !isset( $funcs[0] ) )
				$funcs[0] = '';

			$key = md5( $message . $file . $line . $funcs[0] );

			$filename = str_replace( '\\', '/', $file );
			$path     = str_replace( '\\', '/', ABSPATH );
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

		}

		return true;

	}

}

function register_qm_php_errors( $qm ) {
	$qm['php_errors'] = new QM_PHP_Errors;
	return $qm;
}

add_filter( 'query_monitor_components', 'register_qm_php_errors', 120 );

?>