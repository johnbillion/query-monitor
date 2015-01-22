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

if ( ! class_exists( 'QM_Collector' ) ) {
abstract class QM_Collector {

	protected $data = array();

	public function __construct() {}

	final public function id() {
		return "qm-{$this->id}";
	}

	abstract public function name();

	public static function timer_stop_float() {
		global $timestart;
		return microtime( true ) - $timestart;
	}

	public static function format_bool_constant( $constant ) {
		if ( !defined( $constant ) ) {
			return 'undefined';
		} else if ( !constant( $constant ) ) {
			return 'false';
		} else {
			return 'true';
		}
	}

	final public function get_data() {
		return $this->data;
	}

	public static function sort_ltime( $a, $b ) {
		if ( $a['ltime'] == $b['ltime'] ) {
			return 0;
		} else {
			return ( $a['ltime'] > $b['ltime'] ) ? -1 : 1;
		}
	}

	public function process() {}

	public function tear_down() {}

}
}
