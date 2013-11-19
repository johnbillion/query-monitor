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

class QM_Component_Theme extends QM_Component {

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

	function process() {

		global $template;

		$template_file        = QM_Util::standard_dir( $template );
		$stylesheet_directory = QM_Util::standard_dir( get_stylesheet_directory() );
		$template_directory   = QM_Util::standard_dir( get_template_directory() );

		$template_file = str_replace( array( $stylesheet_directory, $template_directory ), '', $template_file );
		$template_file = ltrim( $template_file, '/' );

		$this->data['template_file'] = apply_filters( 'query_monitor_template', $template_file, $template );
		$this->data['stylesheet']    = get_stylesheet();
		$this->data['template']      = get_template();

		if ( isset( $this->data['body_class'] ) )
			asort( $this->data['body_class'] );

	}

	function output_html( array $args, array $data ) {

		if ( empty( $data ) )
			return;

		echo '<div class="qm qm-half" id="' . $args['id'] . '">';
		echo '<table cellspacing="0">';
		echo '<thead>';
		echo '<tr>';
		echo '<th colspan="2">' . __( 'Theme', 'query-monitor' ) . '</th>';
		echo '</tr>';
		echo '</thead>';

		echo '<tbody>';
		echo '<tr>';
		echo '<td>' . __( 'Template', 'query-monitor' ) . '</td>';
		echo "<td>{$this->data['template_file']}</td>";
		echo '</tr>';

		if ( !empty( $data['body_class'] ) ) {

			echo '<tr>';
			echo '<td rowspan="' . count( $data['body_class'] ) . '">' . __( 'Body Classes', 'query-monitor' ) . '</td>';
			$first = true;

			foreach ( $data['body_class'] as $class ) {

				if ( !$first )
					echo '<tr>';

				echo "<td>{$class}</td>";
				echo '</tr>';

				$first = false;

			}

		}

		echo '<tr>';
		echo '<td>' . __( 'Theme', 'query-monitor' ) . '</td>';
		echo "<td>{$this->data['stylesheet']}</td>";
		echo '</tr>';

		if ( $this->data['stylesheet'] != $this->data['template'] ) {
			echo '<tr>';
			echo '<td>' . __( 'Parent Theme', 'query-monitor' ) . '</td>';
			echo "<td>{$this->data['template']}</td>";
			echo '</tr>';
		}

		echo '</tbody>';
		echo '</table>';
		echo '</div>';

	}

	function admin_menu( array $menu ) {

		if ( isset( $this->data['template_file'] ) ) {
			$menu[] = $this->menu( array(
				'title' => sprintf( __( 'Template: %s', 'query-monitor' ), $this->data['template_file'] )
			) );
		}
		return $menu;

	}

}

function register_qm_theme( array $qm ) {
	if ( !is_admin() )
		$qm['theme'] = new QM_Component_Theme;
	return $qm;
}

add_filter( 'query_monitor_components', 'register_qm_theme', 60 );
