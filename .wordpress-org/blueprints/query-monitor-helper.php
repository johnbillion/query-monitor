<?php
/**
 * Plugin Name: Query Monitor Playground Helper
 * Description: A helper plugin for running Query Monitor in the WordPress playground
 */

add_action( 'wp_head', static function (): void {
	wp_print_inline_script_tag(
		<<<JS
		if ( ! localStorage.getItem( 'qm-playground-defaults-applied' ) ) {
			localStorage.setItem( 'qm-playground-defaults-applied', '1' );
			localStorage.setItem( 'qm-front-panel', 'timeline' );
		}
		JS
	);
}, 1 );
