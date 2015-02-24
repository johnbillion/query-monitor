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

		foreach ( array(
			'scripts' => __( 'Scripts', 'query-monitor' ),
			'styles'  => __( 'Styles', 'query-monitor' ),
		) as $type => $type_label ) {

			echo '<thead>';

			if ( 'scripts' != $type ) {
				echo '<tr class="qm-totally-legit-spacer">';
				echo '<td colspan="6"></td>';
				echo '</tr>';
			}

			echo '<tr>';
			echo '<th colspan="2">' . $type_label . '</th>';
			echo '<th>' . __( 'Dependencies', 'query-monitor' ) . '</th>';
			echo '<th>' . __( 'Dependents', 'query-monitor' ) . '</th>';
			echo '<th>' . __( 'Version', 'query-monitor' ) . '</th>';
			echo '</tr>';
			echo '</thead>';
			echo '<tbody>';

			foreach ( array(
				'header' => __( 'Header %s', 'query-monitor' ),
				'footer' => __( 'Footer %s', 'query-monitor' ),
			) as $position => $position_label ) {

				$rowspan = max( count( $data["{$position}_{$type}"] ), 1 );

				echo '<tr>';
				echo "<td valign='top' rowspan='{$rowspan}' class='qm-nowrap'>" . sprintf( $position_label, $type_label ) . "</td>";	

				$this->dependency_rows( $data["{$position}_{$type}"], $data["raw_{$type}"] );

			}

			echo '</tbody>';

		}

		echo '</table>';
		echo '</div>';

	}

	protected function dependency_rows( array $handles, WP_Dependencies $dependencies ) {

		$first = true;

		if ( empty( $handles ) ) {
			echo '<td valign="top" colspan="4"><em>' . __( 'none', 'query-monitor' ) . '</em></td>';
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

		$dependents = self::get_dependents( $script, $dependencies );

		echo '<td valign="top" class="qm-wrap">' . $script->handle . '<br><span class="qm-info">' . $src . '</span></td>';
		echo '<td valign="top" class="qm-nowrap">' . implode( '<br>', $script->deps ) . '</td>';
		echo '<td valign="top" class="qm-nowrap">' . implode( '<br>', $dependents ) . '</td>';
		echo '<td valign="top">' . $ver . '</td>';

	}

	protected static function get_dependents( _WP_Dependency $script, WP_Dependencies $dependencies ) {

		$dependents = array();

		foreach ( $dependencies->done as $handle ) {
			$item = $dependencies->query( $handle );
			if ( in_array( $script->handle, $item->deps ) ) {
				$dependents[] = $handle;
			}
		}

		return $dependents;

	}

}

function register_qm_output_html_assets( array $output, QM_Collectors $collectors ) {
	if ( $collector = $collectors::get( 'assets' ) ) {
		$output['assets'] = new QM_Output_Html_Assets( $collector );
	}
	return $output;
}

add_filter( 'query_monitor_output_html', 'register_qm_output_html_assets', 80, 2 );
