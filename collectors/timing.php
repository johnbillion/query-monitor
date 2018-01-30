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
	private $track_timer = array();
	private $start = array();
	private $stop = array();
	private $laps = array();

	public function name() {
		return __( 'Timing', 'query-monitor' );
	}

	public function __construct() {
		parent::__construct();
		add_action( 'qm/start', array( $this, 'action_function_time_start' ), 10, 1 );
		add_action( 'qm/stop', array( $this, 'action_function_time_stop' ), 10, 1 );
		add_action( 'qm/lap', array( $this, 'action_function_time_lap' ), 10, 2 );
	}

	public function action_function_time_start( $function ) {
		$this->track_timer[ $function ] = new QM_Timer;
		$this->start[ $function ] = $this->track_timer[ $function ]->start();
	}

	public function action_function_time_stop( $function ) {
		$this->stop[ $function ] = $this->track_timer[ $function ]->stop();
		$this->calculate_time( $function );
	}

	public function action_function_time_lap( $function, $name = null ) {
		// @TODO guard against lapping non-existant timer
		$this->track_timer[ $function ]->lap( $name );
	}

	public function calculate_time( $function ) {
		$trace = $this->track_timer[ $function ]->get_trace();
		$function_time = $this->track_timer[ $function ]->get_time();
		$function_memory = $this->track_timer[ $function ]->get_memory();
		$function_laps = $this->track_timer[ $function ]->get_laps();

		$this->data['timing'][] = array(
			'function'        => $function,
			'function_time'   => $function_time,
			'function_memory' => $function_memory,
			'laps'            => $function_laps,
			'trace'           => $trace,
		);
	}

	public function process() {
		foreach ( $this->start as $function => $value ) {
			if ( ! isset( $this->stop[ $function ] ) ) {
				$trace = $this->track_timer[ $function ]->get_trace();
				$this->data['warning'][] = array(
					'function'  => $function,
					'message'   => __( 'Please add the stop hook', 'query-monitor' ),
					'trace'     => $trace,
				);
			}
		}
	}

}

# Load early in case a plugin is setting the function to be checked when it initialises instead of after the `plugins_loaded` hook
QM_Collectors::add( new QM_Collector_Timing );
