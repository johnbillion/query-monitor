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

class QM_Component_Transients extends QM_Component {

	var $id = 'transients';

	function __construct() {
		parent::__construct();
		# See http://core.trac.wordpress.org/ticket/24583
		add_action( 'setted_site_transient', array( $this, 'setted_site_transient' ), 10, 3 );
		add_action( 'setted_transient',      array( $this, 'setted_blog_transient' ), 10, 3 );
		add_filter( 'query_monitor_menus',   array( $this, 'admin_menu' ), 70 );
	}

	function setted_site_transient( $transient, $value = null, $expiration = null ) {
		$this->setted_transient( $transient, 'site', $value, $expiration );
	}

	function setted_blog_transient( $transient, $value = null, $expiration = null ) {
		$this->setted_transient( $transient, 'blog', $value, $expiration );
	}

	function setted_transient( $transient, $type, $value = null, $expiration = null ) {
		$trace = new QM_Backtrace( array(
			'ignore_items' => 1 # Ignore the setted_(site|blog)_transient method
		) );
		$this->data['trans'][] = array(
			'transient'  => $transient,
			'trace'      => $trace,
			'type'       => $type,
			'value'      => $value,
			'expiration' => $expiration,
		);
	}

	function output_html( array $args, array $data ) {

		echo '<div class="qm" id="' . $args['id'] . '">';
		echo '<table cellspacing="0">';
		echo '<thead>';
		echo '<tr>';
		echo '<th>' . __( 'Transient Set', 'query-monitor' ) . '</th>';
		if ( is_multisite() )
			echo '<th>' . __( 'Type', 'query-monitor' ) . '</th>';
		if ( !empty( $data['trans'] ) and !is_null( $data['trans'][0]['expiration'] ) )
			echo '<th>' . __( 'Expiration', 'query-monitor' ) . '</th>';
		echo '<th>' . __( 'Call Stack', 'query-monitor' ) . '</th>';
		echo '<th>' . __( 'Component', 'query-monitor' ) . '</th>';
		echo '</tr>';
		echo '</thead>';

		if ( !empty( $data['trans'] ) ) {

			echo '<tbody>';

			foreach ( $data['trans'] as $row ) {
				$stack = $row['trace']->get_stack();
				$transient = str_replace( array(
					'_site_transient_',
					'_transient_'
				), '', $row['transient'] );
				$type = ( is_multisite() ) ? "<td valign='top'>{$row['type']}</td>\n" : '';
				if ( 0 === $row['expiration'] )
					$row['expiration'] = '<em>' . __( 'none', 'query-monitor' ) . '</em>';
				$expiration = ( !is_null( $row['expiration'] ) ) ? "<td valign='top'>{$row['expiration']}</td>\n" : '';

				foreach ( $stack as & $trace ) {
					foreach ( array( 'set_transient', 'set_site_transient' ) as $skip ) {
						if ( 0 === strpos( $trace, $skip ) ) {
							$trace = sprintf( '<span class="qm-na">%s</span>', $trace );
							break;
						}
					}
				}

				$component = QM_Util::get_backtrace_component( $row['trace'] );

				$stack = implode( '<br />', $stack );
				echo "
					<tr>\n
						<td valign='top'>{$transient}</td>\n
						{$type}
						{$expiration}
						<td valign='top' class='qm-ltr'>{$stack}</td>\n
						<td valign='top'>{$component->name}</td>\n
					</tr>\n
				";
			}

			echo '</tbody>';

		} else {

			echo '<tbody>';
			echo '<tr>';
			echo '<td colspan="4" style="text-align:center !important"><em>' . __( 'none', 'query-monitor' ) . '</em></td>';
			echo '</tr>';
			echo '</tbody>';

		}

		echo '</table>';
		echo '</div>';

	}

	function admin_menu( array $menu ) {

		$count = isset( $this->data['trans'] ) ? count( $this->data['trans'] ) : 0;

		$title = ( empty( $count ) )
			? __( 'Transients Set', 'query-monitor' )
			: __( 'Transients Set (%s)', 'query-monitor' );

		$menu[] = $this->menu( array(
			'title' => sprintf( $title, number_format_i18n( $count ) )
		) );
		return $menu;

	}


}

function register_qm_transients( array $qm ) {
	$qm['transients'] = new QM_Component_Transients;
	return $qm;
}

add_filter( 'query_monitor_components', 'register_qm_transients', 100 );
