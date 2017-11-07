<?php
/*
Copyright 2009-2017 John Blackbourn

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

*/

class QM_Output_Html_PHP_Errors extends QM_Output_Html {

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 10 );
		add_filter( 'qm/output/menu_class', array( $this, 'admin_class' ) );
	}

	public function output() {

		$data = $this->collector->get_data();

		if ( empty( $data['errors'] ) ) {
			return;
		}

		echo '<div class="qm" id="' . esc_attr( $this->collector->id() ) . '">';
		echo '<table cellspacing="0">';
		echo '<caption class="screen-reader-text">' . esc_html( 'PHP Errors', 'query-monitor' ) . '</caption>';
		echo '<thead>';
		echo '<tr>';
		echo '<th scope="col" colspan="2">' . esc_html__( 'PHP Error', 'query-monitor' ) . '</th>';
		echo '<th scope="col" class="qm-num">' . esc_html__( 'Count', 'query-monitor' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'Location', 'query-monitor' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'Call Stack', 'query-monitor' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'Component', 'query-monitor' ) . '</th>';
		echo '</tr>';
		echo '</thead>';

		$types = array(
			'warning'               => _x( 'Warning', 'PHP error level', 'query-monitor' ),
			'notice'                => _x( 'Notice', 'PHP error level', 'query-monitor' ),
			'strict'                => _x( 'Strict', 'PHP error level', 'query-monitor' ),
			'deprecated'            => _x( 'Deprecated', 'PHP error level', 'query-monitor' ),
			'warning-suppressed'    => _x( 'Warning (Suppressed)', 'Suppressed PHP error level', 'query-monitor' ),
			'notice-suppressed'     => _x( 'Notice (Suppressed)', 'Suppressed PHP error level', 'query-monitor' ),
			'strict-suppressed'     => _x( 'Strict (Suppressed)', 'Suppressed PHP error level', 'query-monitor' ),
			'deprecated-suppressed' => _x( 'Deprecated (Suppressed)', 'Suppressed PHP error level', 'query-monitor' ),
		);

		foreach ( $types as $type => $title ) {

			if ( isset( $data['errors'][ $type ] ) ) {

				echo '<tbody class="qm-group">';
				echo '<tr class="qm-php-error qm-php-error-' . esc_attr( $type ) . '">';
				echo '<th scope="row" rowspan="' . count( $data['errors'][ $type ] ) . '"><span class="dashicons dashicons-warning"></span>' . esc_html( $title ) . '</th>';
				$first = true;

				foreach ( $data['errors'][ $type ] as $error ) {

					if ( ! $first ) {
						echo '<tr class="qm-php-error qm-php-error-' . esc_attr( $type ) . '">';
					}

					$component = $error->trace->get_component();
					$message   = wp_strip_all_tags( $error->message );

					echo '<td class="qm-ltr">' . esc_html( $message ) . '</td>';
					echo '<td class="qm-num">' . esc_html( number_format_i18n( $error->calls ) ) . '</td>';
					echo '<td class="qm-ltr">';
					echo self::output_filename( $error->filename . ':' . $error->line, $error->file, $error->line ); // WPCS: XSS ok.
					echo '</td>';

					$stack          = array();
					$filtered_trace = $error->trace->get_display_trace();

					// debug_backtrace() (used within QM_Backtrace) doesn't like being used within an error handler so
					// we need to handle its somewhat unreliable stack trace items.
					// https://bugs.php.net/bug.php?id=39070
					// https://bugs.php.net/bug.php?id=64987
					foreach ( $filtered_trace as $i => $item ) {
						if ( isset( $item['file'] ) && isset( $item['line'] ) ) {
							$stack[] = self::output_filename( $item['display'], $item['file'], $item['line'] );
						} elseif ( 0 === $i ) {
							$stack[] = self::output_filename( $item['display'], $error->file, $error->line );
						} else {
							$stack[] = $item['display'] . '<br><span class="qm-info qm-supplemental"><em>' . __( 'Unknown location', 'query-monitor' ) . '</em></span>';
						}
					}

					echo '<td class="qm-row-caller qm-row-stack qm-nowrap qm-ltr"><ol class="qm-numbered"><li>';
					echo implode( '</li><li>', $stack ); // WPCS: XSS ok.
					echo '</li></ol></td>';

					if ( $component ) {
						echo '<td class="qm-nowrap">' . esc_html( $component->name ) . '</td>';
					} else {
						echo '<td><em>' . esc_html__( 'Unknown', 'query-monitor' ) . '</em></td>';
					}

					echo '</tr>';

					$first = false;

				}

				echo '</tbody>';
			}
		}

		echo '</table>';
		echo '</div>';

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

		$types = array(
			/* translators: %s: Number of PHP warnings */
			'warning'    => _x( 'Warnings (%s)', 'PHP error level', 'query-monitor' ),
			/* translators: %s: Number of PHP notices */
			'notice'     => _x( 'Notices (%s)', 'PHP error level', 'query-monitor' ),
			/* translators: %s: Number of strict PHP errors */
			'strict'     => _x( 'Stricts (%s)', 'PHP error level', 'query-monitor' ),
			/* translators: %s: Number of deprecated PHP errors */
			'deprecated' => _x( 'Deprecated (%s)', 'PHP error level', 'query-monitor' ),
		);

		foreach ( $types as $type => $label ) {

			$count = 0;

			if ( isset( $data['errors'][ "{$type}-suppressed" ] ) ) {
				$key   = "{$type}-suppressed";
				$count = count( $data['errors'][ $key ] );
			}
			if ( isset( $data['errors'][ $type ] ) ) {
				$key   = $type;
				$count += count( $data['errors'][ $key ] );
			}

			if ( ! $count ) {
				continue;
			}

			$menu[] = $this->menu( array(
				'id'    => "query-monitor-{$key}s",
				'title' => esc_html( sprintf(
					$label,
					number_format_i18n( $count )
				) ),
			) );
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
