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

abstract class QM_Output_Headers extends QM_Output {

	public function output() {

		$id = $this->collector->id;

		foreach ( $this->get_output() as $key => $value ) {
			if ( is_scalar( $value ) ) {
				header( sprintf( 'X-QM-%s-%s: %s', $id, $key, $value ) );
			} else {
				header( sprintf( 'X-QM-%s-%s: %s', $id, $key, json_encode( $value ) ) );
			}
		}

	}

}
