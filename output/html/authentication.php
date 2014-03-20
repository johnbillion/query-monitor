<?php
/*

Copyright 2014 John Blackbourn

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

*/

class QM_Output_Html_Authentication extends QM_Output_Html {

	public function output() {

		echo '<div class="qm qm-half" id="' . $this->collector->id() . '">';
		echo '<table cellspacing="0">';
		echo '<thead>';
		echo '<tr>';
		echo '<th>' . $this->collector->name() . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		$atts = $this->collector->get_cookie_attributes();

		if ( !$this->collector->user_verified() ) {

			$cookie = $this->collector->get_cookie_content();
			$text   = esc_js( __( 'Authentication cookie set. You can now view Query Monitor output while logged out or while logged in as a different user.', 'query-monitor' ) );
			$value  = "{$atts['name']}={$cookie['value']}; domain={$atts['domain']}; path={$atts['path']}";
			if ( $cookie['secure'] ) {
				$value .= '; secure';
			}
			$link = "document.cookie='{$value}'; alert('{$text}'); return false;";

			echo '<tr>';
			echo '<td>' . __( 'You can set an authentication cookie which allows you to view Query Monitor output when you&rsquo;re not logged in.', 'query-monitor' ) . '</td>';
			echo '</tr>';
			echo '<tr>';
			echo '<td><a href="#" onclick="' . $link . '">' . __( 'Set authentication cookie', 'query-monitor' ) . '</a></td>';
			echo '</tr>';

		} else {

			$text  = esc_js( __( 'Authentication cookie cleared.', 'query-monitor' ) );
			$value = "{$atts['name']}=; expires=' + new Date(0).toUTCString() + '; domain={$atts['domain']}; path={$atts['path']}";
			$link  = "document.cookie='{$value}'; alert('{$text}'); return false;";

			echo '<tr>';
			echo '<td>' . __( 'You currently have an authentication cookie which allows you to view Query Monitor output.', 'query-monitor' ) . '</td>';
			echo '</tr>';
			echo '<tr>';
			echo '<td><a href="#" onclick="' . $link . '">' . __( 'Clear authentication cookie', 'query-monitor' ) . '</a></td>';
			echo '</tr>';

		}

		echo '</tbody>';
		echo '</table>';
		echo '</div>';

	}

}

function register_qm_output_html_authentication( QM_Output $output = null, QM_Collector $collector ) {
	return new QM_Output_Html_Authentication( $collector );
}

add_filter( 'query_monitor_output_html_authentication', 'register_qm_output_html_authentication', 10, 2 );
