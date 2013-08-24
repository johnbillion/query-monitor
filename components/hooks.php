<?php

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

		$hooks = $parts = array();

		if ( is_multisite() and is_network_admin() )
			$this->data['screen'] = preg_replace( '|-network$|', '', $this->data['screen'] );

		foreach ( $wp_actions as $action => $count ) {

			$name = $action;
			$actions = array();

			if ( isset( $wp_filter[$action] ) ) {

				foreach( $wp_filter[$action] as $priority => $functions ) {

					foreach ( $functions as $function ) {

						$css_class = '';

						if ( is_array( $function['function'] ) ) {

							if ( is_object( $function['function'][0] ) )
								$class = get_class( $function['function'][0] );
							else
								$class = $function['function'][0];

							$out = $class . '->' . $function['function'][1] . '()';
						} else if ( is_object( $function['function'] ) and is_a( $function['function'], 'Closure' ) ) {
							$ref  = new ReflectionFunction( $function['function'] );
							$file = trim( QM_Util::standard_dir( $ref->getFileName(), '' ), '/' );
							$out  = sprintf( __( '{closure}() on line %1$d of %2$s', 'query-monitor' ), $ref->getEndLine(), $file );
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

			$p = array_filter( preg_split( '/[_-]/', $name ) );
			$parts = array_merge( $parts, $p );

			$hooks[$action] = array(
				'name'    => $name,
				'actions' => $actions,
				'parts'   => $p
			);

		}

		$this->data['hooks'] = $hooks;
		$this->data['parts'] = array_unique( array_filter( $parts ) );

	}

	function output_html( array $args, array $data ) {

		echo '<div class="qm" id="' . $args['id'] . '">';
		echo '<table cellspacing="0">';
		echo '<thead>';
		echo '<tr>';
		echo '<th>' . __( 'Hook', 'query-monitor' ) . $this->build_filter( 'name', $data['parts'] ) . '</th>';
		echo '<th>' . __( 'Actions', 'query-monitor' ) . '</th>';
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

			$row_attr['data-qm-hooks-name'] = implode( ' ', $hook['parts'] );

			$attr = '';

			foreach ( $row_attr as $a => $v )
				$attr .= ' ' . $a . '="' . esc_attr( $v ) . '"';

			echo "<tr{$attr}>";

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

function register_qm_hooks( array $qm ) {
	$qm['hooks'] = new QM_Component_Hooks;
	return $qm;
}

add_filter( 'query_monitor_components', 'register_qm_hooks', 80 );
