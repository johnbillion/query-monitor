<?php

class QM_Theme extends QM {

	var $id = 'theme';

	function __construct() {
		parent::__construct();
		add_filter( 'body_class',          array( $this, 'body_class' ), 99 );
		add_filter( 'query_monitor_menus', array( $this, 'admin_menu' ), 100 );
	}

	function body_class( $class ) {
		$this->data['body_class'] = $class;
		return $class;
	}

	function output( $args, $data ) {

		global $template;

		# @TODO display parent/child theme info

		$template_file = apply_filters( 'query_monitor_template', basename( $template ) );

		echo '<table class="qm" cellspacing="0" id="' . $args['id'] . '">';
		echo '<thead>';
		echo '<tr>';
		echo '<th colspan="2">' . __( 'Theme', 'query_monitor' ) . '</th>';
		echo '</tr>';
		echo '</thead>';

		echo '<tbody>';
		echo '<tr>';
		echo '<td>' . __( 'Template', 'query_monitor' ) . '</td>';
		echo "<td>{$template_file}</td>";
		echo '</tr>';

		if ( !empty( $data['body_class'] ) ) {

			echo '<tr>';
			echo '<td rowspan="' . count( $data['body_class'] ) . '">' . __( 'Body Classes', 'query_monitor' ) . '</td>';
			$first = true;

			foreach ( $data['body_class'] as $class ) {

				if ( !$first )
					echo '<tr>';

				echo "<td>{$class}</td>";
				echo '</tr>';

				$first = false;

			}

		}

		echo '</tbody>';
		echo '</table>';

	}

	function admin_menu( $menu ) {

		# @TODO put the template into process():

		global $template;

		$template_file = apply_filters( 'query_monitor_template', basename( $template ) );

		$menu[] = $this->menu( array(
			'title' => sprintf( __( 'Template: %s', 'query_monitor' ), $template_file )
		) );
		return $menu;

	}

}

function register_qm_theme( $qm ) {
	if ( !is_admin() )
		$qm['theme'] = new QM_Theme;
	return $qm;
}

add_filter( 'query_monitor_components', 'register_qm_theme', 60 );

?>
