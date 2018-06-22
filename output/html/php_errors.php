<?php
/**
 * PHP error output for HTML pages.
 *
 * @package query-monitor
 */

class QM_Output_Html_PHP_Errors extends QM_Output_Html {

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 10 );
		add_filter( 'qm/output/panel_menus', array( $this, 'panel_menu' ), 10 );
		add_filter( 'qm/output/menu_class', array( $this, 'admin_class' ) );
	}

	public function output() {

		$data = $this->collector->get_data();

		if ( empty( $data['errors'] ) && empty( $data['silenced'] ) && empty( $data['suppressed'] ) ) {
			return;
		}

		$levels = array(
			'Warning',
			'Notice',
			'Strict',
			'Deprecated',
		);
		$components = $data['components'];

		usort( $components, 'strcasecmp' );

		$this->before_tabular_output();

		echo '<thead>';
		echo '<tr>';
		echo '<th scope="col" class="qm-filterable-column">';
		echo $this->build_filter( 'type', $levels, __( 'Level', 'query-monitor' ) ); // WPCS: XSS ok.
		echo '</th>';
		echo '<th scope="col" class="qm-col-message">' . esc_html__( 'Message', 'query-monitor' ) . '</th>';
		echo '<th scope="col" class="qm-num">' . esc_html__( 'Count', 'query-monitor' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'Location', 'query-monitor' ) . '</th>';
		echo '<th scope="col" class="qm-filterable-column">';
		echo $this->build_filter( 'component', $components, __( 'Component', 'query-monitor' ) ); // WPCS: XSS ok.
		echo '</th>';
		echo '</tr>';
		echo '</thead>';

		echo '<tbody>';

		foreach ( $this->collector->types as $error_group => $error_types ) {
			foreach ( $error_types as $type => $title ) {

				if ( ! isset( $data[ $error_group ][ $type ] ) ) {
					continue;
				}

				foreach ( $data[ $error_group ][ $type ] as $error ) {

					$component = $error['trace']->get_component();
					$row_attr  = array();
					$row_attr['data-qm-component'] = $component->name;
					$row_attr['data-qm-type']      = ucfirst( $type );

					if ( 'core' !== $component->context ) {
						$row_attr['data-qm-component'] .= ' non-core';
					}

					$attr = '';

					foreach ( $row_attr as $a => $v ) {
						$attr .= ' ' . $a . '="' . esc_attr( $v ) . '"';
					}

					$is_warning = ( 'errors' === $error_group && 'warning' === $type );

					if ( $is_warning ) {
						$class = 'qm-warn';
					} else {
						$class = '';
					}

					echo '<tr ' . $attr . 'class="' . esc_attr( $class ) . '">'; // WPCS: XSS ok.
					echo '<td scope="row" class="qm-nowrap">';

					if ( $is_warning ) {
						echo '<span class="dashicons dashicons-warning" aria-hidden="true"></span>';
					} else {
						echo '<span class="dashicons" aria-hidden="true"></span>';
					}

					echo esc_html( $title );
					echo '</td>';

					echo '<td class="qm-ltr">' . esc_html( $error['message'] ) . '</td>';
					echo '<td class="qm-num">' . esc_html( number_format_i18n( $error['calls'] ) ) . '</td>';

					$stack          = array();
					$filtered_trace = $error['trace']->get_display_trace();

					// debug_backtrace() (used within QM_Backtrace) doesn't like being used within an error handler so
					// we need to handle its somewhat unreliable stack trace items.
					// https://bugs.php.net/bug.php?id=39070
					// https://bugs.php.net/bug.php?id=64987
					foreach ( $filtered_trace as $i => $item ) {
						if ( isset( $item['file'] ) && isset( $item['line'] ) ) {
							$stack[] = self::output_filename( $item['display'], $item['file'], $item['line'] );
						} elseif ( 0 === $i ) {
							$stack[] = self::output_filename( $item['display'], $error['file'], $error['line'] );
						} else {
							$stack[] = $item['display'] . '<br><span class="qm-info qm-supplemental"><em>' . __( 'Unknown location', 'query-monitor' ) . '</em></span>';
						}
					}

					echo '<td class="qm-row-caller qm-row-stack qm-nowrap qm-ltr qm-has-toggle"><ol class="qm-toggler qm-numbered">';

					echo self::build_toggler(); // WPCS: XSS ok;
					if ( ! empty( $stack ) ) {
						echo '<div class="qm-toggled"><li>' . implode( '</li><li>', $stack ) . '</li></div>'; // WPCS: XSS ok.
					}

					echo '<li>';
					echo self::output_filename( $error['filename'] . ':' . $error['line'], $error['file'], $error['line'], true ); // WPCS: XSS ok.
					echo '</li>';

					echo '</ol></td>';

					if ( $component ) {
						echo '<td class="qm-nowrap">' . esc_html( $component->name ) . '</td>';
					} else {
						echo '<td><em>' . esc_html__( 'Unknown', 'query-monitor' ) . '</em></td>';
					}

					echo '</tr>';
				}
			}
		}

		echo '</tbody>';

		$this->after_tabular_output();
	}

	public function admin_class( array $class ) {

		$data = $this->collector->get_data();

		if ( ! empty( $data['errors'] ) ) {
			foreach ( $data['errors'] as $type => $errors ) {
				$class[] = 'qm-' . $type;
			}
		}

		return $class;

	}

	public function admin_menu( array $menu ) {

		$data = $this->collector->get_data();
		$menu_label = array();

		$types = array(
			/* translators: %s: Number of deprecated PHP errors */
			'deprecated' => _nx_noop( '%s Deprecated', '%s Deprecated', 'PHP error level', 'query-monitor' ),
			/* translators: %s: Number of strict PHP errors */
			'strict'     => _nx_noop( '%s Strict', '%s Stricts', 'PHP error level', 'query-monitor' ),
			/* translators: %s: Number of PHP notices */
			'notice'     => _nx_noop( '%s Notice', '%s Notices', 'PHP error level', 'query-monitor' ),
			/* translators: %s: Number of PHP warnings */
			'warning'    => _nx_noop( '%s Warning', '%s Warnings', 'PHP error level', 'query-monitor' ),
		);

		$key = 'quiet';
		$generic = false;

		foreach ( $types as $type => $label ) {

			$count = 0;
			$has_errors = false;

			if ( isset( $data['suppressed'][ $type ] ) ) {
				$has_errors = true;
				$generic = true;
			}
			if ( isset( $data['silenced'][ $type ] ) ) {
				$has_errors = true;
				$generic = true;
			}
			if ( isset( $data['errors'][ $type ] ) ) {
				$has_errors = true;
				$key   = $type;
				$count += array_sum( wp_list_pluck( $data['errors'][ $type ], 'calls' ) );
			}

			if ( ! $has_errors ) {
				continue;
			}

			if ( $count ) {
				$label = sprintf(
					translate_nooped_plural(
						$label,
						$count,
						'query-monitor'
					),
					number_format_i18n( $count )
				);
				$menu_label[] = $label;
			}
		}

		if ( empty( $menu_label ) && ! $generic ) {
			return $menu;
		}

		/* translators: %s: Number of PHP errors */
		$title = __( 'PHP Errors (%s)', 'query-monitor' );

		/* translators: used between list items, there is a space after the comma */
		$sep = __( ', ', 'query-monitor' );

		if ( count( $menu_label ) ) {
			$title = sprintf(
				$title,
				implode( $sep, array_reverse( $menu_label ) )
			);
		} else {
			$title = __( 'PHP Errors', 'query-monitor' );
		}

		$menu['php_errors'] = $this->menu( array(
			'id'    => "query-monitor-{$key}s",
			'title' => $title,
		) );
		return $menu;

	}

	public function panel_menu( array $menu ) {
		if ( isset( $menu['php_errors'] ) ) {
			$menu['php_errors']['title'] = __( 'PHP Errors', 'query-monitor' );
		}

		return $menu;
	}

}

function register_qm_output_html_php_errors( array $output, QM_Collectors $collectors ) {
	if ( $collector = QM_Collectors::get( 'php_errors' ) ) {
		$output['php_errors'] = new QM_Output_Html_PHP_Errors( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_php_errors', 110, 2 );
