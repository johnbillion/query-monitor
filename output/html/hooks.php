<?php
/*
Copyright 2009-2016 John Blackbourn

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

*/

class QM_Output_Html_Hooks extends QM_Output_Html {

	public $id = 'hooks';

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 80 );
	}

	public function output() {

		$data = $this->collector->get_data();

		if ( empty( $data['hooks'] ) ) {
			return;
		}

		if ( is_multisite() and is_network_admin() ) {
			$screen = preg_replace( '|-network$|', '', $data['screen'] );
		} else {
			$screen = $data['screen'];
		}

		echo '<div class="qm" id="' . esc_attr( $this->collector->id() ) . '">';
		echo '<table cellspacing="0">';
		echo '<caption class="screen-reader-text">' . esc_html__( 'Hooks', 'query-monitor' ) . '</caption>';
		echo '<thead>';
		echo '<tr>';
		echo '<th scope="col">';
		echo $this->build_filter( 'name', $data['parts'], __( 'Hook', 'query-monitor' ) ); // WPCS: XSS ok.
		echo '</th>';
		echo '<th  scope="col" colspan="3">';
		echo $this->build_filter( 'component', $data['components'], __( 'Actions', 'query-monitor' ), 'subject' ); // WPCS: XSS ok.
		echo '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		foreach ( $data['hooks'] as $hook ) {

			if ( !empty( $screen ) ) {

				if ( false !== strpos( $hook['name'], $screen . '.php' ) ) {
					$hook_name = str_replace( '-' . $screen . '.php', '-<span class="qm-current">' . $screen . '.php</span>', esc_html( $hook['name'] ) );
				} else {
					$hook_name = str_replace( '-' . $screen, '-<span class="qm-current">' . $screen . '</span>', esc_html( $hook['name'] ) );
				}

			} else {
				$hook_name = esc_html( $hook['name'] );
			}

			$row_attr = array();
			$row_attr['data-qm-name']      = implode( ' ', $hook['parts'] );
			$row_attr['data-qm-component'] = implode( ' ', $hook['components'] );

			$attr = '';

			if ( !empty( $hook['actions'] ) ) {
				$rowspan = count( $hook['actions'] );
			} else {
				$rowspan = 1;
			}

			foreach ( $row_attr as $a => $v ) {
				$attr .= ' ' . $a . '="' . esc_attr( $v ) . '"';
			}

			if ( !empty( $hook['actions'] ) ) {

				$first = true;

				foreach ( $hook['actions'] as $action ) {

					if ( isset( $action['callback']['component'] ) ) {
						$component = $action['callback']['component']->name;
					} else {
						$component = '';
					}

					printf( // WPCS: XSS ok.
						'<tr data-qm-subject="%s" %s>',
						esc_attr( $component ),
						$attr
					);

					if ( $first ) {

						echo '<th scope="row" rowspan="' . absint( $rowspan ) . '" class="qm-nowrap">';
						echo $hook_name; // WPCS: XSS ok.
						if ( 'all' === $hook['name'] ) {
							echo '<br><span class="qm-warn">';
							printf(
								/* translators: %s: Action name */
								esc_html__( 'Warning: The %s action is extremely resource intensive. Try to avoid using it.', 'query-monitor' ),
								'<code>all</code>'
							);
							echo '<span>';
						}
						echo '</th>';

					}

					echo '<td class="qm-num">' . intval( $action['priority'] ) . '</td>';
					echo '<td class="qm-ltr qm-wrap">';

					if ( isset( $action['callback']['file'] ) ) {
						echo self::output_filename( $action['callback']['name'], $action['callback']['file'], $action['callback']['line'] ); // WPCS: XSS ok.
					} else {
						echo esc_html( $action['callback']['name'] );
					}

					if ( isset( $action['callback']['error'] ) ) {
						echo '<br><span class="qm-warn">';
						echo esc_html( sprintf(
							/* translators: %s: Error message text */
							__( 'Error: %s', 'query-monitor' ),
							$action['callback']['error']->get_error_message()
						) );
						echo '<span>';
					}

					echo '</td>';
					echo '<td class="qm-nowrap">';
					echo esc_html( $component );
					echo '</td>';
					echo '</tr>';
					$first = false;
				}

			} else {
				echo "<tr{$attr}>"; // WPCS: XSS ok.
				echo '<th scope="row">';
				echo $hook_name; // WPCS: XSS ok.
				echo '</th>';
				echo '<td colspan="3">&nbsp;</td>';
				echo '</tr>';
			}

		}

		echo '</tbody>';
		echo '</table>';
		echo '</div>';

	}

}

function register_qm_output_html_hooks( array $output, QM_Collectors $collectors ) {
	if ( $collector = QM_Collectors::get( 'hooks' ) ) {
		$output['hooks'] = new QM_Output_Html_Hooks( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_hooks', 80, 2 );
