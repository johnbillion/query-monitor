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

		if ( empty( $data['raw'] ) ) {
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
				'missing' => __( 'Missing %s', 'query-monitor' ),
				'broken'  => __( 'Broken Dependencies', 'query-monitor' ),
				'header'  => __( 'Header %s', 'query-monitor' ),
				'footer'  => __( 'Footer %s', 'query-monitor' ),
			) as $position => $position_label ) {

				if ( isset( $data[ $position ][ $type ] ) ) {
					$this->dependency_rows( $data[ $position ][ $type ], $data['raw'][ $type ], sprintf( $position_label, $type_label ) );
				}

			}

			echo '</tbody>';

		}

		echo '</table>';
		echo '</div>';

	}

	protected function dependency_rows( array $handles, WP_Dependencies $dependencies, $label ) {

		$first = true;

		if ( empty( $handles ) ) {
			echo '<tr>';
			echo '<td valign="top" class="qm-nowrap">' . $label . '</td>';	
			echo '<td valign="top" colspan="5"><em>' . __( 'none', 'query-monitor' ) . '</em></td>';
			echo '</tr>';
			return;
		}

		foreach ( $handles as $handle ) {

			if ( in_array( $handle, $dependencies->done ) ) {
				echo '<tr data-qm-subject="' . $handle . '">';
			} else {
				echo '<tr data-qm-subject="' . $handle . '" class="qm-warn">';
			}

			if ( $first ) {
				$rowspan = count( $handles );
				echo "<th valign='top' rowspan='{$rowspan}' class='qm-nowrap'>" . $label . "</th>";	
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
		sort( $deps );

		foreach ( $deps as & $dep ) {
			if ( ! $dependencies->query( $dep ) ) {
				$dep = sprintf( __( '%s (missing)', 'query-monitor' ), $dep );
			}
		}

		echo '<td valign="top" class="qm-wrap">' . $script->handle . '<br><span class="qm-info">' . $src . '</span></td>';
		echo '<td valign="top" class="qm-nowrap qm-highlighter" data-qm-highlight="' . implode( ' ', $deps ) . '">' . implode( '<br>', $deps ) . '</td>';
		echo '<td valign="top" class="qm-nowrap qm-highlighter" data-qm-highlight="' . implode( ' ', $dependents ) . '">' . implode( '<br>', $dependents ) . '</td>';
		echo '<td valign="top">' . $ver . '</td>';

	}

	protected static function get_dependents( _WP_Dependency $script, WP_Dependencies $dependencies ) {

		// @TODO move this into the collector
		$dependents = array();
		$handles    = array_unique( array_merge( $dependencies->queue, $dependencies->done ) );

		foreach ( $handles as $handle ) {
			if ( $item = $dependencies->query( $handle ) ) {
				if ( in_array( $script->handle, $item->deps ) ) {
					$dependents[] = $handle;
				}
			}
		}

		sort( $dependents );

		return $dependents;

	}

	public function admin_class( array $class ) {

		$data = $this->collector->get_data();

		if ( !empty( $data['broken'] ) or !empty( $data['missing'] ) ) {
			$class[] = 'qm-error';
		}

		return $class;

	}

	public function admin_menu( array $menu ) {

		$data = $this->collector->get_data();
		$args = array(
			'title' => $this->collector->name()
		);

		if ( !empty( $data['broken'] ) or !empty( $data['missing'] ) ) {
			$args['meta']['classname'] = 'qm-error';
		}

		$menu[] = $this->menu( $args );

		return $menu;

	}

}

function register_qm_output_html_assets( array $output, QM_Collectors $collectors ) {
	if ( $collector = QM_Collectors::get( 'assets' ) ) {
		$output['assets'] = new QM_Output_Html_Assets( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_assets', 80, 2 );
