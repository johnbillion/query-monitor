<?php

class QM_Hooks extends QM {

	var $id = 'hooks';

	function __construct() {

		parent::__construct();

	}

	function admin_menu() {

		return $this->menu( array(
			'title' => __( 'Hooks', 'query_monitor' )
		) );

	}

	function output() {

		global $wp_actions, $querymonitor;

		echo '<table class="qm" cellspacing="0" id="' . $this->id() . '">';
		echo '<thead>';
		echo '<tr>';
		echo '<th>' . __( 'Hook', 'query_monitor' ) . '</th>';
		echo '<th>' . __( 'Actions', 'query_monitor' ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		if ( is_numeric( current( $wp_actions ) ) )
			$actions = array_keys( $wp_actions ); # wp 3.0+
		else
			$actions = array_values( $wp_actions ); # < wp 3.0

		$qm_class = get_class( $querymonitor );

		if ( is_multisite() and is_network_admin() )
			$screen = preg_replace( '|-network$|', '', $this->screen );
		else
			$screen = $this->screen;

		foreach ( $actions as $action ) {

			$name = $action;

			if ( !empty( $screen ) ) {

				if ( false !== strpos( $name, $screen . '.php' ) )
					$name = str_replace( '-' . $screen . '.php', '-<span class="qm-current">' . $screen . '.php</span>', $name );
				else
					$name = str_replace( '-' . $screen, '-<span class="qm-current">' . $screen . '</span>', $name );

			}

			echo '<tr>';
			echo "<td valign='top'>$name</td>";
			if ( isset( $GLOBALS['wp_filter'][$action] ) ) {
				echo '<td><table class="qm-inner" cellspacing="0">';
				foreach( $GLOBALS['wp_filter'][$action] as $priority => $functions ) {
					foreach ( $functions as $function ) {
						$css = '';
						if ( is_array( $function['function'] ) ) {
							$class = $function['function'][0];
							if ( is_object( $class ) )
								$class = get_class( $class );
							if ( $qm_class == $class )
								$css = 'qm-qm';
							$out = $class . '-&gt;' . $function['function'][1] . '()';
						} else {
							$out = $function['function'] . '()';
						}
						echo '<tr class="' . $css . '">';
						echo '<td valign="top" class="qm-priority">' . $priority . '</td>';
						echo '<td valign="top" class="qm-ltr">';
						echo $out;
						echo '</td>';
						echo '</tr>';
					}
				}
				echo '</table></td>';
			} else {
				echo '<td>&nbsp;</td>';
			}
			echo '</tr>';
		}

		echo '</tbody>';
		echo '</table>';

	}

}

function register_qm_hooks( $qm ) {
	$qm['hooks'] = new QM_Hooks;
	return $qm;
}

add_filter( 'qm', 'register_qm_hooks' );

?>