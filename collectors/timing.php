<?php
/*
Copyright 2009-2017 John Blackbourn

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

*/

class QM_Collector_Timing extends QM_Collector {

	public $id = 'timing';
	private $start = null;
	private $stop = null;

	public function name() {
		return __( 'Timing', 'query-monitor' );
	}

	public function __construct() {
		parent::__construct();
		add_action( 'qm/start', array( $this, 'action_function_time_start' ), 10, 1 );
		add_action( 'qm/stop', array( $this, 'action_function_time_stop' ), 10, 1 );
	}

	public function action_function_time_start( $function ) {
		$start = microtime( true );
		$this->start = $start;
	}

	public function action_function_time_stop( $function ) {
		$stop = microtime( true );
		var_dump($stop);
		$this->stop = $stop;
		$this->calculate_time( $function );
	}

	public function calculate_time( $function ) {
		$function_time = $this->start - $this->stop;
		$this->qm_function_time( $function, $function_time );
	}

	public function qm_function_time( $function, $function_time ) {
		$trace = new QM_Backtrace();

		$this->data['timing'][] = array(
			'function'      => $function,
			'function_time' => $function_time,
			'trace'         => $trace,
		);
	}

}

# Load early in case a plugin is setting the function to be checked when it initialises instead of after the `plugins_loaded` hook
QM_Collectors::add( new QM_Collector_Timing );
