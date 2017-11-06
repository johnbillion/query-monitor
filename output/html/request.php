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

class QM_Output_Html_Request extends QM_Output_Html {

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 50 );
	}

	public function output() {

		$data = $this->collector->get_data();

		$db_queries = QM_Collectors::get( 'db_queries' );

		echo '<div class="qm qm-half" id="' . esc_attr( $this->collector->id() ) . '">';
		echo '<table cellspacing="0">';
		echo '<caption class="screen-reader-text">' . esc_html( $this->collector->name() ) . '</caption>';
		echo '<thead class="screen-reader-text">';
		echo '<tr>';
		echo '<th scope="col">' . esc_html__( 'Property', 'query-monitor' ) . '</th>';
		echo '<th scope="col" colspan="2">' . esc_html__( 'Value', 'query-monitor' ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		foreach ( array(
			'request'       => __( 'Request', 'query-monitor' ),
			'matched_rule'  => __( 'Matched Rule', 'query-monitor' ),
			'matched_query' => __( 'Matched Query', 'query-monitor' ),
			'query_string'  => __( 'Query String', 'query-monitor' ),
		) as $item => $name ) {

			if ( ! isset( $data['request'][ $item ] ) ) {
				continue;
			}

			if ( ! empty( $data['request'][ $item ] ) ) {
				if ( in_array( $item, array( 'request', 'matched_query', 'query_string' ), true ) ) {
					$value = self::format_url( $data['request'][ $item ] );
				} else {
					$value = esc_html( $data['request'][ $item ] );
				}
			} else {
				$value = '<em>' . esc_html__( 'none', 'query-monitor' ) . '</em>';
			}

			echo '<tr>';
			echo '<th>' . esc_html( $name ) . '</th>';
			echo '<td colspan="2" class="qm-ltr qm-wrap">' . $value . '</td>'; // WPCS: XSS ok.
			echo '</tr>';
		}

		echo '</tbody>';

		$rowspan = isset( $data['qvars'] ) ? count( $data['qvars'] ) : 1;

		echo '<tbody class="qm-group">';
		echo '<tr>';
		echo '<th rowspan="' . absint( $rowspan ) . '">';
		esc_html_e( 'Query Vars', 'query-monitor' );

		if ( $db_queries ) {
			$db_queries_data = $db_queries->get_data();
			if ( ! empty( $db_queries_data['dbs']['$wpdb']->has_main_query ) ) {
				echo '<br>';
				printf(
					'<a href="#" class="qm-filter-trigger" data-qm-target="db_queries-wpdb" data-qm-filter="caller" data-qm-value="qm-main-query">%s</a>',
					esc_html__( 'View Main Query', 'query-monitor' )
				);
			}
		}

		echo '</th>';

		if ( ! empty( $data['qvars'] ) ) {

			$first = true;

			foreach ( $data['qvars'] as $var => $value ) {

				if ( ! $first ) {
					echo '<tr>';
				}

				if ( isset( $data['plugin_qvars'][ $var ] ) ) {
					echo '<td class="qm-ltr"><span class="qm-current">' . esc_html( $var ) . '</span></td>';
				} else {
					echo '<td class="qm-ltr">' . esc_html( $var ) . '</td>';
				}

				if ( is_array( $value ) or is_object( $value ) ) {
					echo '<td class="qm-ltr"><pre>';
					echo esc_html( print_r( $value, true ) );
					echo '</pre></td>';
				} else {
					echo '<td class="qm-ltr qm-wrap">' . esc_html( $value ) . '</td>';
				}

				echo '</tr>';

				$first = false;

			}
		} else {

			echo '<td colspan="2"><em>' . esc_html__( 'none', 'query-monitor' ) . '</em></td>';
			echo '</tr>';

		}

		echo '</tbody>';

		if ( ! empty( $data['queried_object'] ) ) {

			echo '<tbody class="qm-group">';
			echo '<tr>';
			echo '<th>' . esc_html__( 'Queried Object', 'query-monitor' ) . '</th>';
			echo '<td colspan="2" class="qm-has-inner qm-has-toggle qm-ltr"><div class="qm-toggler">';

			printf( // WPCS: XSS ok.
				'<div class="qm-inner-toggle">%1$s (%2$s) %3$s</div>',
				esc_html( $data['queried_object']['title'] ),
				esc_html( get_class( $data['queried_object']['data'] ) ),
				$this->build_toggler()
			);

			echo '<div class="qm-toggled">';
			self::output_inner( $data['queried_object']['data'] );
			echo '</div>';

			echo '</div></td>';
			echo '</tr>';
			echo '</tbody>';

		}

		echo '<tbody class="qm-group">';
		echo '<tr>';
		echo '<th>' . esc_html__( 'User', 'query-monitor' ) . '</th>';

		if ( $data['user']['data'] ) {
			echo '<td colspan="2" class="qm-has-inner qm-has-toggle qm-ltr"><div class="qm-toggler">';

			printf( // WPCS: XSS ok.
				'<div class="qm-inner-toggle">%1$s %2$s</div>',
				esc_html( $data['user']['title'] ),
				$this->build_toggler()
			);

			echo '<div class="qm-toggled">';
			self::output_inner( $data['user']['data'] );
			echo '</div>';

			echo '</div></td>';
		} else {
			echo '<td colspan="2">';
			esc_html_e( 'No current user', 'query-monitor' );
			echo '</td>';
		}

		echo '</tr>';

		echo '</tbody>';

		if ( ! empty( $data['multisite'] ) ) {

			$rowspan = count( $data['multisite'] );

			echo '<tbody class="qm-group">';
			echo '<tr>';
			echo '<th rowspan="' . absint( $rowspan ) . '">' . esc_html__( 'Multisite', 'query-monitor' ) . '</th>';

			$first = true;

			foreach ( $data['multisite'] as $var => $value ) {

				if ( ! $first ) {
					echo '<tr>';
				}

				echo '<td colspan="2" class="qm-has-inner qm-has-toggle qm-ltr"><div class="qm-toggler">';

				printf( // WPCS: XSS ok.
					'<div class="qm-inner-toggle">%1$s %2$s</div>',
					esc_html( $value['title'] ),
					$this->build_toggler()
				);

				echo '<div class="qm-toggled">';
				self::output_inner( $value['data'] );
				echo '</div>';

				echo '</div></td>';

				echo '</tr>';

				$first = false;

			}
			echo '</tbody>';
		}

		echo '</table>';
		echo '</div>';

	}

	public function admin_menu( array $menu ) {

		$data  = $this->collector->get_data();
		$count = isset( $data['plugin_qvars'] ) ? count( $data['plugin_qvars'] ) : 0;

		$title = ( empty( $count ) )
			? __( 'Request', 'query-monitor' )
			/* translators: %s: Number of additional query variables */
			: __( 'Request (+%s)', 'query-monitor' );

		$menu[] = $this->menu( array(
			'title' => esc_html( sprintf(
				$title,
				number_format_i18n( $count )
			) ),
		) );
		return $menu;

	}

}

function register_qm_output_html_request( array $output, QM_Collectors $collectors ) {
	if ( $collector = QM_Collectors::get( 'request' ) ) {
		$output['request'] = new QM_Output_Html_Request( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_request', 60, 2 );
