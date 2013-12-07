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

abstract class QM_Collector {

	protected $data = array();

	protected function __construct() {}

	final public function id() {
		return "qm-{$this->id}";
	}

	final protected function menu( array $args ) {

		return wp_parse_args( $args, array(
			'id'   => "query-monitor-{$this->id}",
			'href' => '#' . $this->id()
		) );

	}

	public function name() {
		return null;
	}

	protected function build_filter( $name, array $values ) {

		usort( $values, 'strcasecmp' );

		$out = '<select id="qm-filter-' . esc_attr( $this->id . '-' . $name ) . '" class="qm-filter" data-filter="' . esc_attr( $this->id . '-' . $name ) . '">';
		$out .= '<option value="">' . _x( 'All', '"All" option for filters', 'query-monitor' ) . '</option>';

		foreach ( $values as $value )
			$out .= '<option value="' . esc_attr( $value ) . '">' . esc_html( $value ) . '</option>';

		$out .= '</select>';

		return $out;

	}

	final public function get_data() {
		return $this->data;
	}

	public function process() {}

	public function output_html( array $args, array $data ) {}

	public function output_headers( array $args, array $data ) {}

}
