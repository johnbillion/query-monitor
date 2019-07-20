<?php
/**
 * Duplicate database query output for HTML pages.
 *
 * @package query-monitor
 */

class QM_Output_Html_Headers extends QM_Output_Html {

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 100 );
		add_filter( 'qm/output/panel_menus', array( $this, 'panel_menu' ), 20 );
	}

	public function admin_menu( array $menu ) {
		$menu[ $this->collector->id() ] = $this->menu( array(
			'title' => esc_html__( 'Request Headers', 'query-monitor' ),
		) );

		$response_id = $this->collector->id() . '-response';

		$menu[ $response_id ] = array(
			'id'    => 'query-monitor-' . $response_id,
			'href'  => '#' . $response_id,
			'title' => esc_html__( 'Response Headers', 'query-monitor' ),
		);

		return $menu;
	}

	public function output() {
		$this->output_request();
		$this->output_response();
	}

	public function output_request() {
		$data = $this->collector->get_data();

		$this->before_tabular_output();

		$this->output_header_table( $data['request']['headers'], __( 'Request Header Name', 'query-monitor' ) );

		$this->after_tabular_output();
	}

	public function output_response() {
		$data = $this->collector->get_data();
		$id   = sprintf( 'qm-%s-response', $this->collector->id );

		$this->before_tabular_output( $id );

		$this->output_header_table( $data['response']['headers'], __( 'Response Header Name', 'query-monitor' ) );

		$this->after_tabular_output();
	}

	protected function output_header_table( array $headers, $title ) {
		echo '<thead>';
		echo '<tr>';
		echo '<th>';
		echo esc_html( $title );
		echo '</th><th>';
		esc_html_e( 'Value', 'query-monitor' );
		echo '</th></tr>';
		echo '<tbody>';

		foreach ( $headers as $name => $value ) {
			echo '<tr>';
			$formatted = str_replace( ' ', '-', ucwords( strtolower( str_replace( array( '-', '_' ), ' ', $name ) ) ) );
			printf( '<th scope="row"><code>%s</code></th>', esc_html( $formatted ) );
			printf( '<td><pre class="qm-pre-wrap"><code>%s</code></pre></td>', esc_html( $value ) );
			echo '</tr>';
		}

		echo '</tbody>';

		echo '<tfoot>';
		echo '<tr>';
		echo '<td colspan="2">';
		esc_html_e( 'Note that header names are not case-sensitive.', 'query-monitor' );
		echo '</td>';
		echo '</tr>';
		echo '</tfoot>';
	}

	public function panel_menu( array $menu ) {
		$ids = array(
			$this->collector->id(),
			$this->collector->id() . '-response',
		);
		foreach ( $ids as $id ) {
			if ( isset( $menu[ $id ] ) ) {
				$menu['qm-request']['children'][] = $menu[ $id ];
				unset( $menu[ $id ] );
			}
		}

		return $menu;
	}
}

function register_qm_output_html_headers( array $output, QM_Collectors $collectors ) {
	$collector = QM_Collectors::get( 'raw_request' );
	if ( $collector ) {
		$output['raw_request'] = new QM_Output_Html_Headers( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_headers', 100, 2 );
