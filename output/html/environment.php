<?php
/*
Copyright 2009-2017 John Blackbourn

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

		echo '<div class="qm qm-third">';
		echo '<table cellspacing="0">';
		echo '<caption>PHP</caption>';
		echo '<thead class="screen-reader-text">';
		echo '<tr>';
		echo '<th scope="col">' . esc_html__( 'Property', 'query-monitor' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'Value', 'query-monitor' ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		echo '<tr>';
		echo '<th scope="row">version</th>';
		echo '<td>' . esc_html( $data['php']['version'] ) . '</td>';
		echo '</tr>';

		echo '<tr>';
		echo '<th scope="row">sapi</th>';
		echo '<td>' . esc_html( $data['php']['sapi'] ) . '</td>';
		echo '</tr>';

		if ( isset( $data['php']['hhvm'] ) ) {
			echo '<tr>';
			echo '<th scope="row">hhvm</th>';
			echo '<td>' . esc_html( $data['php']['hhvm'] ) . '</td>';
			echo '</tr>';
		}

		echo '<tr>';
		echo '<th scope="row">user</th>';
		if ( ! empty( $data['php']['user'] ) ) {
			echo '<td>' . esc_html( $data['php']['user'] ) . '</td>';
		} else {
			echo '<td><em>' . esc_html__( 'Unknown', 'query-monitor' ) . '</em></td>';
		}
		echo '</tr>';

		foreach ( $data['php']['variables'] as $key => $val ) {

			echo '<tr>';
			echo '<th scope="row">' . esc_html( $key ) . '</th>';
			echo '<td class="qm-wrap">';
			echo esc_html( $val['after'] );

			if ( $val['after'] !== $val['before'] ) {
				printf(
					'<br><span class="qm-info qm-supplemental">%s</span>',
					esc_html( sprintf(
						/* translators: %s: Original value of a variable */
						__( 'Overridden at runtime from %s', 'query-monitor' ),
						$val['before']
					) )
				);
			}

			echo '</td>';
			echo '</tr>';
		}

		$error_levels = $this->collector->get_error_levels( $data['php']['error_reporting'] );
		$out = array();

		foreach ( $error_levels as $level => $reported ) {
			if ( $reported ) {
				$out[] = esc_html( $level ) . '&nbsp;&#x2713;';
			} else {
				$out[] = '<span class="qm-false">' . esc_html( $level ) . '</span>';
			}
		}

		$error_levels = implode( '</li><li>', $out );

		echo '<tr>';
		echo '<th scope="row">' . esc_html__( 'Error Reporting', 'query-monitor' ) . '</th>';
		echo '<td class="qm-has-toggle qm-ltr"><div class="qm-toggler">';

		echo esc_html( $data['php']['error_reporting'] );
		echo $this->build_toggler(); // WPCS: XSS ok;

		echo '<div class="qm-toggled">';
		echo "<ul class='qm-supplemental'><li>{$error_levels}</li></ul>"; // WPCS: XSS ok.
		echo '</div>';

		echo '</div></td>';
		echo '</tr>';

		if ( ! empty( $data['php']['extensions'] ) ) {
			echo '<tr>';
			echo '<th scope="row">' . esc_html__( 'Extensions', 'query-monitor' ) . '</th>';
			echo '<td class="qm-has-toggle qm-ltr"><div class="qm-toggler">';

			echo esc_html( number_format_i18n( count( $data['php']['extensions'] ) ) );
			echo $this->build_toggler(); // WPCS: XSS ok;

			echo '<div class="qm-toggled"><ul class="qm-supplemental"><li>';
			echo implode( '</li><li>', array_map( 'esc_html', $data['php']['extensions'] ) );
			echo '</li></ul></div>';

			echo '</div></td>';
			echo '</tr>';
		}

		echo '</tbody>';
		echo '</table>';
		echo '</div>';

		if ( isset( $data['db'] ) ) {

			foreach ( $data['db'] as $id => $db ) {

				if ( 1 === count( $data['db'] ) ) {
					$name = __( 'Database', 'query-monitor' );
				} else {
					/* translators: %s: Name of database controller */
					$name = sprintf( __( 'Database: %s', 'query-monitor' ), $id );
				}

				echo '<div class="qm qm-third">';
				echo '<table cellspacing="0">';
				echo '<caption>' . esc_html( $name ) . '</caption>';
				echo '<thead class="screen-reader-text">';
				echo '<tr>';
				echo '<th scope="col">' . esc_html__( 'Property', 'query-monitor' ) . '</th>';
				echo '<th scope="col">' . esc_html__( 'Value', 'query-monitor' ) . '</th>';
				echo '</tr>';
				echo '</thead>';
				echo '<tbody>';

				foreach ( $db['info'] as $key => $value ) {

					echo '<tr>';
					echo '<th scope="row">' . esc_html( $key ) . '</th>';

					if ( ! isset( $value ) ) {
						echo '<td><span class="qm-warn">' . esc_html__( 'Unknown', 'query-monitor' ) . '</span></td>';
					} else {
						echo '<td class="qm-wrap">' . esc_html( $value ) . '</td>';
					}

					echo '</tr>';

				}

				echo '<tr>';

				$first  = true;
				$search = 'https://www.google.com/search?q=mysql+performance+%s';

				foreach ( $db['variables'] as $setting ) {

					$key = $setting->Variable_name;
					$val = $setting->Value;
					$append = '';
					$show_warning = false;

					if ( ( true === $db['vars'][ $key ] ) and empty( $val ) ) {
						$show_warning = true;
					} elseif ( is_string( $db['vars'][ $key ] ) and ( $val !== $db['vars'][ $key ] ) ) {
						$show_warning = true;
					}

					if ( $show_warning ) {
						$append .= sprintf(
							'&nbsp;<span class="qm-info">(<a href="%s" target="_blank">%s</a>)</span>',
							esc_url( sprintf( $search, urlencode( $key ) ) ),
							esc_html__( 'Help', 'query-monitor' )
						);
					}

					if ( is_numeric( $val ) and ( $val >= ( 1024 * 1024 ) ) ) {
						$append .= sprintf(
							'<br><span class="qm-info qm-supplemental">~%s</span>',
							esc_html( size_format( $val ) )
						);
					}

					$class = ( $show_warning ) ? 'qm-warn' : '';

					if ( ! $first ) {
						echo '<tr class="' . esc_attr( $class ) . '">';
					}

					echo '<th scope="row">' . esc_html( $key ) . '</th>';
					echo '<td class="qm-wrap">';
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

		echo '<div class="qm qm-third" style="float:right !important">';
		echo '<table cellspacing="0">';
		echo '<caption>WordPress</caption>';
		echo '<thead class="screen-reader-text">';
		echo '<tr>';
		echo '<th scope="col">' . esc_html__( 'Property', 'query-monitor' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'Value', 'query-monitor' ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		foreach ( $data['wp'] as $key => $val ) {

			echo '<tr>';
			echo '<th scope="row">' . esc_html( $key ) . '</th>';
			echo '<td class="qm-wrap">' . esc_html( $val ) . '</td>';
			echo '</tr>';

		}

		echo '</tbody>';
		echo '</table>';
		echo '</div>';

		echo '<div class="qm qm-third">';
		echo '<table cellspacing="0">';
		echo '<caption>' . esc_html__( 'Server', 'query-monitor' ) . '</caption>';
		echo '<thead class="screen-reader-text">';
		echo '<tr>';
		echo '<th scope="col">' . esc_html__( 'Property', 'query-monitor' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'Value', 'query-monitor' ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		echo '<tr>';
		echo '<th scope="row">' . esc_html__( 'software', 'query-monitor' ) . '</th>';
		echo '<td class="qm-wrap">' . esc_html( $data['server']['name'] ) . '</td>';
		echo '</tr>';

		echo '<tr>';
		echo '<th scope="row">' . esc_html__( 'version', 'query-monitor' ) . '</th>';
		if ( ! empty( $data['server']['version'] ) ) {
			echo '<td class="qm-wrap">' . esc_html( $data['server']['version'] ) . '</td>';
		} else {
			echo '<td><em>' . esc_html__( 'Unknown', 'query-monitor' ) . '</em></td>';
		}
		echo '</tr>';

		echo '<tr>';
		echo '<th scope="row">' . esc_html__( 'address', 'query-monitor' ) . '</th>';
		if ( ! empty( $data['server']['address'] ) ) {
			echo '<td class="qm-wrap">' . esc_html( $data['server']['address'] ) . '</td>';
		} else {
			echo '<td><em>' . esc_html__( 'Unknown', 'query-monitor' ) . '</em></td>';
		}
		echo '</tr>';

		echo '<tr>';
		echo '<th scope="row">' . esc_html__( 'host', 'query-monitor' ) . '</th>';
		echo '<td class="qm-wrap">' . esc_html( $data['server']['host'] ) . '</td>';
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
