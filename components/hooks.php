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

		$hooks = $parts = $components = array();

		if ( is_multisite() and is_network_admin() )
			$this->data['screen'] = preg_replace( '|-network$|', '', $this->data['screen'] );

		foreach ( $wp_actions as $action => $count ) {

			$name = $action;
			$actions = array();
			$c = array();

			if ( isset( $wp_filter[$action] ) ) {

				foreach( $wp_filter[$action] as $priority => $functions ) {

					foreach ( $functions as $function ) {

						if ( is_array( $function['function'] ) ) {

							if ( is_object( $function['function'][0] ) )
								$class = get_class( $function['function'][0] );
							else
								$class = $function['function'][0];

							$ref = new ReflectionMethod( $class, $function['function'][1] );

							$out = $class . '->' . $function['function'][1] . '()';
						} else if ( is_object( $function['function'] ) and is_a( $function['function'], 'Closure' ) ) {
							$ref  = new ReflectionFunction( $function['function'] );
							$file = trim( QM_Util::standard_dir( $ref->getFileName(), '' ), '/' );
							$out  = sprintf( __( '{closure}() on line %1$d of %2$s', 'query-monitor' ), $ref->getEndLine(), $file );
						} else {
							$ref  = new ReflectionFunction( $function['function'] );
							$out = $function['function'] . '()';
						}

						$component = QM_Util::get_file_component( $ref->getFileName() );
						$c[$component->name] = $component->name;
						$actions[] = array(
							'priority'  => $priority,
							'function'  => $out,
							'component' => $component,
						);

					}

				}

			}

			$p = array_filter( preg_split( '/[_\/-]/', $name ) );
			$parts = array_merge( $parts, $p );
			$components = array_merge( $components, $c );

			$hooks[$action] = array(
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

		echo '<div class="qm" id="' . $args['id'] . '">';
		echo '<table cellspacing="0">';
		echo '<thead>';
		echo '<tr>';
		echo '<th>' . __( 'Hook', 'query-monitor' ) . $this->build_filter( 'name', $data['parts'] ) . '</th>';
		echo '<th colspan="2">' . __( 'Actions', 'query-monitor' ) . '</th>';
		echo '<th>' . __( 'Component', 'query-monitor' ) . $this->build_filter( 'component', $data['components'] ) . '</th>';
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
					if ( !$first )
						echo "<tr{$attr}>";
					echo '<td valign="top" class="qm-priority">' . $action['priority'] . '</td>';
					echo '<td valign="top" class="qm-ltr">';
					echo esc_html( $action['function'] );
					echo '</td>';
					echo '<td valign="top">';
					echo esc_html( $action['component']->name );
					echo '</td>';
					echo '</tr>';
					$first = false;
				}

			} else {
				echo '<td colspan="2">&nbsp;</td>';
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
