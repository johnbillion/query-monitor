<?php declare(strict_types = 1);
/**
 * Database query output for HTML pages.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QM_Output_Html_DB_Queries extends QM_Output_Html {

	/**
	 * Collector instance.
	 *
	 * @var QM_Collector_DB_Queries Collector.
	 */
	protected $collector;

	/**
	 * @var int
	 */
	public $query_row = 0;

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 20 );
		add_filter( 'qm/output/panel_menus', array( $this, 'panel_menu' ), 20 );
		add_filter( 'qm/output/title', array( $this, 'admin_title' ), 20 );
		add_filter( 'qm/output/menu_class', array( $this, 'admin_class' ) );
	}

	/**
	 * @return string
	 */
	public function name() {
		return __( 'Database Queries', 'query-monitor' );
	}

	/**
	 * @return void
	 */
	public function output() {
		/** @var QM_Data_DB_Queries $data */
		$data = $this->collector->get_data();

		if ( empty( $data->rows ) ) {
			$this->output_empty_queries();
			return;
		}

		if ( ! empty( $data->errors ) ) {
			$this->output_error_queries( $data->errors );
		}

		if ( ! empty( $data->expensive ) ) {
			$this->output_expensive_queries( $data->expensive );
		}

		$this->output_queries( $data );
	}

	/**
	 * @return void
	 */
	protected function output_empty_queries() {
		$this->before_non_tabular_output();

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

	/**
	 * @param array<int, mixed> $errors
	 * @return void
	 */
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

	/**
	 * @param array<int, mixed> $expensive
	 * @return void
	 */
	protected function output_expensive_queries( array $expensive ) {
		$dp = strlen( substr( strrchr( (string) QM_DB_EXPENSIVE, '.' ) ?: '.0', 1 ) );

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

	/**
	 * @param QM_Data_DB_Queries $data
	 * @return void
	 */
	protected function output_queries( QM_Data_DB_Queries $data ) {
		$this->query_row = 0;
		$span = 4;

		if ( $data->has_result ) {
			$span++;
		}
		if ( $data->has_trace ) {
			$span++;
		}

		$this->before_tabular_output();

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
		if ( apply_filters( 'qm/show_extended_query_prompt', true ) && ! $data->has_trace ) {
			echo '<tr>';
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '<th colspan="' . intval( $span ) . '" class="qm-warn">' . QueryMonitor::icon( 'warning' );
			if ( file_exists( WP_CONTENT_DIR . '/db.php' ) ) {
				/* translators: %s: File name */
				$message = __( 'Extended query information such as the component and affected rows is not available. A conflicting %s file is present.', 'query-monitor' );
			} elseif ( defined( 'QM_DB_SYMLINK' ) && ! QM_DB_SYMLINK ) {
				/* translators: 1: File name, 2: Configuration constant name */
				$message = __( 'Extended query information such as the component and affected rows is not available. Query Monitor was prevented from symlinking its %1$s file into place by the %2$s constant.', 'query-monitor' );
			} else {
				/* translators: %s: File name */
				$message = __( 'Extended query information such as the component and affected rows is not available. Query Monitor was unable to symlink its %s file into place.', 'query-monitor' );
			}
			printf(
				esc_html( $message ),
				'<code>db.php</code>',
				'<code>QM_DB_SYMLINK</code>'
			);

			printf(
				' <a href="%s" target="_blank" class="qm-external-link">See this wiki page for more information.</a>',
				'https://github.com/johnbillion/query-monitor/wiki/db.php-Symlink'
			);
			echo '</th>';
			echo '</tr>';
		}

		$types = array_keys( $data->types );
		$prepend = array();
		$callers = array_column( $data->times, 'caller' );

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

		if ( $data->has_main_query ) {
			$prepend['qm-main-query'] = __( 'Main Query', 'query-monitor' );
		}

		$args = array(
			'prepend' => $prepend,
		);
		echo $this->build_filter( 'caller', $callers, __( 'Caller', 'query-monitor' ), $args ); // WPCS: XSS ok.
		echo '</th>';

		if ( $data->has_trace ) {
			$components = array_column( $data->component_times, 'component' );

			usort( $components, 'strcasecmp' );

			echo '<th scope="col" class="qm-filterable-column">';
			echo $this->build_filter( 'component', $components, __( 'Component', 'query-monitor' ) ); // WPCS: XSS ok.
			echo '</th>';
		}

		if ( $data->has_result ) {
			if ( empty( $data->errors ) ) {
				$class = 'qm-num';
			} else {
				$class = '';
			}
			echo '<th scope="col" class="' . esc_attr( $class ) . ' qm-sortable-column" role="columnheader">';
			echo $this->build_sorter( __( 'Rows', 'query-monitor' ) ); // WPCS: XSS ok.
			echo '</th>';
		}

		echo '<th scope="col" class="qm-num qm-sortable-column" role="columnheader">';
		echo $this->build_sorter( __( 'Time', 'query-monitor' ) ); // WPCS: XSS ok.
		echo '</th>';
		echo '</tr>';
		echo '</thead>';

		echo '<tbody>';

		foreach ( $data->rows as $row ) {
			$this->output_query_row( $row, array( 'row', 'sql', 'caller', 'component', 'result', 'time' ) );
		}

		echo '</tbody>';
		echo '<tfoot>';

		$total_stime = number_format_i18n( $data->total_time, 4 );

		echo '<tr>';
		echo '<td colspan="' . intval( $span - 1 ) . '">';
		printf(
			/* translators: %s: Number of database queries */
			esc_html( _nx( 'Total: %s', 'Total: %s', $data->total_qs, 'Query count', 'query-monitor' ) ),
			'<span class="qm-items-number">' . esc_html( number_format_i18n( $data->total_qs ) ) . '</span>'
		);
		echo '</td>';
		echo '<td class="qm-num qm-items-time">' . esc_html( $total_stime ) . '</td>';
		echo '</tr>';
		echo '</tfoot>';

		$this->after_tabular_output();
	}

	/**
	 * @param array<string, mixed> $row
	 * @param array<int, string> $cols
	 * @return void
	 */
	protected function output_query_row( array $row, array $cols ) {

		$cols = array_flip( $cols );

		if ( ! isset( $row['component'] ) ) {
			unset( $cols['component'] );
		}
		if ( ! isset( $row['result'] ) ) {
			unset( $cols['result'], $cols['errno'] );
		}

		$stime = number_format_i18n( $row['ltime'], 4 );
		$sql = $row['sql'];

		if ( 'Unknown' === $row['type'] ) {
			$sql = "<code>{$sql}</code>";
		} else {
			$sql = self::format_sql( $row['sql'] );
		}

		if ( 'SELECT' !== $row['type'] ) {
			$sql = "<span class='qm-nonselectsql'>{$sql}</span>";
		}

		if ( isset( $row['trace'] ) ) {

			$caller = $row['trace']->get_caller();
			$caller_name = self::output_filename( $row['caller'], $caller['calling_file'], $caller['calling_line'] );
			$stack = array();
			$filtered_trace = $row['trace']->get_filtered_trace();
			array_shift( $filtered_trace );

			foreach ( $filtered_trace as $frame ) {
				$stack[] = self::output_filename( $frame['display'], $frame['calling_file'], $frame['calling_line'] );
			}
		} else {

			if ( ! empty( $row['caller'] ) ) {
				$caller_name = '<code>' . esc_html( $row['caller'] ) . '</code>';
			} else {
				$caller_name = '<code>' . esc_html__( 'Unknown', 'query-monitor' ) . '</code>';
			}

			$stack = $row['stack'];
			array_shift( $stack );
			$stack = array_map( function( $frame ) {
				return '<code>' . esc_html( $frame ) . '</code>';
			}, $stack );

		}

		$row_attr = array();

		if ( $row['result'] instanceof WP_Error ) {
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
		if ( isset( $cols['caller'] ) && ! empty( $row['caller_name'] ) ) {
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
			echo '<td class="qm-row-caller qm-ltr qm-has-toggle qm-nowrap">';

			if ( ! empty( $stack ) ) {
				echo self::build_toggler(); // WPCS: XSS ok;
			}

			echo '<ol>';
			echo "<li>{$caller_name}</li>"; // WPCS: XSS ok.

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
			echo '<td class="qm-row-caller qm-row-stack qm-nowrap qm-ltr"><ol>';
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
			if ( $row['result'] instanceof WP_Error ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo "<td class='qm-row-result qm-row-error'>" . QueryMonitor::icon( 'warning' ) . esc_html( $row['result']->get_error_message() ) . "</td>\n";
			} else {
				echo "<td class='qm-row-result qm-num'>" . esc_html( $row['result'] ) . "</td>\n";
			}
		}

		if ( isset( $cols['errno'] ) && ( $row['result'] instanceof WP_Error ) ) {
			echo "<td class='qm-row-result qm-row-error'>" . esc_html( $row['result']->get_error_code() ) . "</td>\n";
		}

		if ( isset( $cols['time'] ) ) {
			$expensive = $this->collector->is_expensive( $row );
			$td_class = ( $expensive ) ? ' qm-warn' : '';

			echo '<td class="qm-num qm-row-time' . esc_attr( $td_class ) . '" data-qm-sort-weight="' . esc_attr( $row['ltime'] ) . '">';

			if ( $expensive ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo QueryMonitor::icon( 'warning' );
			}

			echo esc_html( $stime );
			echo "</td>\n";
		}

		echo '</tr>';

	}

	/**
	 * @param array<int, string> $title
	 * @return array<int, string>
	 */
	public function admin_title( array $title ) {
		/** @var QM_Data_DB_Queries $data */
		$data = $this->collector->get_data();

		if ( isset( $data->rows ) ) {
			$title[] = sprintf(
				/* translators: %s: A time in seconds with a decimal fraction. No space between value and unit symbol. */
				esc_html_x( '%ss', 'Time in seconds', 'query-monitor' ),
				number_format_i18n( $data->total_time, 2 )
			);

			/* translators: %s: Number of database queries. Note the space between value and unit symbol. */
			$text = _n( '%s Q', '%s Q', $data->total_qs, 'query-monitor' );

			// Avoid a potentially blank translation for the plural form.
			// @see https://meta.trac.wordpress.org/ticket/5377
			if ( '' === $text ) {
				$text = '%s Q';
			}

			$title[] = preg_replace( '#\s?([^0-9,\.]+)#', '<small>$1</small>', sprintf(
				esc_html( $text ),
				number_format_i18n( $data->total_qs )
			) );
		} elseif ( isset( $data->total_qs ) ) {
			/* translators: %s: Number of database queries. Note the space between value and unit symbol. */
			$text = _n( '%s Q', '%s Q', $data->total_qs, 'query-monitor' );

			// Avoid a potentially blank translation for the plural form.
			// @see https://meta.trac.wordpress.org/ticket/5377
			if ( '' === $text ) {
				$text = '%s Q';
			}

			$title[] = preg_replace( '#\s?([^0-9,\.]+)#', '<small>$1</small>', sprintf(
				esc_html( $text ),
				number_format_i18n( $data->total_qs )
			) );
		}

		return $title;
	}

	/**
	 * @param array<int, string> $class
	 * @return array<int, string>
	 */
	public function admin_class( array $class ) {

		if ( $this->collector->get_errors() ) {
			$class[] = 'qm-error';
		}
		if ( $this->collector->get_expensive() ) {
			$class[] = 'qm-expensive';
		}
		return $class;

	}

	/**
	 * @param array<string, mixed[]> $menu
	 * @return array<string, mixed[]>
	 */
	public function admin_menu( array $menu ) {
		/** @var QM_Data_DB_Queries $data */
		$data = $this->collector->get_data();
		$errors = $this->collector->get_errors();
		$expensive = $this->collector->get_expensive();

		$id = $this->collector->id();
		$menu[ $id ] = $this->menu( array(
			'title' => esc_html__( 'Database Queries', 'query-monitor' ),
			// 'href' => esc_attr( sprintf( '#%s', $this->collector->id() ) ),
		) );

		if ( $errors ) {
			$id = $this->collector->id() . '-errors';
			$count = count( $errors );
			$menu[ $id ] = $this->menu( array(
				'id' => 'query-monitor-errors',
				'href' => '#qm-query-errors',
				'title' => esc_html( sprintf(
					/* translators: %s: Number of database errors */
					__( 'Database Errors (%s)', 'query-monitor' ),
					number_format_i18n( $count )
				) ),
			) );
		}

		if ( $expensive ) {
			$id = $this->collector->id() . '-expensive';
			$count = count( $expensive );
			$menu[ $id ] = $this->menu( array(
				'id' => 'query-monitor-expensive',
				'href' => '#qm-query-expensive',
				'title' => esc_html( sprintf(
					/* translators: %s: Number of slow database queries */
					__( 'Slow Queries (%s)', 'query-monitor' ),
					number_format_i18n( $count )
				) ),
			) );
		}

		return $menu;

	}

	/**
	 * @param array<string, mixed[]> $menu
	 * @return array<string, mixed[]>
	 */
	public function panel_menu( array $menu ) {
		foreach ( array( 'errors', 'expensive' ) as $sub ) {
			$id = $this->collector->id() . '-' . $sub;
			if ( isset( $menu[ $id ] ) ) {
				$menu['qm-db_queries']['children'][] = $menu[ $id ];
				unset( $menu[ $id ] );
			}
		}

		return $menu;
	}

}

/**
 * @param array<string, QM_Output> $output
 * @param QM_Collectors $collectors
 * @return array<string, QM_Output>
 */
function register_qm_output_html_db_queries( array $output, QM_Collectors $collectors ) {
	$collector = QM_Collectors::get( 'db_queries' );
	if ( $collector ) {
		$output['db_queries'] = new QM_Output_Html_DB_Queries( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_db_queries', 20, 2 );
