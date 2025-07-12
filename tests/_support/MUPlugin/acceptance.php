<?php

add_action( 'init', function() {
	if ( ! isset( $_GET['_qm_acceptance_group'], $_GET['_qm_acceptance_test'] ) ) {
		return;
	}

	switch ( $_GET['_qm_acceptance_group'] ) {
		case 'doing_it_wrong':
			switch ( $_GET['_qm_acceptance_test'] ) {
				case 'argument':
					_deprecated_argument( 'my_function', '2.0.0' );
					break;
				case 'class':
					_deprecated_class( 'My_Class', '2.0.0' );
					break;
				case 'constructor':
					_deprecated_constructor( 'My_Class', '2.0.0' );
					break;
				case 'file':
					_deprecated_file( 'my_file.php', '2.0.0' );
					break;
				case 'function':
					_deprecated_function( 'my_function', '2.0.0' );
					break;
				case 'hook':
					_deprecated_hook( 'my_hook', '2.0.0' );
					break;
			}
			break;
	}
} );
