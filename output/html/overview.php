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
		echo '<caption class="screen-reader-text">' . esc_html( $this->collector->name() ). '</caption>';
		echo '<thead>';
		echo '<tr>';
		echo '<th scope="col">' . esc_html__( 'Page generation time', 'query-monitor' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'Peak memory usage', 'query-monitor' ) . '</th>';
		if ( isset( $db_query_num ) ) {
			echo '<th scope="col">' . esc_html__( 'Database query time', 'query-monitor' ) . '</th>';
			echo '<th scope="col">' . esc_html__( 'Database queries', 'query-monitor' ) . '</th>';
		}
		echo '<th scope="col">' . esc_html__( 'Object cache', 'query-monitor' ) . '</th>';
		echo '</tr>';
		echo '</thead>';

		echo '<tbody>';
		echo '<tr>';
		echo '<td>';
		echo esc_html( number_format_i18n( $data['time_taken'], 4 ) );
		echo '<br><span class="qm-info">';
		echo esc_html( sprintf(
			/* translators: 1: Percentage of time limit used, 2: Time limit in seconds*/
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
				/* translators: %s: Memory used in kilobytes */
				__( '%s kB', 'query-monitor' ),
				number_format_i18n( $data['memory'] / 1024 )
			) );
			echo '<br><span class="qm-info">';
			echo esc_html( sprintf(
				/* translators: 1: Percentage of memory limit used, 2: Memory limit in kilobytes*/
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

		echo '<td>';
		if ( isset( $cache_hit_percentage ) ) {
			echo esc_html( sprintf(
				/* translators: 1: Cache hit rate percentage, 2: number of cache hits, 3: number of cache misses */
				__( '%s%% hit rate (%s hits, %s misses)', 'query-monitor' ),
				number_format_i18n( $cache_hit_percentage, 1 ),
				number_format_i18n( $cache_data['stats']['cache_hits'], 0 ),
				number_format_i18n( $cache_data['stats']['cache_misses'], 0 )
			) );
			if ( $cache_data['display_hit_rate_warning'] ) {
				printf(
					'<br><a href="%s">%s</a>',
					'https://github.com/johnbillion/query-monitor/wiki/Cache-Hit-Rate',
					esc_html__( "Why is this value 100%?", 'query-monitor' )
				);
			}
			echo '<br><span class="qm-info">';
			if ( $cache_data['ext_object_cache'] ) {
				printf(
					'<a href="%s">%s</a>',
					network_admin_url( 'plugins.php?plugin_status=dropins' ),
					esc_html__( 'External object cache in use', 'query-monitor' )
				);
			} else {
				echo esc_html__( 'External object cache not in use', 'query-monitor' );
			}
			echo '</span>';
		} else {
			echo '<span class="qm-info">';
			echo esc_html__( 'Object cache information is not available', 'query-monitor' );
			echo '</span>';
		}
		echo '</td>';

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
			$memory = number_format_i18n( ( $data['memory'] / 1024 ), 0 );
		}

		$title[] = sprintf(
			/* translators: %s: Page load time in seconds */
			esc_html_x( '%s S', 'Page load time', 'query-monitor' ),
			number_format_i18n( $data['time_taken'], 2 )
		);
		$title[] = sprintf(
			/* translators: %s: Memory usage in kilobytes */
			esc_html_x( '%s kB', 'Memory usage', 'query-monitor' ),
			$memory
		);

		foreach ( $title as &$t ) {
			$t = preg_replace( '#\s?([^0-9,\.]+)#', '<small>$1</small>', $t );
		}

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
