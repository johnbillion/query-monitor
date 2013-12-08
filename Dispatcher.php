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

abstract class QM_Dispatcher {

	public function __construct( QM_Plugin $qm ) {
		$this->qm = $qm;
	}

	abstract public function active();

	public function init() {
		// nothing
	}

	public function before_output() {
		// nothing
	}

	public function after_output() {
		// nothing
	}

	abstract public function get_outputter( QM_Collector $collector );

	public function output( QM_Collector $collector ) {

		$filter = 'query_monitor_output_' . $this->id . '_' . $collector->id;

		$output = apply_filters( $filter, null, $collector );

		if ( !is_a( $output, 'QM_Output' ) ) {
			$output = $this->get_outputter( $collector );
		}

		$output->output();

	}

}
