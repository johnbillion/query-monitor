<?php declare(strict_types = 1);
/**
 * General overview output for HTML pages.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QM_Output_Html_Overview extends QM_Output_Html {

	/**
	 * Collector instance.
	 *
	 * @var QM_Collector_Overview Collector.
	 */
	protected $collector;

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/title', array( $this, 'admin_title' ), 10 );
	}

	/**
	 * @return string
	 */
	public function name() {
		return __( 'Overview', 'query-monitor' );
	}

	/**
	 * @return void
	 */
	public function output() {
		/** @var QM_Data_Overview $data */
		$data = $this->collector->get_data();

		$db_query_num = null;
		/** @var QM_Collector_DB_Queries|null $db_queries */
		$db_queries = QM_Collectors::get( 'db_queries' );

		if ( $db_queries ) {
			/** @var QM_Data_DB_Queries $db_queries_data */
			$db_queries_data = $db_queries->get_data();
			if ( ! empty( $db_queries_data->types ) ) {
				$db_query_num = $db_queries_data->types;
			}
		}

		/** @var QM_Collector_Raw_Request|null $raw_request */
		$raw_request = QM_Collectors::get( 'raw_request' );

		/** @var QM_Collector_Cache|null $cache */
		$cache = QM_Collectors::get( 'cache' );

		/** @var QM_Collector_HTTP|null $http */
		$http = QM_Collectors::get( 'http' );

		$qm_broken = __( 'A JavaScript problem on the page is preventing Query Monitor from working correctly. jQuery may have been blocked from loading.', 'query-monitor' );
		$ajax_errors = __( 'PHP errors were triggered during an Ajax request. See your browser developer console for details.', 'query-monitor' );

		$this->before_non_tabular_output();

		echo '<section id="qm-broken">';
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<p class="qm-warn">' . QueryMonitor::icon( 'warning' ) . esc_html( $qm_broken ) . '</p>';
		echo '</section>';

		echo '<section id="qm-ajax-errors">';
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<p class="qm-warn">' . QueryMonitor::icon( 'warning' ) . esc_html( $ajax_errors ) . '</p>';
		echo '</section>';

		if ( $raw_request ) {
			echo '<section id="qm-overview-raw-request">';
			/** @var QM_Data_Raw_Request $raw_data */
			$raw_data = $raw_request->get_data();

			if ( ! empty( $raw_data->response['status'] ) ) {
				$status = $raw_data->response['status'];
			} else {
				$status = __( 'Unknown HTTP Response Code', 'query-monitor' );
			}

			printf(
				'<h3>%1$s %2$s → %3$s</h3>',
				esc_html( $raw_data->request['method'] ),
				esc_html( $raw_data->request['url'] ),
				esc_html( $status )
			);
			echo '</section>';
		}

		echo '</div>';
		echo '<div class="qm-grid">';

		echo '<section>';
		echo '<h3>' . esc_html__( 'Page Generation Time', 'query-monitor' ) . '</h3>';
		echo '<p>';
		echo esc_html(
			sprintf(
				/* translators: %s: A time in seconds with a decimal fraction. No space between value and unit. */
				_x( '%ss', 'Time in seconds', 'query-monitor' ),
				number_format_i18n( $data->time_taken, 4 )
			)
		);

		if ( $data->time_limit > 0 ) {
			if ( $data->display_time_usage_warning ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo '<br><span class="qm-warn">' . QueryMonitor::icon( 'warning' );
			} else {
				echo '<br><span class="qm-info">';
			}
			echo esc_html( sprintf(
				/* translators: 1: Percentage of time limit used, 2: Time limit in seconds */
				__( '%1$s%% of %2$ss limit', 'query-monitor' ),
				number_format_i18n( $data->time_usage, 1 ),
				number_format_i18n( $data->time_limit )
			) );
			echo '</span>';
		} else {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '<br><span class="qm-warn">' . QueryMonitor::icon( 'warning' );
			printf(
				/* translators: 1: Name of the PHP directive, 2: Value of the PHP directive */
				esc_html__( 'No execution time limit. The %1$s PHP configuration directive is set to %2$s.', 'query-monitor' ),
				'<code>max_execution_time</code>',
				'0'
			);
			echo '</span>';
		}
		echo '</p>';
		echo '</section>';

		echo '<section>';
		echo '<h3>' . esc_html__( 'Peak Memory Usage', 'query-monitor' ) . '</h3>';
		echo '<p>';

		if ( empty( $data->memory ) ) {
			esc_html_e( 'Unknown', 'query-monitor' );
		} else {
			echo esc_html( sprintf(
				/* translators: 1: Memory used in bytes, 2: Memory used in megabytes */
				__( '%1$s bytes (%2$s MB)', 'query-monitor' ),
				number_format_i18n( $data->memory ),
				number_format_i18n( ( $data->memory / 1024 / 1024 ), 1 )
			) );

			if ( $data->memory_limit > 0 ) {
				if ( $data->display_memory_usage_warning ) {
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo '<br><span class="qm-warn">' . QueryMonitor::icon( 'warning' );
				} else {
					echo '<br><span class="qm-info">';
				}
				echo esc_html( sprintf(
					/* translators: 1: Percentage of memory limit used, 2: Memory limit in megabytes */
					__( '%1$s%% of %2$s MB server limit', 'query-monitor' ),
					number_format_i18n( $data->memory_usage, 1 ),
					number_format_i18n( $data->memory_limit / 1024 / 1024 )
				) );
				echo '</span>';
			} else {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo '<br><span class="qm-warn">' . QueryMonitor::icon( 'warning' );
				printf(
					/* translators: 1: Name of the PHP directive, 2: Value of the PHP directive */
					esc_html__( 'No memory limit. The %1$s PHP configuration directive is set to %2$s.', 'query-monitor' ),
					'<code>memory_limit</code>',
					'0'
				);
				echo '</span>';
			}
		}

		echo '</p>';
		echo '</section>';

		echo '<section>';
		echo '<h3>' . esc_html__( 'Database Queries', 'query-monitor' ) . '</h3>';

		if ( isset( $db_query_num, $db_queries_data ) ) {
			echo '<p>';
			echo esc_html(
				sprintf(
					/* translators: %s: A time in seconds with a decimal fraction. No space between value and unit. */
					_x( '%ss', 'Time in seconds', 'query-monitor' ),
					number_format_i18n( $db_queries_data->total_time, 4 )
				)
			);
			echo '</p>';

			echo '<p>';

			if ( ! isset( $db_query_num['SELECT'] ) || count( $db_query_num ) > 1 ) {
				foreach ( $db_query_num as $type_name => $type_count ) {
					$label = sprintf(
						'%1$s: %2$s',
						esc_html( $type_name ),
						esc_html( number_format_i18n( $type_count ) )
					);
					echo self::build_filter_trigger( 'db_queries', 'type', (string) $type_name, esc_html( $label ) ); // WPCS: XSS ok;
					echo '<br>';
				}
			}

			$label = sprintf(
				'%1$s: %2$s',
				esc_html( _x( 'Total', 'database queries', 'query-monitor' ) ),
				esc_html( number_format_i18n( $db_queries_data->total_qs ) )
			);
			echo self::build_filter_trigger( 'db_queries', 'type', '', esc_html( $label ) ); // WPCS: XSS ok;

			echo '</p>';
		} else {
			printf(
				'<p><em>%s</em></p>',
				esc_html__( 'None', 'query-monitor' )
			);
		}

		echo '</section>';

		if ( $http ) {
			echo '<section>';
			echo '<h3>' . esc_html__( 'HTTP API Calls', 'query-monitor' ) . '</h3>';

			$http_data = $http->get_data();

			if ( ! empty( $http_data->http ) ) {
				echo '<p>';
				echo esc_html(
					sprintf(
						/* translators: %s: A time in seconds with a decimal fraction. No space between value and unit. */
						_x( '%ss', 'Time in seconds', 'query-monitor' ),
						number_format_i18n( $http_data->ltime, 4 )
					)
				);
				echo '</p>';

				$label = sprintf(
					'%1$s: %2$s',
					esc_html( _x( 'Total', 'HTTP API calls', 'query-monitor' ) ),
					esc_html( number_format_i18n( count( $http_data->http ) ) )
				);
				echo self::build_filter_trigger( 'http', 'type', '', esc_html( $label ) ); // WPCS: XSS ok;
			} else {
				printf(
					'<p><em>%s</em></p>',
					esc_html__( 'None', 'query-monitor' )
				);
			}

			echo '</section>';
		}

		echo '<section>';
		echo '<h3>' . esc_html__( 'Object Cache', 'query-monitor' ) . '</h3>';

		if ( $cache ) {
			/** @var QM_Data_Cache $cache_data */
			$cache_data = $cache->get_data();

			if ( ! empty( $cache_data->stats ) && ! empty( $cache_data->cache_hit_percentage ) ) {
				$cache_hit_percentage = $cache_data->cache_hit_percentage;

				echo '<p>';
				echo esc_html( sprintf(
					/* translators: 1: Cache hit rate percentage, 2: number of cache hits, 3: number of cache misses */
					__( '%1$s%% hit rate (%2$s hits, %3$s misses)', 'query-monitor' ),
					number_format_i18n( $cache_hit_percentage, 1 ),
					number_format_i18n( $cache_data->stats['cache_hits'], 0 ),
					number_format_i18n( $cache_data->stats['cache_misses'], 0 )
				) );
				echo '</p>';
			}

			if ( $cache_data->has_object_cache ) {
				echo '<p><span class="qm-info">';
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo self::build_link(
					network_admin_url( 'plugins.php?plugin_status=dropins' ),
					esc_html__( 'Persistent object cache plugin in use', 'query-monitor' )
				);
				echo '</span></p>';
			} else {
				echo '<p><span class="qm-warn">';
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo QueryMonitor::icon( 'warning' );
				echo esc_html__( 'Persistent object cache plugin not in use', 'query-monitor' );
				echo '</span></p>';

				$potentials = array_filter( $cache_data->object_cache_extensions );

				if ( ! empty( $potentials ) ) {
					foreach ( $potentials as $name => $value ) {
						$url = sprintf(
							'https://wordpress.org/plugins/search/%s/',
							strtolower( $name )
						);
						echo '<p>';
						echo wp_kses(
							sprintf(
								/* translators: 1: PHP extension name, 2: URL to plugin directory */
								__( 'The %1$s object cache extension for PHP is installed but is not in use by WordPress. You should <a href="%2$s" target="_blank" class="qm-external-link">install a %1$s plugin</a>.', 'query-monitor' ),
								esc_html( $name ),
								esc_url( $url )
							),
							array(
								'a' => array(
									'href' => array(),
									'target' => array(),
									'class' => array(),
								),
							)
						);
						echo '</p>';
					}
				} else {
					echo '<p>';
					echo esc_html__( 'Speak to your web host about enabling an object cache extension such as Redis or Memcached.', 'query-monitor' );
					echo '</p>';
				}
			}
		} else {
			echo '<p>';
			echo esc_html__( 'Object cache statistics are not available', 'query-monitor' );
			echo '</p>';
		}

		echo '</section>';

		if ( $cache ) {
			/** @var QM_Data_Cache $cache_data */
			$cache_data = $cache->get_data();

			echo '<section>';
			echo '<h3>' . esc_html__( 'Opcode Cache', 'query-monitor' ) . '</h3>';

			if ( $cache_data->has_opcode_cache ) {
				foreach ( array_filter( $cache_data->opcode_cache_extensions ) as $opcache_name => $opcache_state ) {
					echo '<p>';
					echo esc_html( sprintf(
						/* translators: %s: Name of cache driver */
						__( 'Opcode cache in use: %s', 'query-monitor' ),
						$opcache_name
					) );
					echo '</p>';
				}
			} else {
				echo '<p><span class="qm-warn">';
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo QueryMonitor::icon( 'warning' );
				echo esc_html__( 'Opcode cache not in use', 'query-monitor' );
				echo '</span></p>';
				echo '<p>';
				echo esc_html__( 'Speak to your web host about enabling an opcode cache such as OPcache.', 'query-monitor' );
				echo '</p>';
		}

			echo '</section>';
		}

		$this->after_non_tabular_output();
	}

	/**
	 * @param array<int, string> $title
	 * @return array<int, string>
	 */
	public function admin_title( array $title ) {
		/** @var QM_Data_Overview $data */
		$data = $this->collector->get_data();

		if ( empty( $data->memory ) ) {
			$memory = '??';
		} else {
			$memory = number_format_i18n( ( $data->memory / 1024 / 1024 ), 1 );
		}

		$title[] = sprintf(
			/* translators: %s: A time in seconds with a decimal fraction. No space between value and unit symbol. */
			esc_html_x( '%ss', 'Time in seconds', 'query-monitor' ),
			number_format_i18n( $data->time_taken, 2 )
		);
		$title[] = preg_replace( '#\s?([^0-9,\.]+)#', '<small>$1</small>', sprintf(
			/* translators: %s: Memory usage in megabytes with a decimal fraction. Note the space between value and unit symbol. */
			esc_html__( '%s MB', 'query-monitor' ),
			$memory
		) );

		return $title;
	}

}

/**
 * @param array<string, QM_Output> $output
 * @param QM_Collectors $collectors
 * @return array<string, QM_Output>
 */
function register_qm_output_html_overview( array $output, QM_Collectors $collectors ) {
	$collector = QM_Collectors::get( 'overview' );
	if ( $collector ) {
		$output['overview'] = new QM_Output_Html_Overview( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_overview', 10, 2 );
