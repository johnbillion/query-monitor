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
		add_filter( 'qm/output/menus',      array( $this, 'admin_menu' ), 70 );
		add_filter( 'qm/output/menu_class', array( $this, 'admin_class' ) );
	}

	public function output() {

		$data = $this->collector->get_data();

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

			if ( !empty( $data["broken_{$type}"] ) ) {

				$rowspan = max( count( $data["broken_{$type}"] ), 1 );

				echo '<tr class="qm-warn">';
				echo "<td valign='top' rowspan='{$rowspan}' class='qm-nowrap'>" . __( 'Broken Dependencies', 'query-monitor' ) . "</td>";	

				$this->dependency_rows( $data["broken_{$type}"], $data["raw_{$type}"] );

			}

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
				if ( in_array( $handle, $dependencies->done ) ) {
					echo '<tr>';
				} else {
					echo '<tr class="qm-warn">';
				}
			}

			$this->dependency_row( $dependencies->query( $handle ), $dependencies );

			echo '</tr>';
			$first = false;
		}

	}

	protected function dependency_row( _WP_Dependency $script, WP_Dependencies $dependencies ) {

		if ( empty( $script->ver ) ) {
			$ver = '&nbsp;';
		} else {
			$ver = esc_html( $script->ver );
		}

		if ( empty( $script->src ) ) {
			$src = '&nbsp;';
		} else {
			$src = $script->src;
		}

		$dependents = self::get_dependents( $script, $dependencies );
		$deps = $script->deps;

		foreach ( $deps as & $dep ) {
			if ( ! $dependencies->query( $dep ) ) {
				$dep = sprintf( '%s (missing)', $dep );
			}
		}

		echo '<td valign="top" class="qm-wrap">' . $script->handle . '<br><span class="qm-info">' . $src . '</span></td>';
		echo '<td valign="top" class="qm-nowrap">' . implode( '<br>', $deps ) . '</td>';
		echo '<td valign="top" class="qm-nowrap">' . implode( '<br>', $dependents ) . '</td>';
		echo '<td valign="top">' . $ver . '</td>';

	}

	protected static function get_dependents( _WP_Dependency $script, WP_Dependencies $dependencies ) {

		// @TODO move this into the collector
		$dependents = array();

		foreach ( $dependencies->queue as $handle ) {
			$item = $dependencies->query( $handle );
			if ( in_array( $script->handle, $item->deps ) ) {
				$dependents[] = $handle;
			}
		}

		return $dependents;

	}

	public function admin_class( array $class ) {

		$data = $this->collector->get_data();

		if ( !empty( $data['broken_scripts'] ) or !empty( $data['broken_styles'] ) ) {
			$class[] = 'qm-error';
		}

		return $class;

	}

	public function admin_menu( array $menu ) {

		$data = $this->collector->get_data();
		$args = array(
			'title' => $this->collector->name()
		);

		if ( !empty( $data['broken_scripts'] ) or !empty( $data['broken_styles'] ) ) {
			$args['meta']['classname'] = 'qm-error';
		}

		$menu[] = $this->menu( $args );

		return $menu;

	}

}

function register_qm_output_html_assets( array $output, QM_Collectors $collectors ) {
	if ( $collector = $collectors::get( 'assets' ) ) {
		$output['assets'] = new QM_Output_Html_Assets( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_assets', 80, 2 );
