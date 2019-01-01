<?php
/**
 * Scripts and styles output for HTML pages.
 *
 * @package query-monitor
 */

class QM_Output_Html_Assets extends QM_Output_Html {

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/menus',      array( $this, 'admin_menu' ), 70 );
		add_filter( 'qm/output/menu_class', array( $this, 'admin_class' ) );
	}

	public function output() {

		$data = $this->collector->get_data();

		if ( empty( $data['raw'] ) ) {
			return;
		}

		$position_labels = array(
			// @TODO translator comments or context:
			'missing' => __( 'Missing', 'query-monitor' ),
			'broken'  => __( 'Missing Dependencies', 'query-monitor' ),
			'header'  => __( 'Header', 'query-monitor' ),
			'footer'  => __( 'Footer', 'query-monitor' ),
		);

		$type_labels = array(
			'scripts' => array(
				/* translators: %s: Total number of enqueued scripts */
				'total'  => _x( 'Total: %s', 'Enqueued scripts', 'query-monitor' ),
				'plural' => __( 'Scripts', 'query-monitor' ),
			),
			'styles'  => array(
				/* translators: %s: Total number of enqueued styles */
				'total'  => _x( 'Total: %s', 'Enqueued styles', 'query-monitor' ),
				'plural' => __( 'Styles', 'query-monitor' ),
			),
		);

		foreach ( $type_labels as $type => $type_label ) {
			$hosts = array(
				__( 'Other', 'query-monitor' ),
			);

			$panel_id = sprintf(
				'%s-%s',
				$this->collector->id(),
				$type
			);

			$this->before_tabular_output( $panel_id, $type_label['plural'] );

			echo '<thead>';
			echo '<tr>';
			echo '<th scope="col">' . esc_html__( 'Position', 'query-monitor' ) . '</th>';
			echo '<th scope="col">' . esc_html__( 'Handle', 'query-monitor' ) . '</th>';
			echo '<th scope="col" class="qm-filterable-column">';
			$args = array(
				'prepend' => array(
					'local' => $data['host'],
				),
			);
			echo $this->build_filter( $type . '-host', $hosts, __( 'Host', 'query-monitor' ), $args ); // WPCS: XSS ok.
			echo '</th>';
			echo '<th scope="col">' . esc_html__( 'Source', 'query-monitor' ) . '</th>';
			echo '<th scope="col" class="qm-filterable-column">';
			echo $this->build_filter( $type . '-dependencies', $data['dependencies'][ $type ], __( 'Dependencies', 'query-monitor' ) ); // WPCS: XSS ok.
			echo '</th>';
			echo '<th scope="col" class="qm-filterable-column">';
			echo $this->build_filter( $type . '-dependents', $data['dependents'][ $type ], __( 'Dependents', 'query-monitor' ) ); // WPCS: XSS ok.
			echo '</th>';
			echo '<th scope="col">' . esc_html__( 'Version', 'query-monitor' ) . '</th>';
			echo '</tr>';
			echo '</thead>';

			echo '<tbody>';

			$total = 0;

			foreach ( $position_labels as $position => $label ) {
				if ( ! empty( $data[ $position ][ $type ] ) ) {
					$this->dependency_rows( $data[ $position ][ $type ], $data['raw'][ $type ], $label, $type );
					$total += count( $data[ $position ][ $type ] );
				}
			}

			echo '</tbody>';

			echo '<tfoot>';

			echo '<tr>';
			printf(
				'<td colspan="7">%1$s</td>',
				sprintf(
					esc_html( $type_label['total'] ),
					'<span class="qm-items-number">' . esc_html( number_format_i18n( $total ) ) . '</span>'
				)
			);
			echo '</tr>';
			echo '</tfoot>';

			$this->after_tabular_output();
		}

	}

	protected function dependency_rows( array $handles, WP_Dependencies $dependencies, $label, $type ) {
		foreach ( $handles as $handle ) {
			$this->dependency_row( $handle, $dependencies->query( $handle ), $dependencies, $label, $type );
		}
	}

	protected function dependency_row( $handle, _WP_Dependency $dependency, WP_Dependencies $dependencies, $label, $type ) {

		if ( empty( $dependency->ver ) ) {
			$ver = '';
		} else {
			$ver = $dependency->ver;
		}

		list( $src, $host, $source, $local ) = $this->collector->get_dependency_data( $dependency, $dependencies, $type );

		$dependents = $this->collector->get_dependents( $dependency, $dependencies );
		$deps       = $dependency->deps;
		sort( $deps );

		foreach ( $deps as & $dep ) {
			if ( ! $dependencies->query( $dep ) ) {
				/* translators: %s: Script or style dependency name */
				$dep = sprintf( __( '%s (missing)', 'query-monitor' ), $dep );
			}
		}

		$this->type = $type;

		$highlight_deps       = array_map( array( $this, '_prefix_type' ), $deps );
		$highlight_dependents = array_map( array( $this, '_prefix_type' ), $dependents );

		$dependencies_list = implode( ' ', $dependency->deps );
		$dependents_list   = implode( ' ', $this->collector->get_dependents( $dependency, $dependencies ) );

		$qm_host = ( $local ) ? 'local' : __( 'Other', 'query-monitor' );

		$warn  = false;
		$class = '';

		if ( ! in_array( $handle, $dependencies->done, true ) ) {
			$warn  = true;
			$class = 'qm-warn';
		}

		echo '<tr data-qm-subject="' . esc_attr( $type . '-' . $handle ) . '" data-qm-' . esc_attr( $type ) . '-host="' . esc_attr( $qm_host ) . '" data-qm-' . esc_attr( $type ) . '-dependents="' . esc_attr( $dependents_list ) . '" data-qm-' . esc_attr( $type ) . '-dependencies="' . esc_attr( $dependencies_list ) . '" class="' . esc_attr( $class ) . '">';
		echo '<td class="qm-nowrap">';

		if ( $warn ) {
			echo '<span class="dashicons dashicons-warning" aria-hidden="true"></span>';
		}

		echo esc_html( $label );
		echo '</td>';

		echo '<td class="qm-nowrap qm-ltr">' . esc_html( $dependency->handle ) . '</td>';
		echo '<td class="qm-nowrap qm-ltr">' . esc_html( $host ) . '</td>';
		echo '<td class="qm-ltr">';
		if ( is_wp_error( $source ) ) {
			printf(
				 '<span class="qm-warn">%s</span>',
				esc_html( $src )
			);
		} elseif ( ! empty( $src ) ) {
			printf(
				'<a href="%s" class="qm-link">%s</a>',
				esc_attr( $src ),
				esc_html( ltrim( str_replace( home_url(), '', remove_query_arg( 'ver', $src ) ), '/' ) )
			);
		}
		echo '</td>';
		echo '<td class="qm-ltr qm-highlighter" data-qm-highlight="' . esc_attr( implode( ' ', $highlight_deps ) ) . '">' . implode( ', ', array_map( 'esc_html', $deps ) ) . '</td>';
		echo '<td class="qm-ltr qm-highlighter" data-qm-highlight="' . esc_attr( implode( ' ', $highlight_dependents ) ) . '">' . implode( ', ', array_map( 'esc_html', $dependents ) ) . '</td>';
		echo '<td class="qm-ltr">' . esc_html( $ver ) . '</td>';

		echo '</tr>';
	}

	public function _prefix_type( $val ) {
		return $this->type . '-' . $val;
	}

	public function admin_class( array $class ) {

		$data = $this->collector->get_data();

		if ( ! empty( $data['broken'] ) || ! empty( $data['missing'] ) ) {
			$class[] = 'qm-error';
		}

		return $class;

	}

	public function admin_menu( array $menu ) {

		$data   = $this->collector->get_data();
		$labels = array(
			'scripts' => __( 'Scripts', 'query-monitor' ),
			'styles'  => __( 'Styles', 'query-monitor' ),
		);

		if ( empty( $data['raw'] ) ) {
			return $menu;
		}

		foreach ( $labels as $type => $label ) {
			$args = array(
				'title' => esc_html( $label ),
				'id'    => esc_attr( "query-monitor-{$this->collector->id}-{$type}" ),
				'href'  => esc_attr( '#' . $this->collector->id() . '-' . $type ),
			);

			if ( ! empty( $data['broken'][ $type ] ) || ! empty( $data['missing'][ $type ] ) ) {
				$args['meta']['classname'] = 'qm-error';
			}

			$menu[] = $this->menu( $args );
		}

		return $menu;

	}

}

function register_qm_output_html_assets( array $output, QM_Collectors $collectors ) {
	$collector = $collectors::get( 'assets' );
	if ( $collector ) {
		$output['assets'] = new QM_Output_Html_Assets( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_assets', 80, 2 );
