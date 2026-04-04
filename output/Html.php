<?php declare(strict_types = 1);
/**
 * Abstract output class for HTML pages.
 *
 * @package query-monitor
 */

abstract class QM_Output_Html extends QM_Output {

	/**
	 * @var bool
	 */
	public static $client_side_rendered = false;

	/**
	 * @var string|false|null
	 */
	protected static $file_link_format = null;

	/**
	 * @var string|null
	 */
	protected $current_id = null;

	/**
	 * @var string|null
	 */
	protected $current_name = null;

	/**
	 * @return string
	 */
	public function name() {
		return $this->collector->id;
	}

	/**
	 * @param array<string, mixed[]> $menu
	 * @return array<string, mixed[]>
	 */
	public function admin_menu( array $menu ) {

		$menu[ $this->collector->id() ] = $this->menu( array(
			'title' => $this->name(),
		) );
		return $menu;

	}

	/**
	 * @return string
	 */
	public function get_output() {
		ob_start();
		// compat until I convert all the existing outputters to use `get_output()`
		$this->output();
		$out = (string) ob_get_clean();
		return $out;
	}

	/**
	 * @param string $id
	 * @param string $name
	 * @return void
	 */
	protected function before_tabular_output( $id = null, $name = null ) {
		if ( null === $id ) {
			$id = $this->collector->id();
		}
		if ( null === $name ) {
			$name = $this->name();
		}

		$this->current_id = $id;
		$this->current_name = $name;

		printf(
			'<div class="qm" id="%1$s" role="tabpanel" aria-labelledby="%1$s-caption" tabindex="-1">' . "\n",
			esc_attr( $id )
		);

		echo '<table>';

		printf(
			'<caption class="qm-screen-reader-text"><h2 id="%1$s-caption">%2$s</h2></caption>' . "\n",
			esc_attr( $id ),
			esc_html( $name )
		);
	}

	/**
	 * @return void
	 */
	protected function after_tabular_output() {
		echo '</table>';
		echo '</div>';
	}

	/**
	 * @param string $id
	 * @param string $name
	 * @return void
	 */
	protected function before_non_tabular_output( $id = null, $name = null ) {
		if ( null === $id ) {
			$id = $this->collector->id();
		}
		if ( null === $name ) {
			$name = $this->name();
		}

		$this->current_id = $id;
		$this->current_name = $name;

		printf(
			'<div class="qm qm-non-tabular" id="%1$s" role="tabpanel" aria-labelledby="%1$s-caption" tabindex="-1">' . "\n",
			esc_attr( $id )
		);

		echo '<div class="qm-boxed">' . "\n";

		printf(
			'<h2 class="qm-screen-reader-text" id="%1$s-caption">%2$s</h2>' . "\n",
			esc_attr( $id ),
			esc_html( $name )
		);
	}

	/**
	 * @return void
	 */
	protected function after_non_tabular_output() {
		echo '</div>';
		echo '</div>';
	}

	/**
	 * @deprecated
	 * @return void
	 */
	protected function output_concerns() {}

	/**
	 * @param string $id
	 * @param string $name
	 * @return void
	 */
	protected function before_debug_bar_output( $id = null, $name = null ) {
		if ( null === $id ) {
			$id = $this->collector->id();
		}
		if ( null === $name ) {
			$name = $this->name();
		}

		printf(
			'<div class="qm qm-debug-bar" id="%1$s" role="tabpanel" aria-labelledby="%1$s-caption" tabindex="-1">' . "\n",
			esc_attr( $id )
		);

		printf(
			'<h2 class="qm-screen-reader-text" id="%1$s-caption">%2$s</h2>' . "\n",
			esc_attr( $id ),
			esc_html( $name )
		);
	}

	/**
	 * @return void
	 */
	protected function after_debug_bar_output() {
		echo '</div>' . "\n";
	}

	/**
	 * @param string $notice
	 * @return string
	 */
	protected function build_notice( $notice ) {
		$return = '<section>' . "\n";
		$return .= '<div class="qm-empty">' . "\n";
		$return .= '<p>';
		$return .= $notice;
		$return .= '</p>' . "\n";
		$return .= '</div>' . "\n";
		$return .= '</section>' . "\n";

		return $return;
	}

	/**
	 * @param array<string, mixed> $vars
	 * @return void
	 */
	public static function output_inner( array $vars ) {

		echo '<table>' . "\n";

		foreach ( $vars as $key => $value ) {
			echo '<tr>' . "\n";
			echo '<td>' . esc_html( $key ) . '</td>' . "\n";
			if ( is_array( $value ) ) {
				echo '<td>';
				self::output_inner( $value );
				echo '</td>' . "\n";
			} elseif ( is_object( $value ) ) {
				echo '<td>';
				self::output_inner( get_object_vars( $value ) );
				echo '</td>' . "\n";
			} elseif ( is_bool( $value ) ) {
				if ( $value ) {
					echo '<td class="qm-true">true</td>' . "\n";
				} else {
					echo '<td class="qm-false">false</td>' . "\n";
				}
			} else {
				echo '<td>';
				echo nl2br( esc_html( $value ) );
				echo '</td>' . "\n";
			}
			echo '</tr>' . "\n";
		}
		echo '</table>' . "\n";

	}

	/**
	 * Returns the table filter controls. No longer used. Safe for output.
	 *
	 * @param  string         $name   The name for the `data-` attributes that get filtered by this control.
	 * @param  (string|int)[] $values Option values for this control.
	 * @param  string         $label  Label text for the filter control.
	 * @param  array          $args {
	 *     @type string   $highlight The name for the `data-` attributes that get highlighted by this control.
	 *     @type string[] $prepend   Associative array of options to prepend to the list of values.
	 *     @type string[] $append    Associative array of options to append to the list of values.
	 * }
	 * @phpstan-param array{
	 *   highlight?: string,
	 *   prepend?: array<string, string>,
	 *   append?: array<string, string>,
	 * } $args
	 * @return string Markup for the table filter controls.
	 */
	protected function build_filter( $name, $values, $label, $args = array() ) {
		return esc_html( $label );
	}

	/**
	 * Returns the column sorter controls. No longer used. Safe for output.
	 *
	 * @param string $heading Heading text for the column. Optional.
	 * @return string Markup for the column sorter controls.
	 */
	protected function build_sorter( $heading = '' ) {
		return esc_html( $heading );
	}

	/**
	 * Returns a toggle control. No longer used. Safe for output.
	 *
	 * @param string $context Optional. Information to uniquely label the toggle button for screen readers.
	 * @return string Markup for the toggle control.
	 */
	protected static function build_toggler( $context = '' ) {
		if ( $context !== '' ) {
			$label = sprintf(
				/* translators: %s: Context for the toggle button, e.g. a function name. */
				__( 'Toggle more information for %s', 'query-monitor' ),
				wp_strip_all_tags( $context )
			);
		} else {
			$label = __( 'Toggle more information', 'query-monitor' );
		}

		return sprintf(
			'<button class="qm-toggle" data-on="+" data-off="-" aria-expanded="false" aria-label="%s"><span aria-hidden="true">+</span></button>',
			esc_attr( $label )
		);
	}

	/**
	 * Returns a filter trigger.
	 *
	 * @param string $target
	 * @param string $filter
	 * @param string $value
	 * @param string $label
	 * @return string
	 */
	protected static function build_filter_trigger( $target, $filter, $value, $label ) {
		return esc_html( $value );
	}

	/**
	 * Returns a link.
	 *
	 * @param string $href
	 * @param string $label
	 * @return string
	 */
	protected static function build_link( $href, $label ) {
		return sprintf(
			'<a href="%1$s" class="qm-link">%2$s%3$s</a>',
			esc_attr( $href ),
			$label,
			QueryMonitor::icon( 'external' )
		);
	}

	/**
	 * @param array<string, mixed> $args
	 * @return array<string, mixed>
	 */
	protected function menu( array $args ) {
		return array_merge( array(
			'id' => $this->collector->id,
			'panel' => $this->collector->id,
		), $args );
	}

	/**
	 * Returns the given SQL string in a nicely presented format. Safe for output.
	 *
	 * @deprecated Use formatSQL in a React component instead.
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

		$keywords = '\b(?:ACTION|ADD|AFTER|AGAINST|ALTER|AND|ASC|AS|AUTO_INCREMENT|BEGIN|BETWEEN|BIGINT|BINARY|BIT|BLOB|BOOLEAN|BOOL|BREAK|BY|CASE|COLLATE|COLUMNS?|COMMIT|CONTINUE|CREATE|DATA(?:BASES?)?|DATE(?:TIME)?|DECIMAL|DECLARE|DEC|DEFAULT|DELAYED|DELETE|DESCRIBE|DESC|DISTINCT|DOUBLE|DO|DROP|DUPLICATE|ELSE|END|ENUM|EXCEPT|EXISTS|EXPLAIN|FIELDS|FLOAT|FORCE|FOREIGN|FOR|FROM|FULL|FUNCTION|GROUP BY|GROUP|HAVING|IF|IGNORE|INDEX|INNER JOIN|INSERT|INTEGER|INTERSECT|INTERVAL|INTO|INT|IN|IS|KEYS?|LEFT JOIN|LIKE|LIMIT|LONG(?:BLOB|TEXT)|MEDIUM(?:BLOB|INT|TEXT)|MATCH|MERGE|MIDDLEINT|NOT|NO|NULLIF|ON|ORDER BY|ORDER|OR|OUTER JOIN|PRIMARY|PROC(?:EDURE)?|REGEXP|RENAME|REPLACE|RIGHT JOIN|RLIKE|ROLLBACK|SCHEMA|SELECT|SET|SHOW|SMALLINT|START|TABLES?|TEXT(?:SIZE)?|THEN|TIME(?:STAMP)?|TINY(?:BLOB|INT|TEXT)|TRUNCATE|UNION|UNIQUE|UNSIGNED|UPDATE|USE|USING|VALUES?|JOIN|VAR(?:BINARY|CHAR)|WHEN|WHERE|WHILE|XOR)\b';
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
		// If there's no query string or only a single query parameter, return the URL as is.
		if ( ! str_contains( $url, '&' ) ) {
			return esc_html( $url );
		}

		return str_replace( array( '?', '&amp;' ), array( '<br>?', '<br>&amp;' ), esc_html( $url ) );
	}

	/**
	 * Returns a file path, name, and line number, or a clickable link to the file. Safe for output.
	 *
	 * @link https://querymonitor.com/help/clickable-stack-traces-and-function-names/
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

		$fallback = QM_Util::standard_dir( $file, '' );

		if ( $line ) {
			$fallback .= ':' . $line;
		}

		/*
		 * A closure in PHP looks like:
		 *
		 * - `{closure}` in PHP < 8.3
		 * - {closure:/<file>:<line>}` in PHP >= 8.4
		 */
		if ( 0 === strpos( $text, '{closure' ) ) {
			$text = sprintf(
				/* translators: A closure is an anonymous PHP function. 1: Line number, 2: File name */
				__( 'Closure on line %1$d of %2$s', 'query-monitor' ),
				$line,
				QM_Util::standard_dir( $file, '' )
			);
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

	/**
	 * Provides a protocol URL for edit links in QM stack traces for various editors.
	 *
	 * @deprecated
	 *
	 * @param string       $editor         The chosen code editor.
	 * @param string|false $default_format A format to use if no editor is found.
	 * @return string|false A protocol URL format or boolean false.
	 */
	public static function get_editor_file_link_format( $editor, $default_format ) {
		return $default_format;
	}

	/**
	 * Returns the fallback file link format from xdebug configuration.
	 *
	 * Uses `%1$s` for file and `%2$d` for line, matching the sprintf format
	 * used by the editor formats in JavaScript.
	 *
	 * @return string|false
	 */
	public static function get_file_link_format() {
		if ( ! isset( self::$file_link_format ) ) {
			$format = ini_get( 'xdebug.file_link_format' );

			/**
			 * Filters the clickable file link format.
			 *
			 * @link https://querymonitor.com/help/clickable-stack-traces-and-function-names/
			 * @since 3.0.0
			 *
			 * @param string|false $format The format of the clickable file link, or false if there is none.
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

	/**
	 * @return array<string, string>
	 */
	public static function get_file_path_map() {
		$map = array();

		// WordPress core and Altis:
		$host_path = getenv( 'HOST_PATH' );

		if ( ! empty( $host_path ) ) {
			$source = ABSPATH;
			$replacement = trailingslashit( $host_path );
			$map[ $source ] = $replacement;
		}

		// WordPress VIP on Lando:
		$lando_path = getenv( 'VIP_DEV_AUTOLOGIN_KEY' ) ? getenv( 'LANDO_APP_ROOT_BIND' ) : null;

		if ( ! empty( $lando_path ) ) {
			// https://github.com/Automattic/vip-cli/blob/2bf64a46b9d409a5683459d032d65c16a6eeac48/assets/dev-env.lando.template.yml.ejs#L288
			$source = ABSPATH;
			$replacement = trailingslashit( $lando_path ) . 'wordpress/';
			$map[ $source ] = $replacement;
		}

		/**
		 * Filters the file path mapping for clickable file links.
		 *
		 * @link https://querymonitor.com/help/clickable-stack-traces-and-function-names/
		 * @since 3.0.0
		 *
		 * @param array<string, string> $file_map Array of file path mappings. Keys are the source paths and values are the replacement paths.
		 */
		return apply_filters( 'qm/output/file_path_map', $map );
	}

	/**
	 * @deprecated
	 *
	 * @return false
	 */
	public static function has_clickable_links() {
		return false;
	}
}
