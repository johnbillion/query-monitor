<?php
/**
 * Hooks and actions output for HTML pages.
 *
 * @package query-monitor
 */

class QM_Output_Html_Hooks extends QM_Output_Html {

	/**
	 * Collector instance.
	 *
	 * @var QM_Collector_Hooks Collector.
	 */
	protected $collector;

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 80 );
	}

	public function name() {
		return __( 'Hooks & Actions', 'query-monitor' );
	}

	public function output() {

		$data = $this->collector->get_data();

		if ( empty( $data['hooks'] ) ) {
			return;
		}

		$this->before_tabular_output();

		echo '<thead>';
		echo '<tr>';
		echo '<th scope="col" class="qm-filterable-column">';
		echo $this->build_filter( 'name', $data['parts'], __( 'Hook', 'query-monitor' ) ); // WPCS: XSS ok.
		echo '</th>';
		echo '<th scope="col">' . esc_html__( 'Priority', 'query-monitor' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'Action', 'query-monitor' ) . '</th>';
		echo '<th scope="col" class="qm-filterable-column">';
		echo $this->build_filter( 'component', $data['components'], __( 'Component', 'query-monitor' ), array(
			'highlight' => 'subject',
		) ); // WPCS: XSS ok.
		echo '</th>';
		echo '</tr>';
		echo '</thead>';

		echo '<tbody>';
		self::output_hook_table( $data['hooks'] );
		echo '</tbody>';

		$this->after_tabular_output();
	}

	public static function output_hook_table( array $hooks ) {
		$core = __( 'Core', 'query-monitor' );

		foreach ( $hooks as $hook ) {
			$row_attr                      = array();
			$row_attr['data-qm-name']      = implode( ' ', $hook['parts'] );
			$row_attr['data-qm-component'] = implode( ' ', $hook['components'] );

			if ( ! empty( $row_attr['data-qm-component'] ) && $core !== $row_attr['data-qm-component'] ) {
				$row_attr['data-qm-component'] .= ' non-core';
			}

			$attr = '';

			if ( ! empty( $hook['actions'] ) ) {
				$rowspan = count( $hook['actions'] );
			} else {
				$rowspan = 1;
			}

			foreach ( $row_attr as $a => $v ) {
				$attr .= ' ' . $a . '="' . esc_attr( $v ) . '"';
			}

			if ( ! empty( $hook['actions'] ) ) {

				$first = true;

				foreach ( $hook['actions'] as $action ) {
					$component = '';
					$subject   = '';

					if ( isset( $action['callback']['component'] ) ) {
						$component = $action['callback']['component']->name;
						$subject   = $component;
					}

					if ( $core !== $component ) {
						$subject .= ' non-core';
					}

					printf( // WPCS: XSS ok.
						'<tr data-qm-subject="%s" %s>',
						esc_attr( $subject ),
						$attr
					);

					if ( $first ) {

						echo '<th scope="row" rowspan="' . intval( $rowspan ) . '" class="qm-nowrap qm-ltr"><span class="qm-sticky">';
						echo '<code>' . esc_html( $hook['name'] ) . '</code>';
						if ( 'all' === $hook['name'] ) {
							echo '<br><span class="qm-warn"><span class="dashicons dashicons-warning" aria-hidden="true"></span>';
							printf(
								/* translators: %s: Action name */
								esc_html__( 'Warning: The %s action is extremely resource intensive. Try to avoid using it.', 'query-monitor' ),
								'<code>all</code>'
							);
							echo '<span>';
						}
						echo '</span></th>';

					}

					if ( isset( $action['callback']['error'] ) ) {
						$class = ' qm-warn';
					} else {
						$class = '';
					}

					echo '<td class="qm-num' . esc_attr( $class ) . '">';

					echo esc_html( $action['priority'] );

					if ( PHP_INT_MAX === $action['priority'] ) {
						echo ' <span class="qm-info">(PHP_INT_MAX)</span>';
					// phpcs:ignore PHPCompatibility.Constants.NewConstants.php_int_minFound
					} elseif ( defined( 'PHP_INT_MIN' ) && PHP_INT_MIN === $action['priority'] ) {
						echo ' <span class="qm-info">(PHP_INT_MIN)</span>';
					} elseif ( -PHP_INT_MAX === $action['priority'] ) {
						echo ' <span class="qm-info">(-PHP_INT_MAX)</span>';
					}

					echo '</td>';

					if ( isset( $action['callback']['file'] ) ) {
						if ( self::has_clickable_links() ) {
							echo '<td class="qm-nowrap qm-ltr' . esc_attr( $class ) . '">';
							echo self::output_filename( $action['callback']['name'], $action['callback']['file'], $action['callback']['line'] ); // WPCS: XSS ok.
							echo '</td>';
						} else {
							echo '<td class="qm-nowrap qm-ltr qm-has-toggle' . esc_attr( $class ) . '">';
							echo self::build_toggler(); // WPCS: XSS ok;
							echo '<ol>';
							echo '<li>';
							echo self::output_filename( $action['callback']['name'], $action['callback']['file'], $action['callback']['line'] ); // WPCS: XSS ok.
							echo '</li>';
							echo '</ol></td>';
						}
					} else {
						echo '<td class="qm-ltr qm-nowrap' . esc_attr( $class ) . '">';
						echo '<code>' . esc_html( $action['callback']['name'] ) . '</code>';

						if ( isset( $action['callback']['error'] ) ) {
							echo '<br><span class="dashicons dashicons-warning" aria-hidden="true"></span>';
							echo esc_html( sprintf(
								/* translators: %s: Error message text */
								__( 'Error: %s', 'query-monitor' ),
								$action['callback']['error']->get_error_message()
							) );
						}

						echo '</td>';
					}

					echo '<td class="qm-nowrap' . esc_attr( $class ) . '">';
					echo esc_html( $component );
					echo '</td>';
					echo '</tr>';
					$first = false;
				}
			} else {
				echo "<tr{$attr}>"; // WPCS: XSS ok.
				echo '<th scope="row" class="qm-ltr">';
				echo '<code>' . esc_html( $hook['name'] ) . '</code>';
				echo '</th>';
				echo '<td></td>';
				echo '<td></td>';
				echo '<td></td>';
				echo '</tr>';
			}
		}

	}

}

function register_qm_output_html_hooks( array $output, QM_Collectors $collectors ) {
	$collector = QM_Collectors::get( 'hooks' );
	if ( $collector ) {
		$output['hooks'] = new QM_Output_Html_Hooks( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_hooks', 80, 2 );
