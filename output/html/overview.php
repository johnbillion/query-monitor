<?php
/*
Copyright 2009-2016 John Blackbourn

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

*/

class QM_Output_Html_Overview extends QM_Output_Html {

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/title', array( $this, 'admin_title' ), 10 );
	}

	public function output() {

		$data = $this->collector->get_data();

		$db_query_num   = null;
		$db_query_types = array();
		$db_queries     = QM_Collectors::get( 'db_queries' );

		if ( $db_queries ) {
			# @TODO: make this less derpy:
			$db_queries_data = $db_queries->get_data();
			if ( isset( $db_queries_data['types'] ) && isset( $db_queries_data['total_time'] ) ) {
				$db_query_num = $db_queries_data['types'];
			}
		}

		$cache = QM_Collectors::get( 'cache' );

		if ( $cache ) {
			$cache_data = $cache->get_data();
			if ( isset( $cache_data['stats'] ) && isset( $cache_data['cache_hit_percentage'] ) ) {
				$cache_hit_percentage = $cache_data['cache_hit_percentage'];
			}
		}

		echo '<div class="qm" id="' . esc_attr( $this->collector->id() ) . '">';
		echo '<table cellspacing="0">';

		echo '<thead>';
		echo '<tr>';
		echo '<th scope="col">' . esc_html__( 'Page generation time', 'query-monitor' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'Peak memory usage', 'query-monitor' ) . '</th>';
		if ( isset( $db_query_num ) ) {
			echo '<th scope="col">' . esc_html__( 'Database query time', 'query-monitor' ) . '</th>';
			echo '<th scope="col">' . esc_html__( 'Database queries', 'query-monitor' ) . '</th>';
		}
		if ( isset( $cache_hit_percentage ) ) {
			echo '<th scope="col">' . esc_html__( 'Object cache', 'query-monitor' ) . '</th>';
		}
		echo '</tr>';
		echo '</thead>';

		echo '<tbody>';
		echo '<tr>';
		echo '<td>';
		echo esc_html( number_format_i18n( $data['time'], 4 ) );
		echo '<br><span class="qm-info">';
		echo esc_html( sprintf(
			__( '%1$s%% of %2$ss limit', 'query-monitor' ),
			number_format_i18n( $data['time_usage'], 1 ),
			number_format_i18n( $data['time_limit'] )
		) );
		echo '</span>';
		echo '</td>';

		if ( empty( $data['memory'] ) ) {
			echo '<td><em>' . esc_html__( 'Unknown', 'query-monitor' ) . '</em></td>';
		} else {
			echo '<td>';
			echo esc_html( sprintf(
				__( '%s kB', 'query-monitor' ),
				number_format_i18n( $data['memory'] / 1024 )
			) );
			echo '<br><span class="qm-info">';
			echo esc_html( sprintf(
				__( '%1$s%% of %2$s kB limit', 'query-monitor' ),
				number_format_i18n( $data['memory_usage'], 1 ),
				number_format_i18n( $data['memory_limit'] / 1024 )
			) );
			echo '</span>';
			echo '</td>';
		}

		if ( isset( $db_query_num ) ) {
			echo '<td>';
			echo esc_html( number_format_i18n( $db_queries_data['total_time'], 4 ) );
			echo '</td>';
			echo '<td>';

			foreach ( $db_query_num as $type_name => $type_count ) {
				$db_query_types[] = sprintf( '%1$s: %2$s', $type_name, number_format_i18n( $type_count ) );
			}

			echo implode( '<br>', array_map( 'esc_html', $db_query_types ) );

			echo '</td>';
		}

		if ( isset( $cache_hit_percentage ) ) {
			echo '<td>';
			echo esc_html( sprintf(
				/* translators: %s: Cache hit rate percentage */
				__( '%s%% hit rate', 'query-monitor' ),
				number_format_i18n( $cache_hit_percentage, 1 )
			) );
			echo '<br>' . esc_html( sprintf(
				/* translators: %s: External object cache status */
				__( 'External object cache: %s'),
				( $cache_data['ext_object_cache'] ? 'true' : 'false' )
			) );
			echo '</td>';
		}

		echo '</tr>';
		echo '</tbody>';

		echo '</table>';
		echo '</div>';

	}

	public function admin_title( array $title ) {

		$data = $this->collector->get_data();

		if ( empty( $data['memory'] ) ) {
			$memory = '??';
		} else {
			$memory = number_format_i18n( ( $data['memory'] / 1024 / 1024 ), 2 );
		}

		$title[] = sprintf(
			/* translators: %s: Page load time in seconds */
			_x( '%s<small>S</small>', 'Page load time', 'query-monitor' ),
			number_format_i18n( $data['time'], 2 )
		);
		$title[] = sprintf(
			/* translators: %s: Memory usage in megabytes */
			_x( '%s<small>MB</small>', 'Memory usage', 'query-monitor' ),
			$memory
		);
		return $title;
	}

}

function register_qm_output_html_overview( array $output, QM_Collectors $collectors ) {
	if ( $collector = QM_Collectors::get( 'overview' ) ) {
		$output['overview'] = new QM_Output_Html_Overview( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_overview', 10, 2 );
