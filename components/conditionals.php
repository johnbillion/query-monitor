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

class QM_Component_Conditionals extends QM_Component {

	var $id = 'conditionals';

	function __construct() {
		parent::__construct();
		add_filter( 'query_monitor_menus', array( $this, 'admin_menu' ), 120 );
	}

	function admin_menu( array $menu ) {

		foreach ( $this->data['conds']['true'] as $cond ) {
			$menu[] = $this->menu( array(
				'title' => $cond . '()',
				'id'    => 'query-monitor-' . $cond,
				'meta'  => array( 'classname' => 'qm-true qm-ltr' )
			) );
		}

		return $menu;

	}

	function output_html( array $args, array $data ) {

		$cols = 5;
		$i = 0;
		$w = floor( 100 / $cols );

		echo '<div class="qm" id="' . $args['id'] . '">';
		echo '<table cellspacing="0">';
		echo '<thead>';
		echo '<tr>';
		echo '<th colspan="' . $cols . '">' . __( 'Conditionals', 'query-monitor' ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		foreach ( $data['conds']['true'] as $cond ) {
			$i++;
			if ( 1 === $i%$cols )
				echo '<tr>';
			echo '<td class="qm-ltr qm-true" width="' . $w . '%">' . $cond . '()</td>';
			if ( 0 === $i%$cols )
				echo '</tr>';
		}

		foreach ( $data['conds']['false'] as $cond ) {
			$i++;
			if ( 1 === $i%$cols )
				echo '<tr>';
			echo '<td class="qm-ltr qm-false" width="' . $w . '%">' . $cond . '()</td>';
			if ( 0 === $i%$cols )
				echo '</tr>';
		}
		$fill = ($cols-($i%$cols));
		if ( $fill ) {
			echo '<td colspan="' . $fill . '">&nbsp;</td>';
			echo '</tr>';
		}

		echo '</tbody>';
		echo '</table>';
		echo '</div>';

	}

	function process() {

		$conds = apply_filters( 'query_monitor_conditionals', array(
			'is_404', 'is_archive', 'is_admin', 'is_attachment', 'is_author', 'is_blog_admin', 'is_category', 'is_comments_popup', 'is_date',
			'is_day', 'is_feed', 'is_front_page', 'is_home', 'is_main_network', 'is_main_site', 'is_month', 'is_multitax', 'is_network_admin',
			'is_page', 'is_page_template', 'is_paged', 'is_post_type_archive', 'is_preview', 'is_robots', 'is_rtl', 'is_search', 'is_single',
			'is_singular', 'is_ssl', 'is_sticky', 'is_tag', 'is_tax', 'is_time', 'is_trackback', 'is_year'
		) );	

		$true = $false = $na = array();

		foreach ( $conds as $cond ) {
			if ( function_exists( $cond ) ) {

				if ( ( 'is_sticky' == $cond ) and !get_post( $id = null ) ) {
					# Special case for is_sticky to prevent PHP notices
					$false[] = $cond;
				} else if ( ( 'is_main_site' == $cond ) and !is_multisite() ) {
					# Special case for is_main_site to prevent it from being annoying on single site installs
					$na[] = $cond;
				} else {
					if ( call_user_func( $cond ) )
						$true[] = $cond;
					else
						$false[] = $cond;
				}

			} else {
				$na[] = $cond;
			}
		}
		$this->data['conds'] = compact( 'true', 'false', 'na' );

	}

}

function register_qm_conditionals( array $qm ) {
	$qm['conditionals'] = new QM_Component_Conditionals;
	return $qm;
}

add_filter( 'query_monitor_components', 'register_qm_conditionals', 40 );
