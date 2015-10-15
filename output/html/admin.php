<?php
/*
Copyright 2009-2015 John Blackbourn

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

*/

class QM_Output_Html_Admin extends QM_Output_Html {

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 60 );
	}

	public function output() {

		$data = $this->collector->get_data();

		if ( empty( $data['current_screen'] ) ) {
			return;
		}

		echo '<div class="qm qm-half" id="' . esc_attr( $this->collector->id() ) . '">';
		echo '<table cellspacing="0">';
		echo '<thead>';
		echo '<tr>';
		echo '<th colspan="2">' . esc_html( $this->collector->name() ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		echo '<tr>';
		echo '<td class="qm-ltr">get_current_screen()</td>';
		echo '<td class="qm-has-inner">';

		echo '<table class="qm-inner" cellspacing="0">';
		echo '<tbody>';
		foreach ( $data['current_screen'] as $key => $value ) {
			echo '<tr>';
			echo '<td>' . esc_html( $key ) . '</td>';
			echo '<td>' . esc_html( $value ) . '</td>';
			echo '</tr>';
		}
		echo '</tbody>';
		echo '</table>';

		echo '</td>';
		echo '</tr>';

		echo '<tr>';
		echo '<td class="qm-ltr">$pagenow</td>';
		echo '<td>' . esc_html( $data['pagenow'] ) . '</td>';
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

		// @TODO a lot of this logic can move to the collector
		if ( !empty( $data['current_screen'] ) and isset( $screens[$data['current_screen']->base] ) ) {

			# And now, WordPress' legendary inconsistency comes into play:

			if ( !empty( $data['current_screen']->taxonomy ) ) {
				$col = $data['current_screen']->taxonomy;
			} else if ( !empty( $data['current_screen']->post_type ) ) {
				$col = $data['current_screen']->post_type . '_posts';
			} else {
				$col = $data['current_screen']->base;
			}

			if ( !empty( $data['current_screen']->post_type ) and empty( $data['current_screen']->taxonomy ) ) {
				$cols = $data['current_screen']->post_type . '_posts';
			} else {
				$cols = $data['current_screen']->id;
			}

			if ( 'edit-comments' == $col ) {
				$col = 'comments';
			} else if ( 'upload' == $col ) {
				$col = 'media';
			} else if ( 'link-manager' == $col ) {
				$col = 'link';
			}

			echo '<tr>';
			echo '<td rowspan="2">' . esc_html__( 'Column Filters', 'query-monitor' ) . '</td>';
			echo '<td colspan="2">manage_<span class="qm-current">' . esc_html( $cols ) . '</span>_columns</td>';
			echo '</tr>';
			echo '<tr>';
			echo '<td colspan="2">manage_<span class="qm-current">' . esc_html( $data['current_screen']->id ) . '</span>_sortable_columns</td>';
			echo '</tr>';

			echo '<tr>';
			echo '<td rowspan="1">' . esc_html__( 'Column Action', 'query-monitor' ) . '</td>';
			echo '<td colspan="2">manage_<span class="qm-current">' . esc_html( $col ) . '</span>_custom_column</td>';
			echo '</tr>';

		}

		echo '</tbody>';
		echo '</table>';
		echo '</div>';

	}

}

function register_qm_output_html_admin( array $output, QM_Collectors $collectors ) {
	if ( $collector = QM_Collectors::get( 'admin' ) ) {
		$output['admin'] = new QM_Output_Html_Admin( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_admin', 70, 2 );
