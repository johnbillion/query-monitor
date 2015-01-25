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

class QM_Output_Html_Assets extends QM_Output_Html {

	public $id = 'hooks';

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'query_monitor_menus', array( $this, 'admin_menu' ), 70 );
	}

	public function output() {

		$data = $this->collector->get_data();

		if ( empty( $data ) ) {
			return;
		}

		echo '<div class="qm" id="' . esc_attr( $this->collector->id() ) . '">';
		echo '<table cellspacing="0">';
		echo '<thead>';
		echo '<tr>';
		echo '<th colspan="2">' . esc_html( $this->collector->name() ) . '</th>';
		echo '<th>' . __( 'Dependencies', 'query-monitor' ) . '</th>';
	//	echo '<th>' . __( 'Component', 'query-monitor' ) . '</th>';
		echo '<th>' . __( 'Version', 'query-monitor' ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		// @TODO concat, do_concat, concat_version

		$rowspan = count( $data['header_scripts'] );

		echo '<tr>';
		echo "<td valign='top' rowspan='{$rowspan}'>" . __( 'Header&nbsp;Scripts', 'query-monitor' ) . "</td>";	

		$this->dependency_rows( $data['header_scripts'], $data['raw_scripts'] );

		$rowspan = count( $data['footer_scripts'] );

		echo '<tr>';
		echo "<td valign='top' rowspan='{$rowspan}'>" . __( 'Footer&nbsp;Scripts', 'query-monitor' ) . "</td>";	

		$this->dependency_rows( $data['footer_scripts'], $data['raw_scripts'] );

		$rowspan = count( $data['header_styles'] );

		echo '<tr>';
		echo "<td valign='top' rowspan='{$rowspan}'>" . __( 'Header&nbsp;Styles', 'query-monitor' ) . "</td>";	

		$this->dependency_rows( $data['header_styles'], $data['raw_styles'] );

		$rowspan = count( $data['footer_styles'] );

		echo '<tr>';
		echo "<td valign='top' rowspan='{$rowspan}'>" . __( 'Footer&nbsp;Styles', 'query-monitor' ) . "</td>";	

		$this->dependency_rows( $data['footer_styles'], $data['raw_styles'] );

		echo '</tbody>';
		echo '</table>';
		echo '</div>';

	}

	protected function dependency_rows( array $handles, WP_Dependencies $dependencies ) {

		$first = true;

		if ( empty( $handles ) ) {
			echo '<td valign="top" colspan="3"><em>' . __( 'none', 'query-monitor' ) . '</em></td>';
			echo '</tr>';
			return;
		}

		foreach ( $handles as $handle ) {
			if ( !$first ) {
				echo '<tr>';
			}

			$this->dependency_row( $dependencies->registered[$handle], $dependencies );

			echo '</tr>';
			$first = false;
		}

	}

	protected function dependency_row( _WP_Dependency $script, WP_Dependencies $dependencies ) {

	//	$path = $script->src;

	//	if ( preg_match( '#^//#', $path ) ) {
	//		$path = is_ssl() ? 'https:' . $path : 'http:' . $path;
	//	} else if ( preg_match( '#^/#', $path ) ) {
	//		$path = home_url( $path );
	//	} else {
	//		$path = set_url_scheme( $path );
	//	}

	//	$path      = str_replace( set_url_scheme( WP_PLUGIN_URL ), WP_PLUGIN_DIR, $path );
	//	$path      = str_replace( set_url_scheme( WP_CONTENT_URL ), WP_CONTENT_DIR, $path );
	//	$component = QM_Util::get_file_component( $path );

		if ( empty( $script->ver ) ) {
			$ver = '<em class="qm-info">' . $dependencies->default_version . '</em>';
		} else {
			$ver = esc_html( $script->ver );
		}

		if ( empty( $script->src ) ) {
			$src = '&nbsp;';
		} else {
			$src = $script->src;
		}

		echo '<td valign="top">' . $script->handle . '<br><span class="qm-info">' . $src . '</span></td>';
		echo '<td valign="top">' . implode( '<br>', $script->deps ) . '</td>';
	//	echo '<td valign="top">' . $component->name . '</td>';
		echo '<td valign="top">' . $ver . '</td>';

	}

	public function admin_menu( array $menu ) {

		$menu[] = $this->menu( array(
			'title' => $this->collector->name(),
		) );
		return $menu;

	}

}

function register_qm_output_html_assets( QM_Output $output = null, QM_Collector $collector ) {
	return new QM_Output_Html_Assets( $collector );
}

add_filter( 'query_monitor_output_html_assets', 'register_qm_output_html_assets', 10, 2 );
