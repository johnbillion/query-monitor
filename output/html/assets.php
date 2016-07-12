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

			if ( 'scripts' !== $type ) {
				echo '<tr class="qm-totally-legit-spacer">';
				echo '<td colspan="6"></td>';
				echo '</tr>';
			}

			echo '<tr>';
			echo '<th colspan="2">' . esc_html( $type_label ) . '</th>';
			echo '<th>' . esc_html__( 'Dependencies', 'query-monitor' ) . '</th>';
			echo '<th>' . esc_html__( 'Dependents', 'query-monitor' ) . '</th>';
			echo '<th>' . esc_html__( 'Version', 'query-monitor' ) . '</th>';
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
					$this->dependency_rows( $data[ $position ][ $type ], $data['raw'][ $type ], sprintf( $position_label, $type_label ), $type );
				}

			}

			echo '</tbody>';

		}

		echo '</table>';
		echo '</div>';

	}

	protected function dependency_rows( array $handles, WP_Dependencies $dependencies, $label, $type ) {

		$first = true;

		if ( empty( $handles ) ) {
			echo '<tr>';
			echo '<td class="qm-nowrap">' . esc_html( $label ) . '</td>';
			echo '<td colspan="5"><em>' . esc_html__( 'none', 'query-monitor' ) . '</em></td>';
			echo '</tr>';
			return;
		}

		foreach ( $handles as $handle ) {

			if ( in_array( $handle, $dependencies->done ) ) {
				echo '<tr data-qm-subject="' . esc_attr( $type . '-' . $handle ) . '">';
			} else {
				echo '<tr data-qm-subject="' . esc_attr( $type . '-' . $handle ) . '" class="qm-warn">';
			}

			if ( $first ) {
				$rowspan = count( $handles );
				echo '<th rowspan="' . esc_attr( $rowspan ) . '" class="qm-nowrap">' . esc_html( $label ) . '</th>';
			}

			$this->dependency_row( $dependencies->query( $handle ), $dependencies, $type );

			echo '</tr>';
			$first = false;
		}

	}

	protected function dependency_row( _WP_Dependency $dependency, WP_Dependencies $dependencies, $type ) {

		if ( empty( $dependency->ver ) ) {
			$ver = '';
		} else {
			$ver = $dependency->ver;
		}

		/**
		 * Filter the script loader source.
		 *
		 * @param string $src    Script loader source path.
		 * @param string $handle Script handle.
		 */
		$source = apply_filters( 'script_loader_src', $dependency->src, $dependency->handle );

		if ( is_wp_error( $source ) ) {
			$src = $source->get_error_message();
			if ( ( $error_data = $source->get_error_data() ) && isset( $error_data['src'] ) ) {
				$src .= ' (' . $error_data['src'] . ')';
			}
		} elseif ( empty( $source ) ) {
			$src = '';
		} else {
			$src = $source;
		}

		$dependents = self::get_dependents( $dependency, $dependencies, $type );
		$deps = $dependency->deps;
		sort( $deps );

		foreach ( $deps as & $dep ) {
			if ( ! $dependencies->query( $dep ) ) {
				$dep = sprintf( __( '%s (missing)', 'query-monitor' ), $dep );
			}
		}

		$this->type = $type;

		$highlight_deps       = array_map( array( $this, '_prefix_type' ), $deps );
		$highlight_dependents = array_map( array( $this, '_prefix_type' ), $dependents );

		echo '<td class="qm-wrap">' . esc_html( $dependency->handle ) . '<br><span class="qm-info">&nbsp;';
		if ( is_wp_error( $source ) ) {
			printf( '<span class="qm-warn">%s</span>',
				esc_html( $src )
			);
		} else {
			echo esc_html( $src );
		}
		echo '</span></td>';
		echo '<td class="qm-nowrap qm-highlighter" data-qm-highlight="' . esc_attr( implode( ' ', $highlight_deps ) ) . '">' . implode( '<br>', array_map( 'esc_html', $deps ) ) . '</td>';
		echo '<td class="qm-nowrap qm-highlighter" data-qm-highlight="' . esc_attr( implode( ' ', $highlight_dependents ) ) . '">' . implode( '<br>', array_map( 'esc_html', $dependents ) ) . '</td>';
		echo '<td>' . esc_html( $ver ) . '</td>';

	}

	public function _prefix_type( $val ) {
		return $this->type . '-' . $val;
	}

	protected static function get_dependents( _WP_Dependency $dependency, WP_Dependencies $dependencies, $type ) {

		// @TODO move this into the collector
		$dependents = array();
		$handles    = array_unique( array_merge( $dependencies->queue, $dependencies->done ) );

		foreach ( $handles as $handle ) {
			if ( $item = $dependencies->query( $handle ) ) {
				if ( in_array( $dependency->handle, $item->deps ) ) {
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
			'title' => esc_html( $this->collector->name() ),
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
