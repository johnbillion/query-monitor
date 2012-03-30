<?php

class QM_Hooks extends QM {

	var $id = 'hooks';

	function __construct() {
		parent::__construct();
		add_filter( 'query_monitor_menus', array( $this, 'admin_menu' ), 60 );
	}

	function admin_menu( $menu ) {

		$menu[] = $this->menu( array(
			'title' => __( 'Hooks', 'query_monitor' )
		) );
		return $menu;

	}

	function process_late() {

		global $wp_actions, $wp_filter, $querymonitor, $current_screen, $pagenow;

		if ( is_admin() and ( $admin = $this->get_component( 'admin' ) ) )
			$screen = $admin->data['base'];
		else
			$screen = '';

		$qm_class = get_class( $querymonitor );
		$hooks = array();

		if ( $this->is_multisite and is_network_admin() )
			$screen = preg_replace( '|-network$|', '', $screen );

		if ( is_numeric( current( $wp_actions ) ) )
			$actions = array_keys( $wp_actions ); # wp 3.0+
		else
			$actions = array_values( $wp_actions ); # < wp 3.0

		foreach ( $actions as $action ) {

			$name = $action;
			$actions = array();

			if ( !empty( $screen ) ) {

				if ( false !== strpos( $name, $screen . '.php' ) )
					$name = str_replace( '-' . $screen . '.php', '-<span class="qm-current">' . $screen . '.php</span>', $name );
				else
					$name = str_replace( '-' . $screen, '-<span class="qm-current">' . $screen . '</span>', $name );

			}

			if ( isset( $wp_filter[$action] ) ) {

				foreach( $wp_filter[$action] as $priority => $functions ) {

					foreach ( $functions as $function ) {

						$css_class = '';

						if ( is_array( $function['function'] ) ) {

							if ( is_object( $function['function'][0] ) )
								$class = get_class( $function['function'][0] );
							else
								$class = $function['function'][0];

							if ( $qm_class == $class )
								$css_class = 'qm-qm';
							$out = $class . '->' . $function['function'][1] . '()';
						} else {
							$out = $function['function'] . '()';
						}

						$actions[] = array(
							'class'    => $css_class,
							'priority' => $priority,
							'function' => $out
						);

					}

				}

			}

			$hooks[$action] = array(
				'name'    => $name,
				'actions' => $actions
			);

		}

		$this->data['hooks'] = $hooks;

	}

	function output( $args, $data ) {

		echo '<div class="qm" id="' . $args['id'] . '">';
		echo '<table cellspacing="0">';
		echo '<thead>';
		echo '<tr>';
		echo '<th>' . __( 'Hook', 'query_monitor' ) . '</th>';
		echo '<th>' . __( 'Actions', 'query_monitor' ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		foreach ( $data['hooks'] as $hook ) {

			echo '<tr>';
			echo "<td valign='top'>{$hook['name']}</td>";
			if ( !empty( $hook['actions'] ) ) {
				echo '<td><table class="qm-inner" cellspacing="0">';
				foreach ( $hook['actions'] as $action ) {
					echo '<tr class="' . $action['class'] . '">';
					echo '<td valign="top" class="qm-priority">' . $action['priority'] . '</td>';
					echo '<td valign="top" class="qm-ltr">';
					echo esc_html( $action['function'] );
					echo '</td>';
					echo '</tr>';
				}
				echo '</table></td>';
			} else {
				echo '<td>&nbsp;</td>';
			}
			echo '</tr>';
		}

		echo '</tbody>';
		echo '</table>';
		echo '</div>';

	}

}

function register_qm_hooks( $qm ) {
	$qm['hooks'] = new QM_Hooks;
	return $qm;
}

add_filter( 'query_monitor_components', 'register_qm_hooks', 80 );

?>
