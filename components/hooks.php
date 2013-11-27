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

class QM_Component_Hooks extends QM_Component {

	var $id = 'hooks';

	function __construct() {
		parent::__construct();
		add_filter( 'query_monitor_menus', array( $this, 'admin_menu' ), 80 );
	}

	function admin_menu( array $menu ) {

		$menu[] = $this->menu( array(
			'title' => __( 'Hooks', 'query-monitor' )
		) );
		return $menu;

	}

	function process() {

		global $wp_actions, $wp_filter;

		if ( is_admin() and ( $admin = $this->get_component( 'admin' ) ) )
			$this->data['screen'] = $admin->data['base'];
		else
			$this->data['screen'] = '';

		$hooks = $parts = $components = array();

		# @TODO why am i doing this here?:
		if ( is_multisite() and is_network_admin() )
			$this->data['screen'] = preg_replace( '|-network$|', '', $this->data['screen'] );

		foreach ( $wp_actions as $name => $count ) {

			$actions = array();
			# @TODO better variable name:
			$c = array();

			if ( isset( $wp_filter[$name] ) ) {

				foreach( $wp_filter[$name] as $priority => $callbacks ) {

					foreach ( $callbacks as $callback ) {

						$callback = QM_Util::populate_callback( $callback );

						if ( isset( $callback['component'] ) )
							$c[$callback['component']->name] = $callback['component']->name;

						$actions[] = array(
							'priority'  => $priority,
							'callback'  => $callback,
						);

					}

				}

			}

			# @TODO better variable name:
			$p = array_filter( preg_split( '/[_\/-]/', $name ) );
			$parts = array_merge( $parts, $p );
			$components = array_merge( $components, $c );

			$hooks[$name] = array(
				'name'    => $name,
				'actions' => $actions,
				'parts'   => $p,
				'components' => $c,
			);

		}

		$this->data['hooks'] = $hooks;
		$this->data['parts'] = array_unique( array_filter( $parts ) );
		$this->data['components'] = array_unique( array_filter( $components ) );

	}

	function output_html( array $args, array $data ) {

		$row_attr = array();

		echo '<div class="qm" id="' . $args['id'] . '">';
		echo '<table cellspacing="0">';
		echo '<thead>';
		echo '<tr>';
		echo '<th>' . __( 'Hook', 'query-monitor' ) . $this->build_filter( 'name', $data['parts'] ) . '</th>';
		echo '<th colspan="3">' . __( 'Actions', 'query-monitor' ) . $this->build_filter( 'component', $data['components'] ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		foreach ( $data['hooks'] as $hook ) {

			if ( !empty( $data['screen'] ) ) {

				if ( false !== strpos( $hook['name'], $data['screen'] . '.php' ) )
					$hook['name'] = str_replace( '-' . $data['screen'] . '.php', '-<span class="qm-current">' . $data['screen'] . '.php</span>', $hook['name'] );
				else
					$hook['name'] = str_replace( '-' . $data['screen'], '-<span class="qm-current">' . $data['screen'] . '</span>', $hook['name'] );

			}

			$row_attr['data-qm-hooks-name']      = implode( ' ', $hook['parts'] );
			$row_attr['data-qm-hooks-component'] = implode( ' ', $hook['components'] );

			$attr = '';

			if ( !empty( $hook['actions'] ) )
				$rowspan = count( $hook['actions'] );
			else
				$rowspan = 1;

			foreach ( $row_attr as $a => $v )
				$attr .= ' ' . $a . '="' . esc_attr( $v ) . '"';

			echo "<tr{$attr}>";

			echo "<td valign='top' rowspan='{$rowspan}'>{$hook['name']}</td>";	
			if ( !empty( $hook['actions'] ) ) {

				$first = true;

				foreach ( $hook['actions'] as $action ) {

					if ( isset( $action['callback']['component'] ) )
						$component = $action['callback']['component']->name;
					else
						$component = '';

					if ( !$first )
						echo "<tr{$attr}>";

					echo '<td valign="top" class="qm-priority">' . $action['priority'] . '</td>';
					echo '<td valign="top" class="qm-ltr">';
					echo esc_html( $action['callback']['name'] );
					if ( isset( $action['callback']['error'] ) ) {
						echo '<br><span class="qm-warn">';
						printf( __( 'Error: %s', 'query-monitor' ),
							esc_html( $action['callback']['error']->get_error_message() )
						);
						echo '<span>';
					}
					echo '</td>';
					echo '<td valign="top">';
					echo esc_html( $component );
					echo '</td>';
					echo '</tr>';
					$first = false;
				}

			} else {
				echo '<td colspan="3">&nbsp;</td>';
			}
			echo '</tr>';
		}

		echo '</tbody>';
		echo '</table>';
		echo '</div>';

	}

}

function register_qm_hooks( array $qm ) {
	$qm['hooks'] = new QM_Component_Hooks;
	return $qm;
}

add_filter( 'query_monitor_components', 'register_qm_hooks', 80 );
