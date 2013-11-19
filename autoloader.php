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

function qm_autoloader( $class ) {

	if ( 0 !== strpos( $class, 'QM_' ) )
		return;

	$name = preg_replace( '|^QM_|', '', $class );
	$name = str_replace( '_', '/', $name );

	$file = sprintf( '%1$s/%2$s.php',
		dirname( __FILE__ ),
		$name
	);

	if ( is_readable( $file ) )
		include $file;

}

spl_autoload_register( 'qm_autoloader' );
