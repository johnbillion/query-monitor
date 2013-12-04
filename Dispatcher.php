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

abstract class QM_Output_Dispatcher {

	public $outputters = array();

	public function __construct() {
		// nothing
	}

	final public function setup( QM_Plugin $qm ) {
		$filter = 'query_monitor_outputs_' . $this->id;
		foreach ( apply_filters( $filter, array() ) as $output ) {
			$this->add_outputter( $output );
		}
		$this->qm = $qm;
	}

	# @TODO don't pass QM_Plugin to this method
	abstract public function active( QM_Plugin $qm );

	# @TODO don't pass QM_Plugin to this method
	public function init( QM_Plugin $qm ) {
		// nothing
	}

	# @TODO don't pass QM_Plugin to this method
	public function before_output( QM_Plugin $qm ) {
		// nothing
	}

	# @TODO don't pass QM_Plugin to this method
	public function after_output( QM_Plugin $qm ) {
		// nothing
	}

	public function add_outputter( QM_Output $output ) {
		$this->outputters[$output->id] = $output;
	}

	abstract public function get_outputter();

	public function output( QM_Component $component ) {

		if ( isset( $this->outputters[$component->id] ) ) {
			$this->outputters[$component->id]->output( $component );
		} else {
			$output = $this->get_outputter();
			$output->output( $component );
		}
	}

}
