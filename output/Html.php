<?php
/**
 * Abstract output class for HTML pages.
 *
 * @package query-monitor
 */

abstract class QM_Output_Html extends QM_Output {

	protected static $file_link_format = null;

	public function admin_menu( array $menu ) {

		$menu[] = $this->menu( array(
			'title' => esc_html( $this->collector->name() ),
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
			$name = $this->collector->name();
		}

		printf(
			'<div class="qm" id="%1$s" role="group" aria-labelledby="%1$s-caption" tabindex="-1">',
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
	}

	protected function before_non_tabular_output( $id = null, $name = null ) {
		if ( null === $id ) {
			$id = $this->collector->id();
		}
		if ( null === $name ) {
			$name = $this->collector->name();
		}

		printf(
			'<div class="qm qm-non-tabular" id="%1$s" role="group" aria-labelledby="%1$s-caption" tabindex="-1">',
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
	}

	protected function before_debug_bar_output( $id = null, $name = null ) {
		if ( null === $id ) {
			$id = $this->collector->id();
		}
		if ( null === $name ) {
			$name = $this->collector->name();
		}

		printf(
			'<div class="qm qm-debug-bar" id="%1$s" role="group" aria-labelledby="%1$s-caption" tabindex="-1">',
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
		$return = '<div class="qm-section">';
		$return .= '<div class="qm-notice">';
		$return .= '<p>';
		$return .= $notice;
		$return .= '</p>';
		$return .= '</div>';
		$return .= '</div>';

		return $return;
	}

	public static function output_inner( $vars ) {

		echo '<table class="qm-inner">';

		foreach ( $vars as $key => $value ) {
			echo '<tr>';
			echo '<td>' . esc_html( $key ) . '</td>';
			if ( is_array( $value ) ) {
				echo '<td class="qm-has-inner">';
				self::output_inner( $value );
				echo '</td>';
			} elseif ( is_object( $value ) ) {
				echo '<td class="qm-has-inner">';
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
	 *     @type string $highlihgt The name for the `data-` attributes that get highlighted by this control.
	 *     @type array  $prepend   Associative array of options to prepend to the list of values.
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
		), $args );

		$core = __( 'Core', 'query-monitor' );

		if ( 'component' === $name && count( $values ) > 1 && in_array( $core, $values, true ) ) {
			$args['prepend']['non-core'] = __( 'Non-Core', 'query-monitor' );
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

		foreach ( $values as $value ) {
			$out .= '<option value="' . esc_attr( $value ) . '">' . esc_html( $value ) . '</option>';
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
		$out .= '<button class="qm-sort-controls">';
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
		$out = '<button class="qm-toggle" data-on="+" data-off="-" aria-expanded="false"><span aria-hidden="true">+</span><span class="screen-reader-text">' . esc_html__( ' Toggle button', 'query-monitor' ) . '</span></button>';
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
		$sql = preg_replace( '# (' . $regex . ') #', '<br> $1 ', $sql );

		$keywords = '\b(?:ACTION|ADD|AFTER|ALTER|AND|ASC|AS|AUTO_INCREMENT|BEGIN|BETWEEN|BIGINT|BINARY|BIT|BLOB|BOOLEAN|BOOL|BREAK|BY|CASE|COLLATE|COLUMNS?|COMMIT|CONTINUE|CREATE|DATA(?:BASES?)?|DATE(?:TIME)?|DECIMAL|DECLARE|DEC|DEFAULT|DELAYED|DELETE|DESCRIBE|DESC|DISTINCT|DOUBLE|DO|DROP|DUPLICATE|ELSE|END|ENUM|EXCEPT|EXISTS|EXPLAIN|FIELDS|FLOAT|FOREIGN|FOR|FROM|FULL|FUNCTION|GROUP|HAVING|IF|IGNORE|INDEX|INNER|INSERT|INTEGER|INTERSECT|INTERVAL|INTO|INT|IN|IS|JOIN|KEYS?|LEFT|LIKE|LIMIT|LONG(?:BLOB|TEXT)|MEDIUM(?:BLOB|INT|TEXT)|MERGE|MIDDLEINT|NOT|NO|NULLIF|ON|ORDER|OR|OUTER|PRIMARY|PROC(?:EDURE)?|REGEXP|RENAME|REPLACE|RIGHT|RLIKE|ROLLBACK|SCHEMA|SELECT|SET|SHOW|SMALLINT|START|TABLES?|TEXT(?:SIZE)?|THEN|TIME(?:STAMP)?|TINY(?:BLOB|INT|TEXT)|TRUNCATE|UNION|UNIQUE|UNSIGNED|UPDATE|USE|USING|VALUES?|VAR(?:BINARY|CHAR)|WHEN|WHERE|WHILE|XOR)\b';
		$sql = preg_replace( '#' . $keywords . '#', '<b>$0</b>', $sql );

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
	 * Returns a file path, name, and line number. Safe for output.
	 *
	 * If clickable file links are enabled via the `xdebug.file_link_format` setting in the PHP configuration,
	 * a link such as this is returned:
	 *
	 *     <a href="subl://open/?line={line}&url={file}">{text}</a>
	 *
	 * Otherwise, the display text and file details such as this is returned:
	 *
	 *     {text}<br>{file}:{line}
	 *
	 * Further information on clickable stack traces for your editor:
	 *
	 * PhpStorm: (support is built in)
	 * `phpstorm://open?file=%f&line=%l`
	 *
	 * Visual Studio Code: (support is built in)
	 * `vscode://file/%f:%l`
	 *
	 * Sublime Text: https://github.com/corysimmons/subl-handler
	 * `subl://open/?url=file://%f&line=%l`
	 *
	 * Atom: https://github.com/WizardOfOgz/atom-handler
	 * `atm://open/?url=file://%f&line=%l`
	 *
	 * Netbeans: http://simonwheatley.co.uk/2012/08/clickable-stack-traces-with-netbeans/
	 * `nbopen://%f:%l`
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

	public static function get_file_link_format() {
		if ( ! isset( self::$file_link_format ) ) {
			$format = ini_get( 'xdebug.file_link_format' );
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
		// @TODO document this!
		return apply_filters( 'qm/output/file_path_map', array() );
	}

	public static function has_clickable_links() {
		return ( false !== self::get_file_link_format() );
	}

}
