<?php declare(strict_types = 1);
/**
 * HTTP API request output for HTML pages.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QM_Output_Html_HTTP extends QM_Output_Html {

	/**
	 * Collector instance.
	 *
	 * @var QM_Collector_HTTP Collector.
	 */
	protected $collector;

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 90 );
		add_filter( 'qm/output/menu_class', array( $this, 'admin_class' ) );
	}

	/**
	 * @return string
	 */
	public function name() {
		return __( 'HTTP API Calls', 'query-monitor' );
	}

	/**
	 * @return void
	 */
	public function output() {
		/** @var QM_Data_HTTP $data */
		$data = $this->collector->get_data();

		if ( ! empty( $data->http ) ) {
			$statuses = array_keys( $data->types );
			$components = array_column( $data->component_times, 'component' );

			usort( $statuses, 'strcasecmp' );
			usort( $components, 'strcasecmp' );

			$status_output = array();
			$hosts = array_unique( array_column( $data->http, 'host' ) );
			sort( $hosts );

			foreach ( $statuses as $status ) {
				if ( 'error' === $status ) {
					$status_output['error'] = __( 'Error', 'query-monitor' );
				} elseif ( 'non-blocking' === $status ) {
					/* translators: A non-blocking HTTP API request */
					$status_output['non-blocking'] = __( 'Non-blocking', 'query-monitor' );
				} else {
					$status_output[] = $status;
				}
			}

			$this->before_tabular_output();

			echo '<thead>';
			echo '<tr>';
			echo '<th scope="col">' . esc_html__( 'Method', 'query-monitor' ) . '</th>';
			echo '<th scope="col" class="qm-filterable-column">';
			echo $this->build_filter( 'host', $hosts, __( 'URL', 'query-monitor' ) ); // WPCS: XSS ok.
			echo '</th>';
			echo '<th scope="col" class="qm-filterable-column">';
			echo $this->build_filter( 'type', $status_output, __( 'Status', 'query-monitor' ) ); // WPCS: XSS ok.
			echo '</th>';
			echo '<th scope="col">' . esc_html__( 'Caller', 'query-monitor' ) . '</th>';
			echo '<th scope="col" class="qm-filterable-column">';
			echo $this->build_filter( 'component', $components, __( 'Component', 'query-monitor' ) ); // WPCS: XSS ok.
			echo '</th>';
			echo '<th scope="col" class="qm-num">' . esc_html__( 'Response Size', 'query-monitor' ) . '</th>';
			echo '<th scope="col" class="qm-num">' . esc_html__( 'Timeout', 'query-monitor' ) . '</th>';
			echo '<th scope="col" class="qm-num">' . esc_html__( 'Time', 'query-monitor' ) . '</th>';
			echo '</tr>';
			echo '</thead>';

			echo '<tbody>';
			$i = 0;

			foreach ( $data->http as $row ) {
				$ltime = $row['ltime'];
				$i++;
				$is_error = false;
				$row_attr = array();
				$css = '';

				if ( $row['response'] instanceof WP_Error ) {
					$response = $row['response']->get_error_message();
					$is_error = true;
				} elseif ( ! $row['args']['blocking'] ) {
					/* translators: A non-blocking HTTP API request */
					$response = __( 'Non-blocking', 'query-monitor' );
				} else {
					$code = wp_remote_retrieve_response_code( $row['response'] );
					$msg = wp_remote_retrieve_response_message( $row['response'] );

					if ( intval( $code ) >= 400 ) {
						$is_error = true;
					}

					$response = $code . ' ' . $msg;

				}

				if ( $is_error ) {
					$css = 'qm-warn';
				}

				$url = self::format_url( $row['url'] );
				$info = '';

				if ( ! $row['local'] ) {
					$url = preg_replace( '|^http:|', '<span class="qm-warn">http</span>:', $url );

					if ( 'https' === parse_url( $row['url'], PHP_URL_SCHEME ) && empty( $row['args']['sslverify'] ) ) {
						$info .= '<span class="qm-warn">' . QueryMonitor::icon( 'warning' ) . esc_html( sprintf(
							/* translators: An HTTP API request has disabled certificate verification. 1: Relevant argument name */
							__( 'Certificate verification disabled (%s)', 'query-monitor' ),
							'sslverify=false'
						) ) . '</span><br>';
						$url = preg_replace( '|^https:|', '<span class="qm-warn">https</span>:', $url );
					}
				}

				$component = $row['component'];

				$stack = array();
				$filtered_trace = $row['filtered_trace'];

				foreach ( $filtered_trace as $frame ) {
					$stack[] = self::output_filename( $frame['display'], $frame['calling_file'], $frame['calling_line'] );
				}

				$row_attr['data-qm-component'] = $component->name;
				$row_attr['data-qm-type'] = $row['type'];
				$row_attr['data-qm-host'] = $row['host'];

				if ( ! $row['intercepted'] ) {
					$row_attr['data-qm-time'] = $row['ltime'];
				}

				if ( 'core' !== $component->context ) {
					$row_attr['data-qm-component'] .= ' non-core';
				}

				$attr = '';
				foreach ( $row_attr as $a => $v ) {
					$attr .= ' ' . $a . '="' . esc_attr( (string) $v ) . '"';
				}

				printf( // WPCS: XSS ok.
					'<tr %s class="%s">',
					$attr,
					esc_attr( $css )
				);
				printf(
					'<td>%s</td>',
					esc_html( $row['args']['method'] )
				);

				if ( $row['intercepted'] ) {
					$url = sprintf(
						'<span class="qm-warn">%1$s%2$s</span><br>',
						QueryMonitor::icon( 'warning' ),
						sprintf(
							/* translators: %s: The name of a filter that short-circuited an HTTP API request */
							esc_html__( 'This HTTP request was short-circuited by the %s filter and was not sent', 'query-monitor' ),
							'<code>pre_http_request</code>'
						)
					) . $url;
				}

				if ( ! empty( $row['redirected_to'] ) ) {
					$url .= sprintf(
						'<br><span class="qm-warn">%1$s%2$s</span><br>%3$s',
						QueryMonitor::icon( 'warning' ),
						/* translators: An HTTP API request redirected to another URL */
						esc_html__( 'This HTTP request was redirected to:', 'query-monitor' ),
						self::format_url( $row['redirected_to'] )
					);
				}

				printf( // WPCS: XSS ok.
					'<td class="qm-url qm-ltr qm-wrap">%s%s</td>',
					$info,
					$url
				);

				$size = '';
				$timeout = $row['args']['timeout'];

				if ( $row['intercepted'] ) {
					$ltime = 0;
					$timeout = '';
				} elseif ( isset( $row['info']['size_download'] ) ) {
					$size = sprintf(
						/* translators: %s: Memory used in kilobytes */
						__( '%s kB', 'query-monitor' ),
						number_format_i18n( $row['info']['size_download'] / 1024, 1 )
					);
				} elseif ( is_array( $row['response'] ) && isset( $row['response']['body'] ) ) {
					$size = sprintf(
						/* translators: %s: Memory used in kilobytes */
						__( '%s kB', 'query-monitor' ),
						number_format_i18n( strlen( $row['response']['body'] ) / 1024, 1 )
					);
				}

				if ( $row['intercepted'] && ! $is_error ) {
					echo '<td class="qm-col-status"></td>';
				} else {
					$show_toggle = ! empty( $row['info'] );

					echo '<td class="qm-has-toggle qm-col-status">';
					if ( $is_error ) {
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						echo QueryMonitor::icon( 'warning' );
					}
					echo esc_html( $response );

					if ( $show_toggle ) {
						echo self::build_toggler(); // WPCS: XSS ok;
						echo '<ul class="qm-toggled">';
					}

					if ( ! empty( $row['info'] ) ) {
						$time_fields = array(
							'namelookup_time' => __( 'DNS Resolution Time', 'query-monitor' ),
							'connect_time' => __( 'Connection Time', 'query-monitor' ),
							'starttransfer_time' => __( 'Transfer Start Time (TTFB)', 'query-monitor' ),
						);
						foreach ( $time_fields as $key => $value ) {
							if ( ! isset( $row['info'][ $key ] ) ) {
								continue;
							}
							printf(
								'<li><span class="qm-info qm-supplemental">%1$s: %2$s</span></li>',
								esc_html( $value ),
								esc_html( number_format_i18n( $row['info'][ $key ], 4 ) )
							);
						}

						$other_fields = array(
							'content_type' => __( 'Response Content Type', 'query-monitor' ),
							'primary_ip' => __( 'IP Address', 'query-monitor' ),
						);
						foreach ( $other_fields as $key => $value ) {
							if ( ! isset( $row['info'][ $key ] ) ) {
								continue;
							}
							printf(
								'<li><span class="qm-info qm-supplemental">%1$s: %2$s</span></li>',
								esc_html( $value ),
								esc_html( $row['info'][ $key ] )
							);
						}
					}

					if ( $show_toggle ) {
						echo '</ul>';
					}

					echo '</td>';
				}

				$caller = array_shift( $stack );

				echo '<td class="qm-has-toggle qm-nowrap qm-ltr">';

				if ( ! empty( $stack ) ) {
					echo self::build_toggler(); // WPCS: XSS ok;
				}

				echo '<ol>';

				echo "<li>{$caller}</li>"; // WPCS: XSS ok.

				if ( ! empty( $stack ) ) {
					echo '<div class="qm-toggled"><li>' . implode( '</li><li>', $stack ) . '</li></div>'; // WPCS: XSS ok.
				}

				echo '</ol></td>';

				printf(
					'<td class="qm-nowrap">%s</td>',
					esc_html( $component->name )
				);

				printf(
					'<td class="qm-nowrap qm-num">%s</td>',
					esc_html( $size )
				);

				printf(
					'<td class="qm-num">%s</td>',
					esc_html( $timeout )
				);

				if ( empty( $ltime ) ) {
					$stime = '';
				} else {
					$stime = number_format_i18n( $ltime, 4 );
				}

				printf(
					'<td class="qm-num">%s</td>',
					esc_html( $stime )
				);
				echo '</tr>';
			}

			echo '</tbody>';
			echo '<tfoot>';

			$total_stime = number_format_i18n( $data->ltime, 4 );
			$count = count( $data->http );

			echo '<tr>';
			printf(
				'<td colspan="7">%s</td>',
				sprintf(
					/* translators: %s: Number of HTTP API requests */
					esc_html( _nx( 'Total: %s', 'Total: %s', $count, 'HTTP API calls', 'query-monitor' ) ),
					'<span class="qm-items-number">' . esc_html( number_format_i18n( $count ) ) . '</span>'
				)
			);
			echo '<td class="qm-num qm-items-time">' . esc_html( $total_stime ) . '</td>';
			echo '</tr>';
			echo '</tfoot>';

			$this->after_tabular_output();
		} else {
			$this->before_non_tabular_output();

			$notice = __( 'No HTTP API calls.', 'query-monitor' );
			echo $this->build_notice( $notice ); // WPCS: XSS ok.

			$this->after_non_tabular_output();
		}
	}

	/**
	 * @param array<int, string> $class
	 * @return array<int, string>
	 */
	public function admin_class( array $class ) {
		/** @var QM_Data_HTTP $data */
		$data = $this->collector->get_data();

		if ( isset( $data->errors['alert'] ) ) {
			$class[] = 'qm-alert';
		}
		if ( isset( $data->errors['warning'] ) ) {
			$class[] = 'qm-warning';
		}

		return $class;

	}

	/**
	 * @param array<string, mixed[]> $menu
	 * @return array<string, mixed[]>
	 */
	public function admin_menu( array $menu ) {
		/** @var QM_Data_HTTP $data */
		$data = $this->collector->get_data();

		$count = ! empty( $data->http ) ? count( $data->http ) : 0;

		$title = ( empty( $count ) )
			? __( 'HTTP API Calls', 'query-monitor' )
			/* translators: %s: Number of calls to the HTTP API */
			: __( 'HTTP API Calls (%s)', 'query-monitor' );

		$args = array(
			'title' => esc_html( sprintf(
				$title,
				number_format_i18n( $count )
			) ),
		);

		if ( isset( $data->errors['alert'] ) ) {
			$args['meta']['classname'] = 'qm-alert';
		}
		if ( isset( $data->errors['warning'] ) ) {
			$args['meta']['classname'] = 'qm-warning';
		}

		$menu[ $this->collector->id() ] = $this->menu( $args );

		return $menu;

	}

}

/**
 * @param array<string, QM_Output> $output
 * @param QM_Collectors $collectors
 * @return array<string, QM_Output>
 */
function register_qm_output_html_http( array $output, QM_Collectors $collectors ) {
	$collector = QM_Collectors::get( 'http' );
	if ( $collector ) {
		$output['http'] = new QM_Output_Html_HTTP( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_http', 90, 2 );
