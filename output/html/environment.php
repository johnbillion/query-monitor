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

class QM_Output_Html_Environment extends QM_Output_Html {

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'query_monitor_menus', array( $this, 'admin_menu' ), 100 );
	}

	public function output() {

		$data = $this->collector->get_data();

		echo '<div id="' . $this->collector->id() . '">';

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
		echo "<td>{$data['php']['version']}</td>";
		echo '</tr>';
		echo '<tr>';
		echo '<td>user</td>';
		echo "<td>{$data['php']['user']}</td>";
		echo '</tr>';

		foreach ( $data['php']['variables'] as $key => $val ) {

			$append = '';

			if ( $val['after'] != $val['before'] )
				$append .= '<br><span class="qm-info">' . sprintf( __( 'Overridden at runtime from %s', 'query-monitor' ), $val['before'] ) . '</span>';

			echo '<tr>';
			echo "<td>{$key}</td>";
			echo "<td>{$val['after']}{$append}</td>";
			echo '</tr>';
		}

		$error_levels = implode( '<br>', $this->collector->get_error_levels( $data['php']['error_reporting'] ) );

		echo '<tr>';
		echo '<td>error_reporting</td>';
		echo "<td>{$data['php']['error_reporting']}<br><span class='qm-info'>{$error_levels}</span></td>";
		echo '</tr>';

		echo '</tbody>';
		echo '</table>';
		echo '</div>';

		if ( isset( $data['db'] ) ) {

			foreach ( $data['db'] as $id => $db ) {

				if ( 1 == count( $data['db'] ) )
					$name = 'MySQL';
				else
					$name = 'MySQL: ' . $id;

				echo '<div class="qm qm-half">';
				echo '<table cellspacing="0">';
				echo '<thead>';
				echo '<tr>';
				echo '<th colspan="2">' . esc_html( $name ) . '</th>';
				echo '</tr>';
				echo '</thead>';
				echo '<tbody>';

				echo '<tr>';
				echo '<td>version</td>';
				echo '<td>' . $db['version'] . '</td>';
				echo '</tr>';

				echo '<tr>';
				echo '<td>driver</td>';
				echo '<td>' . $db['driver'] . '</td>';
				echo '</tr>';

				echo '<tr>';
				echo '<td>user</td>';
				echo '<td>' . $db['user'] . '</td>';
				echo '</tr>';

				echo '<tr>';
				echo '<td>host</td>';
				echo '<td>' . $db['host'] . '</td>';
				echo '</tr>';

				echo '<tr>';
				echo '<td>database</td>';
				echo '<td>' . $db['name'] . '</td>';
				echo '</tr>';

				echo '<tr>';

				$first  = true;
				$warn   = __( "This value may not be optimal. Check the recommended configuration for '%s'.", 'query-monitor' );
				$search = __( 'https://www.google.com/search?q=mysql+performance+%s', 'query-monitor' );

				foreach ( $db['variables'] as $setting ) {

					$key = $setting->Variable_name;
					$val = $setting->Value;
					$prepend = '';
					$show_warning = false;

					if ( ( true === $db['vars'][$key] ) and empty( $val ) )
						$show_warning = true;
					else if ( is_string( $db['vars'][$key] ) and ( $val !== $db['vars'][$key] ) )
						$show_warning = true;

					if ( $show_warning )
						$prepend .= '&nbsp;<span class="qm-info">(<a href="' . esc_url( sprintf( $search, $key ) ) . '" target="_blank" title="' . esc_attr( sprintf( $warn, $key ) ) . '">' . __( 'Help', 'query-monitor' ) . '</a>)</span>';

					if ( is_numeric( $val ) and ( $val >= ( 1024*1024 ) ) )
						$prepend .= '<br><span class="qm-info">~' . size_format( $val ) . '</span>';

					$class = ( $show_warning ) ? 'qm-warn' : '';

					if ( !$first )
						echo "<tr class='{$class}'>";

					$key = esc_html( $key );
					$val = esc_html( $val );

					echo "<td>{$key}</td>";
					echo "<td>{$val}{$prepend}</td>";

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
			echo "<td>{$key}</td>";
			echo "<td>{$val}</td>";
			echo '</tr>';

		}

		echo '</tbody>';
		echo '</table>';
		echo '</div>';

		echo '<div class="qm qm-half">';
		echo '<table cellspacing="0">';
		echo '<thead>';
		echo '<tr>';
		echo '<th colspan="2">' . __( 'Server', 'query-monitor' ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		echo '<tr>';
		echo '<td>software</td>';
		echo "<td>{$data['server']['name']}</td>";
		echo '</tr>';

		echo '<tr>';
		echo '<td>version</td>';
		echo "<td>{$data['server']['version']}</td>";
		echo '</tr>';

		echo '<tr>';
		echo '<td>address</td>';
		echo "<td>{$data['server']['address']}</td>";
		echo '</tr>';

		echo '<tr>';
		echo '<td>host</td>';
		echo "<td>{$data['server']['host']}</td>";
		echo '</tr>';

		echo '</tbody>';
		echo '</table>';
		echo '</div>';

		echo '</div>';

	}

	public function admin_menu( array $menu ) {

		$menu[] = $this->menu( array(
			'title' => __( 'Environment', 'query-monitor' )
		) );
		return $menu;

	}

}

function register_qm_output_html_environment( QM_Output $output = null, QM_Collector $collector ) {
	return new QM_Output_Html_Environment( $collector );
}

add_filter( 'query_monitor_output_html_environment', 'register_qm_output_html_environment', 10, 2 );
