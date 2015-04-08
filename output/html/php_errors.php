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

class QM_Output_Html_PHP_Errors extends QM_Output_Html {

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 10 );
		add_filter( 'qm/output/menu_class', array( $this, 'admin_class' ) );
	}

	public function output() {

		$data = $this->collector->get_data();

		if ( empty( $data['errors'] ) ) {
			return;
		}

		echo '<div class="qm" id="' . esc_attr( $this->collector->id() ) . '">';
		echo '<table cellspacing="0">';
		echo '<thead>';
		echo '<tr>';
		echo '<th colspan="2">' . __( 'PHP Error', 'query-monitor' ) . '</th>';
		echo '<th class="qm-num">' . __( 'Count', 'query-monitor' ) . '</th>';
		echo '<th>' . __( 'Location', 'query-monitor' ) . '</th>';
		echo '<th>' . __( 'Call Stack', 'query-monitor' ) . '</th>';
		echo '<th>' . __( 'Component', 'query-monitor' ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		$types = array(
			'warning'    => __( 'Warning', 'query-monitor' ),
			'notice'     => __( 'Notice', 'query-monitor' ),
			'strict'     => __( 'Strict', 'query-monitor' ),
			'deprecated' => __( 'Deprecated', 'query-monitor' ),
		);

		foreach ( $types as $type => $title ) {

			if ( isset( $data['errors'][$type] ) ) {

				echo '<tr>';
				echo '<td rowspan="' . count( $data['errors'][$type] ) . '">' . $title . '</td>';
				$first = true;

				foreach ( $data['errors'][$type] as $error ) {

					if ( !$first ) {
						echo '<tr>';
					}

					$stack     = $error->trace->get_stack();
					$component = $error->trace->get_component();
					if ( $component ) {
						$name = $component->name;
					} else {
						$name = '<em>' . __( 'Unknown', 'query-monitor' ) . '</em>';
					}
					$stack     = implode( '<br>', $stack );
					$message   = str_replace( "href='function.", "target='_blank' href='http://php.net/function.", $error->message );

					$output = esc_html( $error->filename ) . ':' . $error->line;

					echo '<td>' . $message . '</td>';
					echo '<td>' . number_format_i18n( $error->calls ) . '</td>';
					echo '<td>';
					echo self::output_filename( $output, $error->file, $error->line );
					echo '</td>';
					echo '<td class="qm-nowrap qm-ltr">' . $stack . '</td>';
					echo '<td class="qm-nowrap">' . $name . '</td>';
					echo '</tr>';

					$first = false;

				}

			}

		}

		echo '</tbody>';
		echo '</table>';
		echo '</div>';

	}

	public function admin_class( array $class ) {

		$data = $this->collector->get_data();

		if ( isset( $data['errors']['warning'] ) ) {
			$class[] = 'qm-warning';
		} else if ( isset( $data['errors']['notice'] ) ) {
			$class[] = 'qm-notice';
		} else if ( isset( $data['errors']['strict'] ) ) {
			$class[] = 'qm-strict';
		} else if ( isset( $data['errors']['deprecated'] ) ) {
			$class[] = 'qm-deprecated';
		}

		return $class;

	}

	public function admin_menu( array $menu ) {

		$data = $this->collector->get_data();

		if ( isset( $data['errors']['warning'] ) ) {
			$menu[] = $this->menu( array(
				'id'    => 'query-monitor-warnings',
				'title' => sprintf( __( 'PHP Warnings (%s)', 'query-monitor' ), number_format_i18n( count( $data['errors']['warning'] ) ) )
			) );
		}
		if ( isset( $data['errors']['notice'] ) ) {
			$menu[] = $this->menu( array(
				'id'    => 'query-monitor-notices',
				'title' => sprintf( __( 'PHP Notices (%s)', 'query-monitor' ), number_format_i18n( count( $data['errors']['notice'] ) ) )
			) );
		}
		if ( isset( $data['errors']['strict'] ) ) {
			$menu[] = $this->menu( array(
				'id'    => 'query-monitor-stricts',
				'title' => sprintf( __( 'PHP Stricts (%s)', 'query-monitor' ), number_format_i18n( count( $data['errors']['strict'] ) ) )
			) );
		}
		if ( isset( $data['errors']['deprecated'] ) ) {
			$menu[] = $this->menu( array(
				'id'    => 'query-monitor-deprecated',
				'title' => sprintf( __( 'PHP Deprecated (%s)', 'query-monitor' ), number_format_i18n( count( $data['errors']['deprecated'] ) ) )
			) );
		}
		return $menu;

	}

}

function register_qm_output_html_php_errors( array $output, QM_Collectors $collectors ) {
	if ( $collector = QM_Collectors::get( 'php_errors' ) ) {
		$output['php_errors'] = new QM_Output_Html_PHP_Errors( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_php_errors', 110, 2 );
