<?php
/**
 * Abstract output class for HTML pages.
 *
 * @package query-monitor
 */

abstract class QM_Output_Html extends QM_Output {

	protected static $file_link_format = null;

	protected $current_id   = null;
	protected $current_name = null;

	public function name() {
		_deprecated_function(
			esc_html( get_class( $this->collector ) . '::name()' ),
			'3.5',
			esc_html( get_class( $this ) . '::name()' )
		);

		return $this->collector->name();
	}

	public function admin_menu( array $menu ) {

		$menu[ $this->collector->id() ] = $this->menu( array(
			'title' => esc_html( $this->name() ),
		) );
		return $menu;

	}

	public function get_output() {
		ob_start();
		// compat until I convert all the existing outputters to use `get_output()`
		$this->output();
		$out = ob_get_clean();
		return $out;
	}

	protected function before_tabular_output( $id = null, $name = null ) {
		if ( null === $id ) {
			$id = $this->collector->id();
		}
		if ( null === $name ) {
			$name = $this->name();
		}

		$this->current_id   = $id;
		$this->current_name = $name;

		printf(
			'<div class="qm" id="%1$s" role="tabpanel" aria-labelledby="%1$s-caption" tabindex="-1">',
			esc_attr( $id )
		);

		echo '<table class="qm-sortable">';

		printf(
			'<caption class="qm-screen-reader-text"><h2 id="%1$s-caption">%2$s</h2></caption>',
			esc_attr( $id ),
			esc_html( $name )
		);
	}

	protected function after_tabular_output() {
		echo '</table>';
		echo '</div>';

		$this->output_concerns();
	}

	protected function before_non_tabular_output( $id = null, $name = null ) {
		if ( null === $id ) {
			$id = $this->collector->id();
		}
		if ( null === $name ) {
			$name = $this->name();
		}

		$this->current_id   = $id;
		$this->current_name = $name;

		printf(
			'<div class="qm qm-non-tabular" id="%1$s" role="tabpanel" aria-labelledby="%1$s-caption" tabindex="-1">',
			esc_attr( $id )
		);

		echo '<div class="qm-boxed">';

		printf(
			'<h2 class="qm-screen-reader-text" id="%1$s-caption">%2$s</h2>',
			esc_attr( $id ),
			esc_html( $name )
		);
	}

	protected function after_non_tabular_output() {
		echo '</div>';
		echo '</div>';

		$this->output_concerns();
	}

	protected function output_concerns() {
		$concerns = array(
			'concerned_actions' => array(
				__( 'Related Hooks with Actions Attached', 'query-monitor' ),
				__( 'Action', 'query-monitor' ),
			),
			'concerned_filters' => array(
				__( 'Related Hooks with Filters Attached', 'query-monitor' ),
				__( 'Filter', 'query-monitor' ),
			),
		);

		if ( empty( $this->collector->concerned_actions ) && empty( $this->collector->concerned_filters ) ) {
			return;
		}

		printf(
			'<div class="qm qm-concerns" id="%1$s" role="tabpanel" aria-labelledby="%1$s-caption" tabindex="-1">',
			esc_attr( $this->current_id . '-concerned_hooks' )
		);

		echo '<table>';

		printf(
			'<caption><h2 id="%1$s-caption">%2$s</h2></caption>',
			esc_attr( $this->current_id . '-concerned_hooks' ),
			sprintf(
				/* translators: %s: Panel name */
				esc_html__( '%s: Related Hooks with Filters or Actions Attached', 'query-monitor' ),
				esc_html( $this->name() )
			)
		);

		echo '<thead>';
		echo '<tr>';
		echo '<th scope="col">' . esc_html__( 'Hook', 'query-monitor' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'Priority', 'query-monitor' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'Callback', 'query-monitor' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'Component', 'query-monitor' ) . '</th>';
		echo '</tr>';
		echo '</thead>';

		echo '<tbody>';

		foreach ( $concerns as $key => $labels ) {
			if ( empty( $this->collector->$key ) ) {
				continue;
			}

			QM_Output_Html_Hooks::output_hook_table( $this->collector->$key );
		}

		echo '</tbody>';
		echo '</table>';

		echo '</div>';
	}

	protected function before_debug_bar_output( $id = null, $name = null ) {
		if ( null === $id ) {
			$id = $this->collector->id();
		}
		if ( null === $name ) {
			$name = $this->name();
		}

		printf(
			'<div class="qm qm-debug-bar" id="%1$s" role="tabpanel" aria-labelledby="%1$s-caption" tabindex="-1">',
			esc_attr( $id )
		);

		printf(
			'<h2 class="qm-screen-reader-text" id="%1$s-caption">%2$s</h2>',
			esc_attr( $id ),
			esc_html( $name )
		);
	}

	protected function after_debug_bar_output() {
		echo '</div>';
	}

	protected function build_notice( $notice ) {
		$return = '<section>';
		$return .= '<div class="qm-notice">';
		$return .= '<p>';
		$return .= $notice;
		$return .= '</p>';
		$return .= '</div>';
		$return .= '</section>';

		return $return;
	}

	public static function output_inner( $vars ) {

		echo '<table>';

		foreach ( $vars as $key => $value ) {
			echo '<tr>';
			echo '<td>' . esc_html( $key ) . '</td>';
			if ( is_array( $value ) ) {
				echo '<td>';
				self::output_inner( $value );
				echo '</td>';
			} elseif ( is_object( $value ) ) {
				echo '<td>';
				self::output_inner( get_object_vars( $value ) );
				echo '</td>';
			} elseif ( is_bool( $value ) ) {
				if ( $value ) {
					echo '<td class="qm-true">true</td>';
				} else {
					echo '<td class="qm-false">false</td>';
				}
			} else {
				echo '<td>';
				echo nl2br( esc_html( $value ) );
				echo '</td>';
			}
			echo '</td>';
			echo '</tr>';
		}
		echo '</table>';

	}

	/**
	 * Returns the table filter controls. Safe for output.
	 *
	 * @param  string   $name   The name for the `data-` attributes that get filtered by this control.
	 * @param  string[] $values Option values for this control.
	 * @param  string   $label  Label text for the filter control.
	 * @param  array    $args {
	 *     @type string $highlight The name for the `data-` attributes that get highlighted by this control.
	 *     @type array  $prepend   Associative array of options to prepend to the list of values.
	 *     @type array  $append    Associative array of options to append to the list of values.
	 * }
	 * @return string Markup for the table filter controls.
	 */
	protected function build_filter( $name, array $values, $label, $args = array() ) {

		if ( empty( $values ) ) {
			return esc_html( $label ); // Return label text, without being marked up as a label element.
		}

		if ( ! is_array( $args ) ) {
			$args = array(
				'highlight' => $args,
			);
		}

		$args = array_merge( array(
			'highlight' => '',
			'prepend'   => array(),
			'append'    => array(),
		), $args );

		$core_val = __( 'Core', 'query-monitor' );
		$core_key = array_search( $core_val, $values, true );

		if ( 'component' === $name && count( $values ) > 1 && false !== $core_key ) {
			$args['append'][ $core_val ] = $core_val;
			$args['append']['non-core']  = __( 'Non-Core', 'query-monitor' );
			unset( $values[ $core_key ] );
		}

		$filter_id = 'qm-filter-' . $this->collector->id . '-' . $name;

		$out = '<div class="qm-filter-container">';
		$out .= '<label for="' . esc_attr( $filter_id ) . '">' . esc_html( $label ) . '</label>';
		$out .= '<select id="' . esc_attr( $filter_id ) . '" class="qm-filter" data-filter="' . esc_attr( $name ) . '" data-highlight="' . esc_attr( $args['highlight'] ) . '">';
		$out .= '<option value="">' . esc_html_x( 'All', '"All" option for filters', 'query-monitor' ) . '</option>';

		if ( ! empty( $args['prepend'] ) ) {
			foreach ( $args['prepend'] as $value => $label ) {
				$out .= '<option value="' . esc_attr( $value ) . '">' . esc_html( $label ) . '</option>';
			}
		}

		foreach ( $values as $key => $value ) {
			if ( is_int( $key ) && $key >= 0 ) {
				$out .= '<option value="' . esc_attr( $value ) . '">' . esc_html( $value ) . '</option>';
			} else {
				$out .= '<option value="' . esc_attr( $key ) . '">' . esc_html( $value ) . '</option>';
			}
		}

		if ( ! empty( $args['append'] ) ) {
			foreach ( $args['append'] as $value => $label ) {
				$out .= '<option value="' . esc_attr( $value ) . '">' . esc_html( $label ) . '</option>';
			}
		}

		$out .= '</select>';
		$out .= '</div>';

		return $out;

	}

	/**
	 * Returns the column sorter controls. Safe for output.
	 *
	 * @param string $heading Heading text for the column. Optional.
	 * @return string Markup for the column sorter controls.
	 */
	protected function build_sorter( $heading = '' ) {
		$out = '';
		$out .= '<label class="qm-th">';
		$out .= '<span class="qm-sort-heading">';

		if ( '#' === $heading ) {
			$out .= '<span class="qm-screen-reader-text">' . esc_html__( 'Sequence', 'query-monitor' ) . '</span>';
		} elseif ( $heading ) {
			$out .= esc_html( $heading );
		}

		$out .= '</span>';
		$out .= '<button class="qm-sort-controls" aria-label="' . esc_attr__( 'Sort data by this column', 'query-monitor' ) . '">';
		$out .= '<span class="qm-sort-arrow" aria-hidden="true"></span>';
		$out .= '</button>';
		$out .= '</label>';
		return $out;
	}

	/**
	 * Returns a toggle control. Safe for output.
	 *
	 * @return string Markup for the column sorter controls.
	 */
	protected static function build_toggler() {
		$out = '<button class="qm-toggle" data-on="+" data-off="-" aria-expanded="false" aria-label="' . esc_attr__( 'Toggle more information', 'query-monitor' ) . '"><span aria-hidden="true">+</span></button>';
		return $out;
	}

	protected function menu( array $args ) {

		return array_merge( array(
			'id'   => esc_attr( "query-monitor-{$this->collector->id}" ),
			'href' => esc_attr( '#' . $this->collector->id() ),
		), $args );

	}

	/**
	 * Returns the given SQL string in a nicely presented format. Safe for output.
	 *
	 * @param  string $sql An SQL query string.
	 * @return string      The SQL formatted with markup.
	 */
	public static function format_sql( $sql ) {

		$sql = str_replace( array( "\r\n", "\r", "\n", "\t" ), ' ', $sql );
		$sql = esc_html( $sql );
		$sql = trim( $sql );

		$regex = 'ADD|AFTER|ALTER|AND|BEGIN|COMMIT|CREATE|DELETE|DESCRIBE|DO|DROP|ELSE|END|EXCEPT|EXPLAIN|FROM|GROUP|HAVING|INNER|INSERT|INTERSECT|LEFT|LIMIT|ON|OR|ORDER|OUTER|RENAME|REPLACE|RIGHT|ROLLBACK|SELECT|SET|SHOW|START|THEN|TRUNCATE|UNION|UPDATE|USE|USING|VALUES|WHEN|WHERE|XOR';
		$sql   = preg_replace( '# (' . $regex . ') #', '<br> $1 ', $sql );

		$keywords = '\b(?:ACTION|ADD|AFTER|ALTER|AND|ASC|AS|AUTO_INCREMENT|BEGIN|BETWEEN|BIGINT|BINARY|BIT|BLOB|BOOLEAN|BOOL|BREAK|BY|CASE|COLLATE|COLUMNS?|COMMIT|CONTINUE|CREATE|DATA(?:BASES?)?|DATE(?:TIME)?|DECIMAL|DECLARE|DEC|DEFAULT|DELAYED|DELETE|DESCRIBE|DESC|DISTINCT|DOUBLE|DO|DROP|DUPLICATE|ELSE|END|ENUM|EXCEPT|EXISTS|EXPLAIN|FIELDS|FLOAT|FOREIGN|FOR|FROM|FULL|FUNCTION|GROUP|HAVING|IF|IGNORE|INDEX|INNER|INSERT|INTEGER|INTERSECT|INTERVAL|INTO|INT|IN|IS|JOIN|KEYS?|LEFT|LIKE|LIMIT|LONG(?:BLOB|TEXT)|MEDIUM(?:BLOB|INT|TEXT)|MERGE|MIDDLEINT|NOT|NO|NULLIF|ON|ORDER|OR|OUTER|PRIMARY|PROC(?:EDURE)?|REGEXP|RENAME|REPLACE|RIGHT|RLIKE|ROLLBACK|SCHEMA|SELECT|SET|SHOW|SMALLINT|START|TABLES?|TEXT(?:SIZE)?|THEN|TIME(?:STAMP)?|TINY(?:BLOB|INT|TEXT)|TRUNCATE|UNION|UNIQUE|UNSIGNED|UPDATE|USE|USING|VALUES?|VAR(?:BINARY|CHAR)|WHEN|WHERE|WHILE|XOR)\b';
		$sql      = preg_replace( '#' . $keywords . '#', '<b>$0</b>', $sql );

		return '<code>' . $sql . '</code>';

	}

	/**
	 * Returns the given URL in a nicely presented format. Safe for output.
	 *
	 * @param  string $url A URL.
	 * @return string      The URL formatted with markup.
	 */
	public static function format_url( $url ) {
		return str_replace( array( '?', '&amp;' ), array( '<br>?', '<br>&amp;' ), esc_html( $url ) );
	}

	/**
	 * Returns a file path, name, and line number, or a clickable link to the file. Safe for output.
	 *
	 * @link https://querymonitor.com/blog/2019/02/clickable-stack-traces-and-function-names-in-query-monitor/
	 *
	 * @param  string $text        The display text, such as a function name or file name.
	 * @param  string $file        The full file path and name.
	 * @param  int    $line        Optional. A line number, if appropriate.
	 * @param  bool   $is_filename Optional. Is the text a plain file name? Default false.
	 * @return string The fully formatted file link or file name, safe for output.
	 */
	public static function output_filename( $text, $file, $line = 0, $is_filename = false ) {
		if ( empty( $file ) ) {
			if ( $is_filename ) {
				return esc_html( $text );
			} else {
				return '<code>' . esc_html( $text ) . '</code>';
			}
		}

		$link_line = ( $line ) ? $line : 1;

		if ( ! self::has_clickable_links() ) {
			$fallback = QM_Util::standard_dir( $file, '' );
			if ( $line ) {
				$fallback .= ':' . $line;
			}
			if ( $is_filename ) {
				$return = esc_html( $text );
			} else {
				$return = '<code>' . esc_html( $text ) . '</code>';
			}
			if ( $fallback !== $text ) {
				$return .= '<br><span class="qm-info qm-supplemental">' . esc_html( $fallback ) . '</span>';
			}
			return $return;
		}

		$map = self::get_file_path_map();

		if ( ! empty( $map ) ) {
			foreach ( $map as $from => $to ) {
				$file = str_replace( $from, $to, $file );
			}
		}

		$link = sprintf( self::get_file_link_format(), rawurlencode( $file ), intval( $link_line ) );

		if ( $is_filename ) {
			$format = '<a href="%s" class="qm-edit-link">%s</a>';
		} else {
			$format = '<a href="%s" class="qm-edit-link"><code>%s</code></a>';
		}

		return sprintf(
			$format,
			esc_attr( $link ),
			esc_html( $text )
		);
	}

	/**
	 * Provides a protocol URL for edit links in QM stack traces for various editors.
	 *
	 * @param string $editor the chosen code editor
	 * @param string $default_format a format to use if no editor is found
	 *
	 * @return string a protocol URL format
	 */
	public static function get_editor_file_link_format( $editor, $default_format ) {
		switch ( $editor ) {
			case 'phpstorm':
				return 'phpstorm://open?file=%f&line=%l';
			case 'vscode':
				return 'vscode://file/%f:%l';
			case 'atom':
				return 'atom://open/?url=file://%f&line=%l';
			case 'sublime':
				return 'subl://open/?url=file://%f&line=%l';
			case 'netbeans':
				return 'nbopen://%f:%l';
			default:
				return $default_format;
		}
	}

	public static function get_file_link_format() {
		if ( ! isset( self::$file_link_format ) ) {
			$format = ini_get( 'xdebug.file_link_format' );

			if ( defined( 'QM_EDITOR_COOKIE' ) && isset( $_COOKIE[ QM_EDITOR_COOKIE ] ) ) {
				$format = self::get_editor_file_link_format(
					$_COOKIE[ QM_EDITOR_COOKIE ],
					$format
				);
			}

			/**
			 * Filters the clickable file link format.
			 *
			 * @link https://querymonitor.com/blog/2019/02/clickable-stack-traces-and-function-names-in-query-monitor/
			 * @since 3.0.0
			 *
			 * @param string $format The format of the clickable file link.
			 */
			$format = apply_filters( 'qm/output/file_link_format', $format );
			if ( empty( $format ) ) {
				self::$file_link_format = false;
			} else {
				self::$file_link_format = str_replace( array( '%f', '%l' ), array( '%1$s', '%2$d' ), $format );
			}
		}

		return self::$file_link_format;
	}

	public static function get_file_path_map() {
		/**
		 * Filters the file path mapping for clickable file links.
		 *
		 * @link https://querymonitor.com/blog/2019/02/clickable-stack-traces-and-function-names-in-query-monitor/
		 * @since 3.0.0
		 *
		 * @param array $file_map Array of file path mappings.
		 */
		return apply_filters( 'qm/output/file_path_map', array() );
	}

	public static function has_clickable_links() {
		return ( false !== self::get_file_link_format() );
	}

}
