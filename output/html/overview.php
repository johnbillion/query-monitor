<?php
/**
 * General overview output for HTML pages.
 *
 * @package query-monitor
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

		$qm_broken = __( 'A JavaScript problem on the page is preventing Query Monitor from working correctly. jQuery may have been blocked from loading.', 'query-monitor' );

		echo '<div class="qm qm-non-tabular" id="' . esc_attr( $this->collector->id() ) . '">';
		echo '<div class="qm-boxed" id="qm-broken">';

		echo '<div class="qm-section">';
		echo '<h2 class="qm-warn"><span class="dashicons dashicons-warning" aria-hidden="true"></span>' . esc_html( $qm_broken ) . '</h2>';
		echo '</div>';

		echo '</div>';
		echo '<div class="qm-boxed qm-boxed-wrap">';

		echo '<div class="qm-section">';
		echo '<h2>' . esc_html__( 'Page Generation Time', 'query-monitor' ) . '</h2>';
		echo '<p class="qm-item">';
		echo esc_html( number_format_i18n( $data['time_taken'], 4 ) );
		echo '<br><span class="qm-info">';
		echo esc_html( sprintf(
			/* translators: 1: Percentage of time limit used, 2: Time limit in seconds */
			__( '%1$s%% of %2$ss limit', 'query-monitor' ),
			number_format_i18n( $data['time_usage'], 1 ),
			number_format_i18n( $data['time_limit'] )
		) );
		echo '</span>';
		echo '</p>';
		echo '</div>';

		echo '<div class="qm-section">';
		echo '<h2>' . esc_html__( 'Peak Memory Usage', 'query-monitor' ) . '</h2>';
		echo '<p class="qm-item">';

		if ( empty( $data['memory'] ) ) {
			esc_html_e( 'Unknown', 'query-monitor' );
		} else {
			echo esc_html( sprintf(
				/* translators: %s: Memory used in kilobytes */
				__( '%s kB', 'query-monitor' ),
				number_format_i18n( $data['memory'] / 1024 )
			) );
			echo '<br><span class="qm-info">';
			echo esc_html( sprintf(
				/* translators: 1: Percentage of memory limit used, 2: Memory limit in kilobytes */
				__( '%1$s%% of %2$s kB limit', 'query-monitor' ),
				number_format_i18n( $data['memory_usage'], 1 ),
				number_format_i18n( $data['memory_limit'] / 1024 )
			) );
			echo '</span>';
		}

		echo '</p>';
		echo '</div>';

		if ( isset( $db_query_num ) ) {
			echo '<div class="qm-section">';
			echo '<h2>' . esc_html__( 'Database Query Time', 'query-monitor' ) . '</h2>';
			echo '<p class="qm-item">';
			echo esc_html( number_format_i18n( $db_queries_data['total_time'], 4 ) );
			echo '</p>';
			echo '</div>';

			echo '<div class="qm-section">';
			echo '<h2>' . esc_html__( 'Database Queries', 'query-monitor' ) . '</h2>';
			echo '<p class="qm-item">';

			if ( ! isset( $db_query_num['SELECT'] ) || count( $db_query_num ) > 1 ) {
				foreach ( $db_query_num as $type_name => $type_count ) {
					printf(
						'<a href="#" class="qm-filter-trigger" data-qm-target="db_queries-wpdb" data-qm-filter="type" data-qm-value="%1$s">%2$s: %3$s</a><br>',
						esc_attr( $type_name ),
						esc_html( $type_name ),
						esc_html( number_format_i18n( $type_count ) )
					);
				}
			}

			echo esc_html__( 'Total', 'query-monitor' ) . ': ' . esc_html( number_format_i18n( $db_queries_data['total_qs'] ) );

			echo '</p>';
			echo '</div>';
		}

		echo '<div class="qm-section">';
		echo '<h2>' . esc_html__( 'Object Cache', 'query-monitor' ) . '</h2>';
		echo '<p class="qm-item">';

		if ( isset( $cache_hit_percentage ) ) {
			echo esc_html( sprintf(
				/* translators: 1: Cache hit rate percentage, 2: number of cache hits, 3: number of cache misses */
				__( '%1$s%% hit rate (%2$s hits, %3$s misses)', 'query-monitor' ),
				number_format_i18n( $cache_hit_percentage, 1 ),
				number_format_i18n( $cache_data['stats']['cache_hits'], 0 ),
				number_format_i18n( $cache_data['stats']['cache_misses'], 0 )
			) );
			if ( $cache_data['display_hit_rate_warning'] ) {
				printf(
					'<br><a href="%s">%s</a>',
					'https://github.com/johnbillion/query-monitor/wiki/Cache-Hit-Rate',
					esc_html__( 'Why is this value 100%?', 'query-monitor' )
				);
			}
			if ( $cache_data['ext_object_cache'] ) {
				echo '<br><span class="qm-info">';
				printf(
					'<a href="%s">%s</a>',
					esc_url( network_admin_url( 'plugins.php?plugin_status=dropins' ) ),
					esc_html__( 'External object cache in use', 'query-monitor' )
				);
				echo '</span>';
			} else {
				echo '<br><span class="qm-warn">';
				echo esc_html__( 'External object cache not in use', 'query-monitor' );
				echo '</span>';
			}
		} else {
			echo '<span class="qm-info">';
			echo esc_html__( 'Object cache information is not available', 'query-monitor' );
			echo '</span>';
		}

		echo '</p>';
		echo '</div>';

		echo '</div>';
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
