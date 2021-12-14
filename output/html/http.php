<?php
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

		$data = $this->collector->get_data();

		if ( ! empty( $data['http'] ) ) {
			$statuses = array_keys( $data['types'] );
			$components = wp_list_pluck( $data['component_times'], 'component' );

			usort( $statuses, 'strcasecmp' );
			usort( $components, 'strcasecmp' );

			$status_output = array();

			foreach ( $statuses as $key => $status ) {
				if ( -1 === $status ) {
					$status_output[-1] = __( 'Error', 'query-monitor' );
				} elseif ( -2 === $status ) {
					/* translators: A non-blocking HTTP API request */
					$status_output[-2] = __( 'Non-blocking', 'query-monitor' );
				} else {
					$status_output[] = $status;
				}
			}

			$this->before_tabular_output();

			echo '<thead>';
			echo '<tr>';
			echo '<th scope="col">' . esc_html__( 'Method', 'query-monitor' ) . '</th>';
			echo '<th scope="col">' . esc_html__( 'URL', 'query-monitor' ) . '</th>';
			echo '<th scope="col" class="qm-filterable-column">';
			echo $this->build_filter( 'type', $status_output, __( 'Status', 'query-monitor' ) ); // WPCS: XSS ok.
			echo '</th>';
			echo '<th scope="col">' . esc_html__( 'Caller', 'query-monitor' ) . '</th>';
			echo '<th scope="col" class="qm-filterable-column">';
			echo $this->build_filter( 'component', $components, __( 'Component', 'query-monitor' ) ); // WPCS: XSS ok.
			echo '</th>';
			echo '<th scope="col" class="qm-num">' . esc_html__( 'Timeout', 'query-monitor' ) . '</th>';
			echo '<th scope="col" class="qm-num">' . esc_html__( 'Time', 'query-monitor' ) . '</th>';
			echo '</tr>';
			echo '</thead>';

			echo '<tbody>';
			$i = 0;

			foreach ( $data['http'] as $key => $row ) {
				$ltime = $row['ltime'];
				$i++;
				$is_error = false;
				$row_attr = array();
				$css = '';

				if ( is_wp_error( $row['response'] ) ) {
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

				$url = preg_replace( '|^http:|', '<span class="qm-warn">http</span>:', $url );

				if ( 'https' === parse_url( $row['url'], PHP_URL_SCHEME ) ) {
					if ( empty( $row['args']['sslverify'] ) && ! $row['local'] ) {
						$info .= '<span class="qm-warn"><span class="dashicons dashicons-warning" aria-hidden="true"></span>' . esc_html( sprintf(
							/* translators: An HTTP API request has disabled certificate verification. 1: Relevant argument name */
							__( 'Certificate verification disabled (%s)', 'query-monitor' ),
							'sslverify=false'
						) ) . '</span><br>';
						$url = preg_replace( '|^https:|', '<span class="qm-warn">https</span>:', $url );
					} elseif ( ! $is_error && $row['args']['blocking'] ) {
						$url = preg_replace( '|^https:|', '<span class="qm-true">https</span>:', $url );
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
				$row_attr['data-qm-time'] = $row['ltime'];

				if ( 'core' !== $component->context ) {
					$row_attr['data-qm-component'] .= ' non-core';
				}

				$attr = '';
				foreach ( $row_attr as $a => $v ) {
					$attr .= ' ' . $a . '="' . esc_attr( $v ) . '"';
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

				if ( ! empty( $row['redirected_to'] ) ) {
					$url .= sprintf(
						'<br><span class="qm-warn"><span class="dashicons dashicons-warning" aria-hidden="true"></span>%1$s</span><br>%2$s',
						/* translators: An HTTP API request redirected to another URL */
						__( 'Redirected to:', 'query-monitor' ),
						self::format_url( $row['redirected_to'] )
					);
				}

				printf( // WPCS: XSS ok.
					'<td class="qm-url qm-ltr qm-wrap">%s%s</td>',
					$info,
					$url
				);

				$show_toggle = ( ! empty( $row['transport'] ) && ! empty( $row['info'] ) );

				echo '<td class="qm-has-toggle qm-col-status">';
				if ( $is_error ) {
					echo '<span class="dashicons dashicons-warning" aria-hidden="true"></span>';
				}
				echo esc_html( $response );

				if ( $show_toggle ) {
					echo self::build_toggler(); // WPCS: XSS ok;
					echo '<ul class="qm-toggled">';
				}

				if ( ! empty( $row['transport'] ) ) {
					$transport = sprintf(
						/* translators: %s HTTP API transport name */
						__( 'HTTP API Transport: %s', 'query-monitor' ),
						$row['transport']
					);
					printf(
						'<li><span class="qm-info qm-supplemental">%s</span></li>',
						esc_html( $transport )
					);
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

					$size_fields = array(
						'size_download' => __( 'Response Size', 'query-monitor' ),
					);
					foreach ( $size_fields as $key => $value ) {
						if ( ! isset( $row['info'][ $key ] ) ) {
							continue;
						}
						printf(
							'<li><span class="qm-info qm-supplemental">%1$s: %2$s</span></li>',
							esc_html( $value ),
							esc_html( size_format( $row['info'][ $key ] ) )
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
					'<td class="qm-num">%s</td>',
					esc_html( $row['args']['timeout'] )
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

			$total_stime = number_format_i18n( $data['ltime'], 4 );
			$count = count( $data['http'] );

			echo '<tr>';
			printf(
				'<td colspan="6">%s</td>',
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

		$data = $this->collector->get_data();

		if ( isset( $data['errors']['alert'] ) ) {
			$class[] = 'qm-alert';
		}
		if ( isset( $data['errors']['warning'] ) ) {
			$class[] = 'qm-warning';
		}

		return $class;

	}

	/**
	 * @param array<string, mixed[]> $menu
	 * @return array<string, mixed[]>
	 */
	public function admin_menu( array $menu ) {

		$data = $this->collector->get_data();

		$count = isset( $data['http'] ) ? count( $data['http'] ) : 0;

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

		if ( isset( $data['errors']['alert'] ) ) {
			$args['meta']['classname'] = 'qm-alert';
		}
		if ( isset( $data['errors']['warning'] ) ) {
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
