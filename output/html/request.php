<?php declare(strict_types = 1);
/**
 * Request data output for HTML pages.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QM_Output_Html_Request extends QM_Output_Html {

	/**
	 * Collector instance.
	 *
	 * @var QM_Collector_Request Collector.
	 */
	protected $collector;

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 50 );
	}

	/**
	 * @return string
	 */
	public function name() {
		return __( 'Request', 'query-monitor' );
	}

	/**
	 * @return void
	 */
	public function output() {
		/** @var QM_Data_Request $data */
		$data = $this->collector->get_data();

		/** @var QM_Collector_DB_Queries|null $db_queries */
		$db_queries = QM_Collectors::get( 'db_queries' );

		/** @var QM_Collector_Raw_Request|null $raw_request */
		$raw_request = QM_Collectors::get( 'raw_request' );

		$this->before_non_tabular_output();

		foreach ( array(
			'request' => __( 'Request', 'query-monitor' ),
			'matched_rule' => __( 'Matched Rule', 'query-monitor' ),
			'matched_query' => __( 'Matched Query', 'query-monitor' ),
			'query_string' => __( 'Query String', 'query-monitor' ),
		) as $item => $name ) {
			if ( is_admin() && ! isset( $data->request[ $item ] ) ) {
				continue;
			}

			if ( ! empty( $data->request[ $item ] ) ) {
				if ( in_array( $item, array( 'request', 'matched_query', 'query_string' ), true ) ) {
					$value = self::format_url( $data->request[ $item ] );
				} else {
					$value = esc_html( $data->request[ $item ] );
				}
			} else {
				$value = '<em>' . esc_html__( 'none', 'query-monitor' ) . '</em>';
			}

			echo '<section>';
			echo '<h3>' . esc_html( $name ) . '</h3>';
			echo '<p class="qm-ltr"><code>' . $value . '</code></p>'; // WPCS: XSS ok.
			echo '</section>';
		}

		echo '</div>';

		echo '<div class="qm-boxed">';

		if ( ! empty( $data->matching_rewrites ) ) {
			echo '<section>';
			echo '<h3>' . esc_html__( 'All Matching Rewrite Rules', 'query-monitor' ) . '</h3>';
			echo '<table>';

			foreach ( $data->matching_rewrites as $rule => $query ) {
				$query = str_replace( 'index.php?', '', $query );

				echo '<tr>';
				echo '<td class="qm-ltr"><code>' . esc_html( $rule ) . '</code></td>';
				echo '<td class="qm-ltr"><code>';
				echo self::format_url( $query ); // WPCS: XSS ok.
				echo '</code></td>';
				echo '</tr>';
			}

			echo '</table>';
			echo '</section>';
		}

		echo '<section>';
		echo '<h3>';
		esc_html_e( 'Query Vars', 'query-monitor' );
		echo '</h3>';

		if ( $db_queries ) {
			$db_queries_data = $db_queries->get_data();
			if ( ! empty( $db_queries_data->wpdb->has_main_query ) ) {
				echo '<p>';
				echo self::build_filter_trigger( 'db_queries', 'caller', 'qm-main-query', esc_html__( 'View Main Query', 'query-monitor' ) ); // WPCS: XSS ok;
				echo '</p>';
			}
		}

		if ( ! empty( $data->qvars ) ) {

			echo '<table>';

			foreach ( $data->qvars as $var => $value ) {

				echo '<tr>';

				if ( isset( $data->plugin_qvars[ $var ] ) ) {
					echo '<th scope="row" class="qm-ltr"><span class="qm-current">' . esc_html( $var ) . '</span></td>';
				} else {
					echo '<th scope="row" class="qm-ltr">' . esc_html( $var ) . '</td>';
				}

				if ( is_array( $value ) || is_object( $value ) ) {
					echo '<td class="qm-ltr"><pre>';
					echo esc_html( print_r( $value, true ) );
					echo '</pre></td>';
				} else {
					echo '<td class="qm-ltr qm-wrap">' . esc_html( $value ) . '</td>';
				}

				echo '</tr>';

			}
			echo '</table>';

		} else {

			echo '<p><em>' . esc_html__( 'none', 'query-monitor' ) . '</em></p>';

		}

		echo '</section>';

		echo '<section>';
		echo '<h3>' . esc_html__( 'Response', 'query-monitor' ) . '</h3>';
		echo '<h4>' . esc_html__( 'Queried Object', 'query-monitor' ) . '</h4>';

		if ( ! empty( $data->queried_object ) ) {
			$class = get_class( $data->queried_object['data'] );
			$class = $class ?: __( 'Unknown', 'query-monitor' );
			printf(
				'<p>%1$s (%2$s)</p>',
				esc_html( $data->queried_object['title'] ),
				esc_html( $class )
			);
		} else {
			echo '<p><em>' . esc_html__( 'none', 'query-monitor' ) . '</em></p>';
		}

		echo '<h4>' . esc_html__( 'Current User', 'query-monitor' ) . '</h4>';

		if ( ! empty( $data->user['data'] ) ) {
			printf( // WPCS: XSS ok.
				'<p>%s</p>',
				esc_html( $data->user['title'] )
			);
		} else {
			echo '<p><em>' . esc_html__( 'none', 'query-monitor' ) . '</em></p>';
		}

		if ( ! empty( $data->multisite ) ) {
			echo '<h4>' . esc_html__( 'Multisite', 'query-monitor' ) . '</h4>';

			foreach ( $data->multisite as $var => $value ) {
				printf( // WPCS: XSS ok.
					'<p>%s</p>',
					esc_html( $value['title'] )
				);
			}
		}

		echo '</section>';

		if ( ! empty( $raw_request ) ) {
			/** @var QM_Data_Raw_Request $raw_data */
			$raw_data = $raw_request->get_data();
			echo '<section>';
			echo '<h3>' . esc_html__( 'Request Data', 'query-monitor' ) . '</h3>';
			echo '<table>';

			foreach ( array(
				'ip' => __( 'Remote IP', 'query-monitor' ),
				'method' => __( 'HTTP method', 'query-monitor' ),
				'url' => __( 'Requested URL', 'query-monitor' ),
			) as $item => $name ) {
				echo '<tr>';
				echo '<th scope="row">' . esc_html( $name ) . '</td>';
				echo '<td class="qm-ltr qm-wrap">' . esc_html( $raw_data->request[ $item ] ) . '</td>';
				echo '</tr>';
			}

			echo '</table>';

			echo '</section>';
		}

		$this->after_non_tabular_output();
	}

	/**
	 * @param array<string, mixed[]> $menu
	 * @return array<string, mixed[]>
	 */
	public function admin_menu( array $menu ) {
		/** @var QM_Data_Request $data */
		$data = $this->collector->get_data();
		$count = isset( $data->plugin_qvars ) ? count( $data->plugin_qvars ) : 0;

		$title = ( empty( $count ) )
			? __( 'Request', 'query-monitor' )
			/* translators: %s: Number of additional query variables */
			: __( 'Request (+%s)', 'query-monitor' );

		$menu[ $this->collector->id() ] = $this->menu( array(
			'title' => esc_html( sprintf(
				$title,
				number_format_i18n( $count )
			) ),
		) );
		return $menu;

	}

}

/**
 * @param array<string, QM_Output> $output
 * @param QM_Collectors $collectors
 * @return array<string, QM_Output>
 */
function register_qm_output_html_request( array $output, QM_Collectors $collectors ) {
	$collector = QM_Collectors::get( 'request' );
	if ( $collector ) {
		$output['request'] = new QM_Output_Html_Request( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_request', 60, 2 );
