<?php
/*

© 2013 John Blackbourn

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

*/

abstract class QM_Component {

	protected $data = array();

	protected function __construct() {}

	public function id() {
		return "qm-{$this->id}";
	}

	protected function menu( array $args ) {

		return wp_parse_args( $args, array(
			'id'   => "query-monitor-{$this->id}",
			'href' => '#' . $this->id()
		) );

	}

	protected function get_component( $id ) {
		# @TODO use singleton?
		global $querymonitor;
		return $querymonitor->get_component( $id );
	}

	public function get_data() {
		if ( isset( $this->data ) )
			return $this->data;
		return null;
	}

	public function process() {
		return false;
	}

	public function output_html( array $args, array $data ) {
		return false;
	}

	public function output_headers( array $args, array $data ) {
		return false;
	}

}
