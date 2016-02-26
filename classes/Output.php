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

if ( ! class_exists( 'QM_Output' ) ) {
abstract class QM_Output {

	protected $collector;

	public function __construct( QM_Collector $collector ) {
		$this->collector = $collector;
	}

	abstract public function get_output();

	public function output() {
		// nothing
	}

	public function get_collector() {
		return $this->collector;
	}

}
}
