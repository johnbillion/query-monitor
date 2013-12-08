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

class QM_Output_Headers implements QM_Output {

	public function __construct( QM_Collector $collector ) {
		$this->collector = $collector;
	}

	public function output() {
		# Headers output does nothing by default
		return false;
	}

	final public function get_type() {
		return 'headers';
	}

}
