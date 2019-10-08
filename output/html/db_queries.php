<?php
/**
 * Database query output for HTML pages.
 *
 * @package query-monitor
 */

class QM_Output_Html_DB_Queries extends QM_Output_Html {

	/**
	 * Collector instance.
	 *
	 * @var QM_Collector_DB_Queries Collector.
	 */
	protected $collector;

	public $query_row = 0;

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 20 );
		add_filter( 'qm/output/panel_menus', array( $this, 'panel_menu' ), 20 );
		add_filter( 'qm/output/title', array( $this, 'admin_title' ), 20 );
		add_filter( 'qm/output/menu_class', array( $this, 'admin_class' ) );
	}

	public function output() {

		$data = $this->collector->get_data();

		if ( empty( $data['dbs'] ) ) {
			$this->output_empty_queries();
			return;
		}

		if ( ! empty( $data['errors'] ) ) {
			$this->output_error_queries( $data['errors'] );
		}

		if ( ! empty( $data['expensive'] ) ) {
			$this->output_expensive_queries( $data['expensive'] );
		}

		foreach ( $data['dbs'] as $name => $db ) {
			$this->output_queries( $name, $db, $data );
		}

	}

	protected function output_empty_queries() {
		$id = sprintf(
			'%s-wpdb',
			$this->collector->id()
		);
		$this->before_non_tabular_output( $id );

		if ( ! SAVEQUERIES ) {
			$notice = sprintf(
				/* translators: 1: Name of PHP constant, 2: Value of PHP constant */
				esc_html__( 'No database queries were logged because the %1$s constant is set to %2$s.', 'query-monitor' ),
				'<code>SAVEQUERIES</code>',
				'<code>false</code>'
			);
		} else {
			$notice = __( 'No database queries were logged.', 'query-monitor' );
		}

		echo $this->build_notice( $notice ); // WPCS: XSS ok.
		$this->after_non_tabular_output();
	}

	protected function output_error_queries( array $errors ) {
		$this->before_tabular_output( 'qm-query-errors', __( 'Database Errors', 'query-monitor' ) );

		echo '<thead>';
		echo '<tr>';
		echo '<th scope="col">' . esc_html__( 'Query', 'query-monitor' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'Caller', 'query-monitor' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'Component', 'query-monitor' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'Error Message', 'query-monitor' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'Error Code', 'query-monitor' ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		foreach ( $errors as $row ) {
			$this->output_query_row( $row, array( 'sql', 'caller', 'component', 'errno', 'result' ) );
		}

		echo '</tbody>';

		$this->after_tabular_output();
	}

	protected function output_expensive_queries( array $expensive ) {
		$dp = strlen( substr( strrchr( (string) QM_DB_EXPENSIVE, '.' ), 1 ) );

		$panel_name = sprintf(
			/* translators: %s: Database query time in seconds */
			esc_html__( 'Slow Database Queries (above %ss)', 'query-monitor' ),
			'<span class="qm-warn">' . esc_html( number_format_i18n( QM_DB_EXPENSIVE, $dp ) ) . '</span>'
		);
		$this->before_tabular_output( 'qm-query-expensive', $panel_name );

		echo '<thead>';
		echo '<tr>';
		echo '<th scope="col">' . esc_html__( 'Query', 'query-monitor' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'Caller', 'query-monitor' ) . '</th>';

		if ( isset( $expensive[0]['component'] ) ) {
			echo '<th scope="col">' . esc_html__( 'Component', 'query-monitor' ) . '</th>';
		}

		if ( isset( $expensive[0]['result'] ) ) {
			echo '<th scope="col" class="qm-num">' . esc_html__( 'Rows', 'query-monitor' ) . '</th>';
		}

		echo '<th scope="col" class="qm-num">' . esc_html__( 'Time', 'query-monitor' ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		foreach ( $expensive as $row ) {
			$this->output_query_row( $row, array( 'sql', 'caller', 'component', 'result', 'time' ) );
		}

		echo '</tbody>';

		$this->after_tabular_output();
	}

	protected function output_queries( $name, stdClass $db, array $data ) {
		$this->query_row = 0;
		$span = 4;

		if ( $db->has_result ) {
			$span++;
		}
		if ( $db->has_trace ) {
			$span++;
		}

		$panel_id = sprintf(
			'%s-%s',
			$this->collector->id(),
			sanitize_title_with_dashes( $name )
		);
		$panel_name = sprintf(
			/* translators: %s: Name of database controller */
			__( '%s Queries', 'query-monitor' ),
			$name
		);

		if ( ! empty( $db->rows ) ) {
			$this->before_tabular_output( $panel_id, $panel_name );

			echo '<thead>';

			/**
			 * Filter whether to show the QM extended query information prompt.
			 *
			 * By default QM shows a prompt to install the QM db.php drop-in,
			 * this filter allows a dev to choose not to show the prompt.
			 *
			 * @since 2.9.0
			 *
			 * @param bool $show_prompt Whether to show the prompt.
			 */
			if ( apply_filters( 'qm/show_extended_query_prompt', true ) && ! $db->has_trace && ( '$wpdb' === $name ) ) {
				echo '<tr>';
				echo '<th colspan="' . intval( $span ) . '" class="qm-warn"><span class="dashicons dashicons-warning" aria-hidden="true"></span>';
				if ( file_exists( WP_CONTENT_DIR . '/db.php' ) ) {
					/* translators: 1: Symlink file name, 2: URL to wiki page */
					$message = __( 'Extended query information such as the component and affected rows is not available. A conflicting %1$s file is present. <a href="%2$s" target="_blank" class="qm-external-link">See this wiki page for more information.</a>', 'query-monitor' );
				} else {
					/* translators: 1: Symlink file name, 2: URL to wiki page */
					$message = __( 'Extended query information such as the component and affected rows is not available. Query Monitor was unable to symlink its %1$s file into place. <a href="%2$s" target="_blank" class="qm-external-link">See this wiki page for more information.</a>', 'query-monitor' );
				}
				echo wp_kses( sprintf(
					$message,
					'<code>db.php</code>',
					'https://github.com/johnbillion/query-monitor/wiki/db.php-Symlink'
				), array(
					'a' => array(
						'href'   => array(),
						'target' => array(),
						'class'  => array(),
					),
				) );
				echo '</th>';
				echo '</tr>';
			}

			$types      = array_keys( $db->types );
			$prepend    = array();
			$callers    = wp_list_pluck( $data['times'], 'caller' );

			sort( $types );
			usort( $callers, 'strcasecmp' );

			if ( count( $types ) > 1 ) {
				$prepend['non-select'] = __( 'Non-SELECT', 'query-monitor' );
			}

			$args = array(
				'prepend' => $prepend,
			);

			echo '<tr>';
			echo '<th scope="col" class="qm-sorted-asc qm-sortable-column" role="columnheader" aria-sort="ascending">';
			echo $this->build_sorter( '#' ); // WPCS: XSS ok;
			echo '</th>';
			echo '<th scope="col" class="qm-filterable-column">';
			echo $this->build_filter( 'type', $types, __( 'Query', 'query-monitor' ), $args ); // WPCS: XSS ok;
			echo '</th>';
			echo '<th scope="col" class="qm-filterable-column">';

			$prepend = array();

			if ( $db->has_main_query ) {
				$prepend['qm-main-query'] = __( 'Main Query', 'query-monitor' );
			}

			$args = array(
				'prepend' => $prepend,
			);
			echo $this->build_filter( 'caller', $callers, __( 'Caller', 'query-monitor' ), $args ); // WPCS: XSS ok.
			echo '</th>';

			if ( $db->has_trace ) {
				$components = wp_list_pluck( $data['component_times'], 'component' );

				usort( $components, 'strcasecmp' );

				echo '<th scope="col" class="qm-filterable-column">';
				echo $this->build_filter( 'component', $components, __( 'Component', 'query-monitor' ) ); // WPCS: XSS ok.
				echo '</th>';
			}

			if ( $db->has_result ) {
				if ( empty( $data['errors'] ) ) {
					$class = 'qm-num';
				} else {
					$class = '';
				}
				echo '<th scope="col" class="' . esc_attr( $class ) . ' qm-sortable-column" role="columnheader" aria-sort="none">';
				echo $this->build_sorter( __( 'Rows', 'query-monitor' ) ); // WPCS: XSS ok.
				echo '</th>';
			}

			echo '<th scope="col" class="qm-num qm-sortable-column" role="columnheader" aria-sort="none">';
			echo $this->build_sorter( __( 'Time', 'query-monitor' ) ); // WPCS: XSS ok.
			echo '</th>';
			echo '</tr>';
			echo '</thead>';

			echo '<tbody>';

			foreach ( $db->rows as $row ) {
				$this->output_query_row( $row, array( 'row', 'sql', 'caller', 'component', 'result', 'time' ) );
			}

			echo '</tbody>';
			echo '<tfoot>';

			$total_stime = number_format_i18n( $db->total_time, 4 );

			echo '<tr>';
			echo '<td colspan="' . intval( $span - 1 ) . '">';
			printf(
				/* translators: %s: Number of database queries */
				esc_html( _nx( 'Total: %s', 'Total: %s', $db->total_qs, 'Query count', 'query-monitor' ) ),
				'<span class="qm-items-number">' . esc_html( number_format_i18n( $db->total_qs ) ) . '</span>'
			);
			echo '</td>';
			echo '<td class="qm-num qm-items-time">' . esc_html( $total_stime ) . '</td>';
			echo '</tr>';
			echo '</tfoot>';

			$this->after_tabular_output();
		} else {
			$this->before_non_tabular_output( $panel_id, $panel_name );

			$notice = __( 'No queries! Nice work.', 'query-monitor' );
			echo $this->build_notice( $notice ); // WPCS: XSS ok.

			$this->after_non_tabular_output();
		}
	}

	protected function output_query_row( array $row, array $cols ) {

		$cols = array_flip( $cols );

		if ( ! isset( $row['component'] ) ) {
			unset( $cols['component'] );
		}
		if ( ! isset( $row['result'] ) ) {
			unset( $cols['result'], $cols['errno'] );
		}

		$stime = number_format_i18n( $row['ltime'], 4 );

		$sql = self::format_sql( $row['sql'] );

		if ( 'SELECT' !== $row['type'] ) {
			$sql = "<span class='qm-nonselectsql'>{$sql}</span>";
		}

		if ( isset( $row['trace'] ) ) {

			$caller         = $row['trace']->get_caller();
			$caller_name    = self::output_filename( $row['caller'], $caller['calling_file'], $caller['calling_line'] );
			$stack          = array();
			$filtered_trace = $row['trace']->get_display_trace();
			array_shift( $filtered_trace );

			foreach ( $filtered_trace as $item ) {
				$stack[] = self::output_filename( $item['display'], $item['calling_file'], $item['calling_line'] );
			}
		} else {

			$caller_name = '<code>' . esc_html( $row['caller'] ) . '</code>';
			$stack       = explode( ', ', $row['stack'] );
			$stack       = array_reverse( $stack );
			array_shift( $stack );
			$stack       = array_map( function( $item ) {
				return '<code>' . esc_html( $item ) . '</code>';
			}, $stack );

		}

		$row_attr = array();

		if ( is_wp_error( $row['result'] ) ) {
			$row_attr['class'] = 'qm-warn';
		}
		if ( isset( $cols['sql'] ) ) {
			$row_attr['data-qm-type'] = $row['type'];
			if ( 'SELECT' !== $row['type'] ) {
				$row_attr['data-qm-type'] .= ' non-select';
			}
		}
		if ( isset( $cols['component'] ) && $row['component'] ) {
			$row_attr['data-qm-component'] = $row['component']->name;

			if ( 'core' !== $row['component']->context ) {
				$row_attr['data-qm-component'] .= ' non-core';
			}
		}
		if ( isset( $cols['caller'] ) ) {
			$row_attr['data-qm-caller'] = $row['caller_name'];

			if ( $row['is_main_query'] ) {
				$row_attr['data-qm-caller'] .= ' qm-main-query';
			}
		}
		if ( isset( $cols['time'] ) ) {
			$row_attr['data-qm-time'] = $row['ltime'];
		}

		$attr = '';

		foreach ( $row_attr as $a => $v ) {
			$attr .= ' ' . $a . '="' . esc_attr( $v ) . '"';
		}

		echo "<tr{$attr}>"; // WPCS: XSS ok.

		if ( isset( $cols['row'] ) ) {
			echo '<th scope="row" class="qm-row-num qm-num">' . intval( ++$this->query_row ) . '</th>';
		}

		if ( isset( $cols['sql'] ) ) {
			printf( // WPCS: XSS ok.
				'<td class="qm-row-sql qm-ltr qm-wrap">%s</td>',
				$sql
			);
		}

		if ( isset( $cols['caller'] ) ) {
			echo '<td class="qm-row-caller qm-ltr qm-has-toggle qm-nowrap"><ol class="qm-toggler qm-numbered">';
			echo "<li>{$caller_name}</li>"; // WPCS: XSS ok.

			echo self::build_toggler(); // WPCS: XSS ok;

			if ( ! empty( $stack ) ) {
				echo '<div class="qm-toggled"><li>' . implode( '</li><li>', $stack ) . '</li></div>'; // WPCS: XSS ok.
			}

			echo '</ol>';
			if ( $row['is_main_query'] ) {
				printf(
					'<p>%s</p>',
					esc_html__( 'Main Query', 'query-monitor' )
				);
			}
			echo '</td>';
		}

		if ( isset( $cols['stack'] ) ) {
			echo '<td class="qm-row-caller qm-row-stack qm-nowrap qm-ltr"><ol class="qm-numbered">';
			if ( ! empty( $stack ) ) {
				echo '<li>' . implode( '</li><li>', $stack ) . '</li>'; // WPCS: XSS ok.
			}
			echo "<li>{$caller_name}</li>"; // WPCS: XSS ok.
			echo '</ol></td>';
		}

		if ( isset( $cols['component'] ) ) {
			if ( $row['component'] ) {
			echo "<td class='qm-row-component qm-nowrap'>" . esc_html( $row['component']->name ) . "</td>\n";
			} else {
				echo "<td class='qm-row-component qm-nowrap'>" . esc_html__( 'Unknown', 'query-monitor' ) . "</td>\n";
			}
		}

		if ( isset( $cols['result'] ) ) {
			if ( is_wp_error( $row['result'] ) ) {
				echo "<td class='qm-row-result qm-row-error'><span class='dashicons dashicons-warning' aria-hidden='true'></span>" . esc_html( $row['result']->get_error_message() ) . "</td>\n";
			} else {
				echo "<td class='qm-row-result qm-num'>" . esc_html( $row['result'] ) . "</td>\n";
			}
		}

		if ( isset( $cols['errno'] ) && is_wp_error( $row['result'] ) ) {
			echo "<td class='qm-row-result qm-row-error'>" . esc_html( $row['result']->get_error_code() ) . "</td>\n";
		}

		if ( isset( $cols['time'] ) ) {
			$expensive = $this->collector->is_expensive( $row );
			$td_class  = ( $expensive ) ? ' qm-warn' : '';

			echo '<td class="qm-num qm-row-time' . esc_attr( $td_class ) . '" data-qm-sort-weight="' . esc_attr( $row['ltime'] ) . '">';

			if ( $expensive ) {
				echo '<span class="dashicons dashicons-warning" aria-hidden="true"></span>';
			}

			echo esc_html( $stime );
			echo "</td>\n";
		}

		echo '</tr>';

	}

	public function admin_title( array $title ) {

		$data = $this->collector->get_data();

		if ( isset( $data['dbs'] ) ) {
			foreach ( $data['dbs'] as $key => $db ) {
				$title[] = sprintf(
					/* translators: %s: Database query time in seconds */
					'%s' . esc_html( _nx( '%s S', '%s S', $db->total_time, 'Query time', 'query-monitor' ) ),
					( count( $data['dbs'] ) > 1 ? '&bull;&nbsp;&nbsp;&nbsp;' : '' ),
					number_format_i18n( $db->total_time, 4 )
				);
				$title[] = sprintf(
					/* translators: %s: Number of database queries */
					esc_html( _nx( '%s Q', '%s Q', $db->total_qs, 'Query count', 'query-monitor' ) ),
					number_format_i18n( $db->total_qs )
				);
			}
		} elseif ( isset( $data['total_qs'] ) ) {
			$title[] = sprintf(
				/* translators: %s: Number of database queries */
				esc_html( _nx( '%s Q', '%s Q', $data['total_qs'], 'Query count', 'query-monitor' ) ),
				number_format_i18n( $data['total_qs'] )
			);
		}

		foreach ( $title as &$t ) {
			$t = preg_replace( '#\s?([^0-9,\.]+)#', '<small>$1</small>', $t );
		}

		return $title;
	}

	public function admin_class( array $class ) {

		if ( $this->collector->get_errors() ) {
			$class[] = 'qm-error';
		}
		if ( $this->collector->get_expensive() ) {
			$class[] = 'qm-expensive';
		}
		return $class;

	}

	public function admin_menu( array $menu ) {

		$data      = $this->collector->get_data();
		$errors    = $this->collector->get_errors();
		$expensive = $this->collector->get_expensive();

		if ( isset( $data['dbs'] ) && count( $data['dbs'] ) > 1 ) {
			foreach ( $data['dbs'] as $name => $db ) {
				$name_attr   = sanitize_title_with_dashes( $name );
				$id          = $this->collector->id() . '-' . $name_attr;
				$menu[ $id ] = $this->menu( array(
					'id'    => esc_attr( sprintf( 'query-monitor-%s-db-%s', $this->collector->id(), $name_attr ) ),
					'title' => esc_html( sprintf(
						/* translators: %s: Name of database controller */
						__( 'Queries: %s', 'query-monitor' ),
						$name
					) ),
					'href'  => esc_attr( sprintf( '#%s-%s', $this->collector->id(), $name_attr ) ),
				) );
			}
		} else {
			$id          = $this->collector->id() . '-$wpdb';
			$menu[ $id ] = $this->menu( array(
				'title' => esc_html__( 'Queries', 'query-monitor' ),
				'href'  => esc_attr( sprintf( '#%s-wpdb', $this->collector->id() ) ),
			) );
		}

		if ( $errors ) {
			$id          = $this->collector->id() . '-errors';
			$count       = count( $errors );
			$menu[ $id ] = $this->menu( array(
				'id'    => 'query-monitor-errors',
				'href'  => '#qm-query-errors',
				'title' => esc_html( sprintf(
					/* translators: %s: Number of database errors */
					_n( 'Database Errors (%s)', 'Database Errors (%s)', $count, 'query-monitor' ),
					number_format_i18n( $count )
				) ),
			) );
		}

		if ( $expensive ) {
			$id          = $this->collector->id() . '-expensive';
			$count       = count( $expensive );
			$menu[ $id ] = $this->menu( array(
				'id'    => 'query-monitor-expensive',
				'href'  => '#qm-query-expensive',
				'title' => esc_html( sprintf(
					/* translators: %s: Number of slow database queries */
					_n( 'Slow Queries (%s)', 'Slow Queries (%s)', $count, 'query-monitor' ),
					number_format_i18n( $count )
				) ),
			) );
		}

		return $menu;

	}

	public function panel_menu( array $menu ) {
		foreach ( array( 'errors', 'expensive' ) as $sub ) {
			$id = $this->collector->id() . '-' . $sub;
			if ( isset( $menu[ $id ] ) ) {
				$menu[ $id ]['title'] = $menu[ $id ]['title'];

				$menu['qm-db_queries-$wpdb']['children'][] = $menu[ $id ];
				unset( $menu[ $id ] );
			}
		}

		return $menu;
	}

}

function register_qm_output_html_db_queries( array $output, QM_Collectors $collectors ) {
	$collector = QM_Collectors::get( 'db_queries' );
	if ( $collector ) {
		$output['db_queries'] = new QM_Output_Html_DB_Queries( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_db_queries', 20, 2 );
