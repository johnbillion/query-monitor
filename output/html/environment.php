<?php
/*
Copyright 2009-2016 John Blackbourn

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

*/

class QM_Output_Html_Environment extends QM_Output_Html {

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 110 );
	}

	public function output() {

		$data = $this->collector->get_data();

		echo '<div id="' . esc_attr( $this->collector->id() ) . '">';

		echo '<div class="qm qm-half">';
		echo '<table cellspacing="0">';
		echo '<thead>';
		echo '<tr>';
		echo '<th colspan="2">PHP</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		echo '<tr>';
		echo '<td>version</td>';
		echo '<td>' . esc_html( $data['php']['version'] ) . '</td>';
		echo '</tr>';

		echo '<tr>';
		echo '<td>sapi</td>';
		echo '<td>' . esc_html( $data['php']['sapi'] ) . '</td>';
		echo '</tr>';

		if ( isset( $data['php']['hhvm'] ) ) {
			echo '<tr>';
			echo '<td>hhvm</td>';
			echo '<td>' . esc_html( $data['php']['hhvm'] ) . '</td>';
			echo '</tr>';
		}

		echo '<tr>';
		echo '<td>user</td>';
		if ( !empty( $data['php']['user'] ) ) {
			echo '<td>' . esc_html( $data['php']['user'] ) . '</td>';
		} else {
			echo '<td><em>' . esc_html__( 'Unknown', 'query-monitor' ) . '</em></td>';
		}
		echo '</tr>';

		foreach ( $data['php']['variables'] as $key => $val ) {

			echo '<tr>';
			echo '<td>' . esc_html( $key ) . '</td>';
			echo '<td>';
			echo esc_html( $val['after'] );

			if ( $val['after'] !== $val['before'] ) {
				printf(
					'<br><span class="qm-info">&nbsp;%s</span>',
					esc_html( sprintf(
						__( 'Overridden at runtime from %s', 'query-monitor' ),
						$val['before']
					) )
				);
			}

			echo '</td>';
			echo '</tr>';
		}

		$error_levels = implode( '<br>&nbsp;', array_map( 'esc_html', $this->collector->get_error_levels( $data['php']['error_reporting'] ) ) );

		echo '<tr>';
		echo '<td>error_reporting</td>';
		echo '<td>' . esc_html( $data['php']['error_reporting'] ) . '<br><span class="qm-info">&nbsp;';
		echo $error_levels; // WPCS: XSS ok.
		echo '</span></td>';
		echo '</tr>';

		echo '</tbody>';
		echo '</table>';
		echo '</div>';

		if ( isset( $data['db'] ) ) {

			foreach ( $data['db'] as $id => $db ) {

				if ( 1 === count( $data['db'] ) ) {
					$name = __( 'Database', 'query-monitor' );
				} else {
					$name = sprintf( __( 'Database: %s', 'query-monitor' ), $id );
				}

				echo '<div class="qm qm-half">';
				echo '<table cellspacing="0">';
				echo '<thead>';
				echo '<tr>';
				echo '<th colspan="2">' . esc_html( $name ) . '</th>';
				echo '</tr>';
				echo '</thead>';
				echo '<tbody>';

				foreach ( $db['info'] as $key => $value ) {

					echo '<tr>';
					echo '<td>' . esc_html( $key ) . '</td>';

					if ( ! isset( $value ) ) {
						echo '<td><span class="qm-warn">' . esc_html__( 'Unknown', 'query-monitor' ) . '</span></td>';
					} else {
						echo '<td>' . esc_html( $value ) . '</td>';
					}

					echo '</tr>';

				}

				echo '<tr>';

				$first  = true;
				$search = __( 'https://www.google.com/search?q=mysql+performance+%s', 'query-monitor' );

				foreach ( $db['variables'] as $setting ) {

					$key = $setting->Variable_name;
					$val = $setting->Value;
					$append = '';
					$show_warning = false;

					if ( ( true === $db['vars'][$key] ) and empty( $val ) ) {
						$show_warning = true;
					} else if ( is_string( $db['vars'][$key] ) and ( $val !== $db['vars'][$key] ) ) {
						$show_warning = true;
					}

					if ( $show_warning ) {
						$append .= sprintf(
							'&nbsp;<span class="qm-info">(<a href="%s" target="_blank">%s</a>)</span>',
							esc_url( sprintf( $search, $key ) ),
							esc_html__( 'Help', 'query-monitor' )
						);
					}

					if ( is_numeric( $val ) and ( $val >= ( 1024*1024 ) ) ) {
						$append .= sprintf(
							'<br><span class="qm-info">&nbsp;~%s</span>',
							esc_html( size_format( $val ) )
						);
					}

					$class = ( $show_warning ) ? 'qm-warn' : '';

					if ( !$first ) {
						echo '<tr class="' . esc_attr( $class ) . '"">';
					}

					echo '<td>' . esc_html( $key ) . '</td>';
					echo '<td>';
					echo esc_html( $val );
					echo $append; // WPCS: XSS ok.
					echo '</td>';

					echo '</tr>';

					$first = false;

				}

				echo '</tbody>';
				echo '</table>';
				echo '</div>';

			}

		}

		echo '<div class="qm qm-half qm-clear">';
		echo '<table cellspacing="0">';
		echo '<thead>';
		echo '<tr>';
		echo '<th colspan="2">WordPress</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		foreach ( $data['wp'] as $key => $val ) {

			echo '<tr>';
			echo '<td>' . esc_html( $key ) . '</td>';
			echo '<td>' . esc_html( $val ) . '</td>';
			echo '</tr>';

		}

		echo '</tbody>';
		echo '</table>';
		echo '</div>';

		echo '<div class="qm qm-half">';
		echo '<table cellspacing="0">';
		echo '<thead>';
		echo '<tr>';
		echo '<th colspan="2">' . esc_html__( 'Server', 'query-monitor' ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		echo '<tr>';
		echo '<td>' . esc_html__( 'software', 'query-monitor' ) . '</td>';
		echo '<td>' . esc_html( $data['server']['name'] ) . '</td>';
		echo '</tr>';

		echo '<tr>';
		echo '<td>' . esc_html__( 'version', 'query-monitor' ) . '</td>';
		if ( !empty( $data['server']['version'] ) ) {
			echo '<td>' . esc_html( $data['server']['version'] ) . '</td>';
		} else {
			echo '<td><em>' . esc_html__( 'Unknown', 'query-monitor' ) . '</em></td>';
		}
		echo '</tr>';

		echo '<tr>';
		echo '<td>' . esc_html__( 'address', 'query-monitor' ) . '</td>';
		if ( !empty( $data['server']['address'] ) ) {
			echo '<td>' . esc_html( $data['server']['address'] ) . '</td>';
		} else {
			echo '<td><em>' . esc_html__( 'Unknown', 'query-monitor' ) . '</em></td>';
		}
		echo '</tr>';

		echo '<tr>';
		echo '<td>' . esc_html__( 'host', 'query-monitor' ) . '</td>';
		echo '<td>' . esc_html( $data['server']['host'] ) . '</td>';
		echo '</tr>';

		echo '</tbody>';
		echo '</table>';
		echo '</div>';

		echo '</div>';

	}

}

function register_qm_output_html_environment( array $output, QM_Collectors $collectors ) {
	if ( $collector = QM_Collectors::get( 'environment' ) ) {
		$output['environment'] = new QM_Output_Html_Environment( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_environment', 120, 2 );
