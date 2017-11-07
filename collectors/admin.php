<?php
/*
Copyright 2009-2017 John Blackbourn

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

*/

class QM_Collector_Admin extends QM_Collector {

	public $id = 'admin';

	public function name() {
		return __( 'Admin Screen', 'query-monitor' );
	}

	public function process() {

		global $pagenow;

		$current_screen = get_current_screen();

		if ( isset( $_GET['page'] ) && null !== $current_screen ) { // @codingStandardsIgnoreLine
			$this->data['base'] = $current_screen->base;
		} else {
			$this->data['base'] = $pagenow;
		}

		$this->data['pagenow'] = $pagenow;
		$this->data['current_screen'] = ( $current_screen ) ? get_object_vars( $current_screen ) : null;

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

		if ( ! empty( $this->data['current_screen'] ) and isset( $screens[ $this->data['current_screen']['base'] ] ) ) {

			$list_table = array();

			# And now, WordPress' legendary inconsistency comes into play:

			if ( ! empty( $this->data['current_screen']['taxonomy'] ) ) {
				$list_table['column'] = $this->data['current_screen']['taxonomy'];
			} elseif ( ! empty( $this->data['current_screen']['post_type'] ) ) {
				$list_table['column'] = $this->data['current_screen']['post_type'] . '_posts';
			} else {
				$list_table['column'] = $this->data['current_screen']['base'];
			}

			if ( ! empty( $this->data['current_screen']['post_type'] ) and empty( $this->data['current_screen']['taxonomy'] ) ) {
				$list_table['columns'] = $this->data['current_screen']['post_type'] . '_posts';
			} else {
				$list_table['columns'] = $this->data['current_screen']['id'];
			}

			if ( 'edit-comments' === $list_table['column'] ) {
				$list_table['column'] = 'comments';
			} elseif ( 'upload' === $list_table['column'] ) {
				$list_table['column'] = 'media';
			} elseif ( 'link-manager' === $list_table['column'] ) {
				$list_table['column'] = 'link';
			}

			$list_table['sortables'] = $this->data['current_screen']['id'];

			$this->data['list_table'] = array(
				'columns_filter'   => "manage_{$list_table['columns']}_columns",
				'sortables_filter' => "manage_{$list_table['sortables']}_sortable_columns",
				'column_action'    => "manage_{$list_table['column']}_custom_column",
			);
			$this->data['list_table_markup'] = array(
				'columns_filter'   => 'manage_<span class="qm-current">' . esc_html( $list_table['columns'] ) . '</span>_columns',
				'sortables_filter' => 'manage_<span class="qm-current">' . esc_html( $list_table['sortables'] ) . '</span>_sortable_columns',
				'column_action'    => 'manage_<span class="qm-current">' . esc_html( $list_table['column'] ) . '</span>_custom_column',
			);

		}

	}

}

function register_qm_collector_admin( array $collectors, QueryMonitor $qm ) {
	$collectors['admin'] = new QM_Collector_Admin;
	return $collectors;
}

if ( is_admin() ) {
	add_filter( 'qm/collectors', 'register_qm_collector_admin', 10, 2 );
}
