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

class QM_Component_Admin extends QM_Component {

	var $id = 'admin';

	function __construct() {
		parent::__construct();
		add_filter( 'current_screen',      array( $this, 'current_screen' ), 99 );
		add_filter( 'query_monitor_menus', array( $this, 'admin_menu' ), 100 );
	}

	function current_screen( WP_Screen $screen ) {
		if ( empty( $this->data['admin'] ) )
			$this->data['admin'] = wp_clone( $screen );
		return $screen;
	}

	function process() {

		global $pagenow;

		if ( isset( $_GET['page'] ) )
			$this->data['base'] = get_current_screen()->base;
		else
			$this->data['base'] = $pagenow;

		if ( !isset( $this->data['admin'] ) )
			$this->data['admin'] = __( 'n/a', 'query-monitor' );

		$this->data['pagenow'] = $pagenow;
		$this->data['current_screen'] = get_current_screen();

	}

	function admin_menu( array $menu ) {

		if ( isset( $this->data['base'] ) ) {
			$menu[] = $this->menu( array(
				'title' => sprintf( __( 'Admin Screen: %s', 'query-monitor' ), $this->data['base'] )
			) );
		}
		return $menu;

	}

	function output_html( array $args, array $data ) {

		if ( empty( $data ) )
			return;

		echo '<div class="qm qm-half" id="' . $args['id'] . '">';
		echo '<table cellspacing="0">';
		echo '<thead>';
		echo '<tr>';
		echo '<th colspan="3">' . __( 'Admin', 'query-monitor' ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		echo '<tr>';
		echo '<td class="qm-ltr">get_current_screen()</td>';
		echo '<td>';

		if ( is_object( $data['admin'] ) ) {
			echo '<table class="qm-inner" cellspacing="0">';
			echo '<tbody>';
			foreach ( $data['admin'] as $key => $value ) {
				echo '<tr>';
				echo "<td class='qm-var'>{$key}:</td>";
				echo '<td>';
				echo $value;
				if ( !empty( $value ) and ( $data['current_screen']->$key != $value ) )
					echo '&nbsp;(<a href="http://core.trac.wordpress.org/ticket/14886" class="qm-warn" title="' . esc_attr__( 'This value may not be as expected. Please see WordPress bug #14886.', 'query-monitor' ) . '" target="_blank">!</a>)';
				echo '</td>';
				echo '</tr>';
			}
			echo '</tbody>';
			echo '</table>';
		} else {
			echo $data['admin'];
		}

		echo '</td>';
		echo '</tr>';

		echo '<tr>';
		echo '<td class="qm-ltr">$pagenow</td>';
		echo "<td>{$data['pagenow']}</td>";
		echo '</tr>';

		$screens = array(
			'edit'            => true,
			'edit-comments'   => true,
			'edit-tags'       => true,
			'link-manager'    => true,
			'plugins'         => true,
			'plugins-network' => true,
			'sites-network'   => true,
			'themes-network'  => true,
			'upload'          => true,
			'users'           => true,
			'users-network'   => true,
		);

		if ( !empty( $data['current_screen'] ) and isset( $screens[$data['current_screen']->base] ) ) {

			# And now, WordPress' legendary inconsistency comes into play:

			if ( !empty( $data['current_screen']->taxonomy ) )
				$col = $data['current_screen']->taxonomy;
			else if ( !empty( $data['current_screen']->post_type ) )
				$col = $data['current_screen']->post_type . '_posts';
			else
				$col = $data['current_screen']->base;

			if ( !empty( $data['current_screen']->post_type ) and empty( $data['current_screen']->taxonomy ) )
				$cols = $data['current_screen']->post_type . '_posts';
			else
				$cols = $data['current_screen']->id;

			if ( 'edit-comments' == $col )
				$col = 'comments';
			else if ( 'upload' == $col )
				$col = 'media';
			else if ( 'link-manager' == $col )
				$col = 'link';

			echo '<tr>';
			echo '<td rowspan="2">' . __( 'Column Filters', 'query-monitor' ) . '</td>';
			echo "<td colspan='2'>manage_<span class='qm-current'>{$cols}</span>_columns</td>";
			echo '</tr>';
			echo '<tr>';
			echo "<td colspan='2'>manage_<span class='qm-current'>{$data['current_screen']->id}</span>_sortable_columns</td>";
			echo '</tr>';

			echo '<tr>';
			echo '<td rowspan="1">' . __( 'Column Action', 'query-monitor' ) . '</td>';
			echo "<td colspan='2'>manage_<span class='qm-current'>{$col}</span>_custom_column</td>";
			echo '</tr>';

		}

		echo '</tbody>';
		echo '</table>';
		echo '</div>';

	}

}

function register_qm_admin( array $qm ) {
	if ( is_admin() )
		$qm['admin'] = new QM_Component_Admin;
	return $qm;
}

add_filter( 'query_monitor_components', 'register_qm_admin', 50 );
