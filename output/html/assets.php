<?php declare(strict_types = 1);
/**
 * Scripts and styles output for HTML pages.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class QM_Output_Html_Assets extends QM_Output_Html {

	/**
	 * Collector instance.
	 *
	 * @var QM_Collector_Assets Collector.
	 */
	protected $collector;

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 70 );
		add_filter( 'qm/output/menu_class', array( $this, 'admin_class' ) );
	}

	/**
	 * @return array<string, string>
	 */
	abstract public function get_type_labels();

	/**
	 * @return void
	 */
	public function output() {

		/** @var QM_Data_Assets */
		$data = $this->collector->get_data();
		$type_label = $this->get_type_labels();

		if ( empty( $data->assets ) ) {
			$this->before_non_tabular_output();
			$notice = esc_html( $type_label['none'] );
			echo $this->build_notice( $notice ); // WPCS: XSS ok.
			$this->after_non_tabular_output();
			return;
		}

		$position_labels = array(
			// @TODO translator comments or context:
			'missing' => __( 'Missing', 'query-monitor' ),
			'broken' => __( 'Missing Dependencies', 'query-monitor' ),
			'header' => __( 'Header', 'query-monitor' ),
			'footer' => __( 'Footer', 'query-monitor' ),
		);

		$type = $this->collector->get_dependency_type();

		$hosts = array(
			__( 'Other', 'query-monitor' ),
		);

		$this->before_tabular_output();

		echo '<thead>';
		echo '<tr>';
		echo '<th scope="col">' . esc_html__( 'Position', 'query-monitor' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'Handle', 'query-monitor' ) . '</th>';
		echo '<th scope="col" class="qm-filterable-column">';
		$args = array(
			'prepend' => array(
				'local' => $data->host,
			),
		);
		echo $this->build_filter( $type . '-host', $hosts, __( 'Host', 'query-monitor' ), $args ); // WPCS: XSS ok.
		echo '</th>';
		echo '<th scope="col">' . esc_html__( 'Source', 'query-monitor' ) . '</th>';
		echo '<th scope="col" class="qm-filterable-column">';
		echo $this->build_filter( $type . '-dependencies', $data->dependencies, __( 'Dependencies', 'query-monitor' ) ); // WPCS: XSS ok.
		echo '</th>';
		echo '<th scope="col" class="qm-filterable-column">';
		echo $this->build_filter( $type . '-dependents', $data->dependents, __( 'Dependents', 'query-monitor' ) ); // WPCS: XSS ok.
		echo '</th>';
		echo '<th scope="col">' . esc_html__( 'Version', 'query-monitor' ) . '</th>';
		echo '</tr>';
		echo '</thead>';

		echo '<tbody>';

		foreach ( $position_labels as $position => $label ) {
			if ( ! empty( $data->assets[ $position ] ) ) {
				foreach ( $data->assets[ $position ] as $handle => $asset ) {
					$this->dependency_row( $handle, $asset, $label );
				}
			}
		}

		echo '</tbody>';

		echo '<tfoot>';

		echo '<tr>';
		printf(
			'<td colspan="7">%1$s</td>',
			sprintf(
				esc_html( $type_label['total'] ),
				'<span class="qm-items-number">' . esc_html( number_format_i18n( $data->counts['total'] ) ) . '</span>'
			)
		);
		echo '</tr>';
		echo '</tfoot>';

		$this->after_tabular_output();
	}

	/**
	 * @param string $handle
	 * @param array<string, mixed> $asset
	 * @param string $label
	 * @return void
	 */
	protected function dependency_row( $handle, array $asset, $label ) {
		/** @var QM_Data_Assets */
		$data = $this->collector->get_data();

		$highlight_deps = array_map( array( $this, '_prefix_type' ), $asset['dependencies'] );
		$highlight_dependents = array_map( array( $this, '_prefix_type' ), $asset['dependents'] );

		$dependencies_list = implode( ' ', $asset['dependencies'] );
		$dependents_list = implode( ' ', $asset['dependents'] );

		$dependency_output = array();

		foreach ( $asset['dependencies'] as $dep ) {
			if ( isset( $data->missing_dependencies[ $dep ] ) ) {
				$warning = QueryMonitor::icon( 'warning' );

				$dependency_output[] = sprintf(
					'<span style="white-space:nowrap">%1$s%2$s</span>',
					$warning,
					sprintf(
						/* translators: %s: Name of missing script or style dependency */
						__( '%s (missing)', 'query-monitor' ),
						esc_html( $dep )
					)
				);
			} else {
				$dependency_output[] = $dep;
			}
		}

		$qm_host = ( $asset['local'] ) ? 'local' : __( 'Other', 'query-monitor' );

		$class = '';

		if ( $asset['warning'] ) {
			$class = 'qm-warn';
		}

		$type = $this->collector->get_dependency_type();

		echo '<tr data-qm-subject="' . esc_attr( $type . '-' . $handle ) . '" data-qm-' . esc_attr( $type ) . '-host="' . esc_attr( $qm_host ) . '" data-qm-' . esc_attr( $type ) . '-dependents="' . esc_attr( $dependents_list ) . '" data-qm-' . esc_attr( $type ) . '-dependencies="' . esc_attr( $dependencies_list ) . '" class="' . esc_attr( $class ) . '">';
		echo '<td class="qm-nowrap">';

		$warning = QueryMonitor::icon( 'warning' );

		if ( $asset['warning'] ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $warning;
		}

		echo esc_html( $label );
		echo '</td>';

		$host = $asset['host'];
		$parts = explode( '.', $host );

		foreach ( $parts as $k => $part ) {
			if ( strlen( $part ) > 16 ) {
				$parts[ $k ] = substr( $parts[ $k ], 0, 6 ) . '&hellip;' . substr( $parts[ $k ], -6 );
			}
		}

		$host = implode( '.', $parts );

		if ( ! empty( $asset['port'] ) && ! empty( $asset['host'] ) ) {
			$host = "{$host}:{$asset['port']}";
		}

		echo '<td class="qm-nowrap qm-ltr">' . esc_html( $handle ) . '</td>';
		echo '<td class="qm-nowrap qm-ltr">' . esc_html( $host ) . '</td>';
		echo '<td class="qm-ltr">';
		if ( $asset['source'] instanceof WP_Error ) {
			$error_data = $asset['source']->get_error_data();
			if ( $error_data && isset( $error_data['src'] ) ) {
				printf(
					'<span class="qm-warn">%1$s%2$s:</span><br>',
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					$warning,
					esc_html( $asset['source']->get_error_message() )
				);
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo self::build_link( $error_data['src'], esc_html( $error_data['src'] ) );
			} else {
				printf(
					'<span class="qm-warn">%1$s%2$s</span>',
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					$warning,
					esc_html( $asset['source']->get_error_message() )
				);
			}
		} elseif ( ! empty( $asset['source'] ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo self::build_link( $asset['source'], esc_html( $asset['display'] ) );
		}
		echo '</td>';
		echo '<td class="qm-ltr qm-highlighter" data-qm-highlight="' . esc_attr( implode( ' ', $highlight_deps ) ) . '">';

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo implode( ', ', $dependency_output );

		echo '</td>';
		echo '<td class="qm-ltr qm-highlighter" data-qm-highlight="' . esc_attr( implode( ' ', $highlight_dependents ) ) . '">' . implode( ', ', array_map( 'esc_html', $asset['dependents'] ) ) . '</td>';
		echo '<td class="qm-ltr">' . esc_html( $asset['ver'] ) . '</td>';

		echo '</tr>';
	}

	/**
	 * @param string $val
	 * @return string
	 */
	public function _prefix_type( $val ) {
		return $this->collector->get_dependency_type() . '-' . $val;
	}

	/**
	 * @param array<int, string> $class
	 * @return array<int, string>
	 */
	public function admin_class( array $class ) {
		/** @var QM_Data_Assets */
		$data = $this->collector->get_data();

		if ( ! empty( $data->broken ) || ! empty( $data->missing ) ) {
			$class[] = 'qm-error';
		}

		return $class;

	}

	/**
	 * @param array<string, mixed[]> $menu
	 * @return array<string, mixed[]>
	 */
	public function admin_menu( array $menu ) {
		/** @var QM_Data_Assets */
		$data = $this->collector->get_data();

		if ( empty( $data->assets ) ) {
			return $menu;
		}

		$type_label = $this->get_type_labels();
		$label = sprintf(
			$type_label['count'],
			number_format_i18n( $data->counts['total'] )
		);

		$args = array(
			'title' => esc_html( $label ),
			'id' => esc_attr( "query-monitor-{$this->collector->id}" ),
			'href' => esc_attr( '#' . $this->collector->id() ),
		);

		if ( ! empty( $data->broken ) || ! empty( $data->missing ) ) {
			$args['meta']['classname'] = 'qm-error';
		}

		$id = $this->collector->id();
		$menu[ $id ] = $this->menu( $args );

		return $menu;

	}

}
