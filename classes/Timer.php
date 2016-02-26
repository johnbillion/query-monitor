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

class QM_Timer {

	protected $start = null;
	protected $end   = null;
	protected $trace = null;
	protected $laps  = array();

	public function __construct( array $data = null ) {
		$this->start( $data );
	}

	public function start( array $data = null ) {
		$this->trace = new QM_Backtrace;
		$this->start = array(
			'time'   => microtime( true ),
			'memory' => memory_get_usage(),
			'data'   => $data,
		);
		return $this;
	}

	public function stop( array $data = null ) {

		$this->end = array(
			'time'   => microtime( true ),
			'memory' => memory_get_usage(),
			'data'   => $data,
		);

		return $this;

	}

	public function lap( array $data = null, $name = null ) {

		$lap = array(
			'time'   => microtime( true ),
			'memory' => memory_get_usage(),
			'data'   => $data,
		);

		if ( !isset( $name ) ) {
			$i = sprintf( __( 'Lap %d', 'query-monitor' ), count( $this->laps ) + 1 );
		} else {
			$i = $name;
		}

		$this->laps[$i] = $lap;

		return $this;

	}

	public function get_laps() {

		$laps = array();
		$prev = $this->start;

		foreach ( $this->laps as $lap_id => $lap ) {

			$lap['time_used']   = $lap['time']   - $prev['time'];
			$lap['memory_used'] = $lap['memory'] - $prev['memory'];

			$laps[$lap_id] = $prev = $lap;

		}

		return $laps;

	}

	public function get_time() {
		return $this->end['time'] - $this->start['time'];
	}

	public function get_memory() {
		return $this->end['memory'] - $this->start['memory'];
	}

	public function get_trace() {
		return $this->trace;
	}

	public function end( array $data = null ) {
		return $this->stop( $data );
	}

}
