<?php

class QM_PHP_Errors extends QM {

	var $id = 'php_errors';
	var $php_errors   = array();

	function __construct() {

		parent::__construct();

		set_error_handler( array( $this, 'error_handler' ) );

	}

	function output() {

		if ( empty( $this->php_errors ) )
			return;

		echo '<table class="qm" cellspacing="0" id="' . $this->id() . '">';
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

			if ( isset( $this->php_errors[$type] ) ) {

				echo '<tr>';
				echo '<td rowspan="' . count( $this->php_errors[$type] ) . '">' . $title . '</td>';
				$first = true;

				foreach ( $this->php_errors[$type] as $error ) {

					if ( !$first )
						echo '<tr>';

					$funca = implode( ', ', array_reverse( $error->funcs ) );
					$message = str_replace( "href='function.", "target='_blank' href='http://php.net/function.", $error->message );

					echo '<td>' . $message . '</td>';
					echo '<td>' . esc_html( $error->file ) . '</td>';
					echo '<td>' . esc_html( $error->line ) . '</td>';
					echo '<td title="' . esc_attr( $funca ) . '" class="qm-ltr">' . esc_html( $error->funcs[0] ) . '</td>';
					echo '</tr>';

					$first = false;

				}

			}

		}

		echo '</tbody>';
		echo '</table>';

	}

	function admin_menus() {

		$menus = array();

		if ( isset( $this->php_errors['warning'] ) ) {
			$menus[] = $this->menu( array(
				'id'    => 'query_monitor_warnings',
				'title' => sprintf( __( 'PHP Warnings (%s)', 'query_monitor' ), count( $this->php_errors['warning'] ) ) # 'TODO l18n number
			) );
		}

		if ( isset( $this->php_errors['notice'] ) ) {
			$menus[] = $this->menu( array(
				'id'    => 'query_monitor_notices',
				'title' => sprintf( __( 'PHP Notices (%s)', 'query_monitor' ), count( $this->php_errors['notice'] ) ) # 'TODO l18n number
			) );
		}

		if ( empty( $menus ) )
			return false;

		return $menus;

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

			if ( isset( $this->php_errors[$type][$key] ) ) {
				$this->php_errors[$type][$key]->calls++;
			} else {
				$this->php_errors[$type][$key] = (object) array(
					'type'    => $type,
					'message' => $message,
					'file'    => $file,
					'line'    => $line,
					'funcs'   => $funcs,
					'calls'   => 1
				);
			}

		}

		return true;

	}

}

?>