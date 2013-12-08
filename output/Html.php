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

class QM_Output_Html implements QM_Output {

	public function __construct( QM_Collector $collector ) {
		$this->collector = $collector;
	}

	public function output() {

		$data = $this->collector->get_data();
		$name = $this->collector->name();

		if ( empty( $data ) )
			return;

		echo '<div class="qm" id="' . $this->collector->id() . '">';
		echo '<table cellspacing="0">';
		if ( !empty( $name ) ) {
			echo '<thead>';
			echo '<tr>';
			echo '<th colspan="2">' . $name . '</th>';
			echo '</tr>';
			echo '</thead>';
		}
		echo '<tbody>';

		foreach ( $data as $key => $value ) {
			echo '<tr>';
			echo '<td>' . esc_html( $key ) . '</td>';
			if ( is_object( $value ) or is_array( $value ) ) {
				echo '<td><pre>' . print_r( $value, true ) . '</pre></td>';
			} else {
				echo '<td>' . esc_html( $value ) . '</td>';
			}
			echo '</tr>';
		}

		echo '</tbody>';
		echo '</table>';
		echo '</div>';

	}

	protected function build_filter( $name, array $values ) {

		usort( $values, 'strcasecmp' );

		$out = '<select id="qm-filter-' . esc_attr( $this->collector->id . '-' . $name ) . '" class="qm-filter" data-filter="' . esc_attr( $this->collector->id . '-' . $name ) . '">';
		$out .= '<option value="">' . _x( 'All', '"All" option for filters', 'query-monitor' ) . '</option>';

		foreach ( $values as $value )
			$out .= '<option value="' . esc_attr( $value ) . '">' . esc_html( $value ) . '</option>';

		$out .= '</select>';

		return $out;

	}

	protected function menu( array $args ) {

		return array_merge( array(
			'id'   => "query-monitor-{$this->collector->id}",
			'href' => '#' . $this->collector->id()
		), $args );

	}

	final public function get_type() {
		return 'html';
	}

}
