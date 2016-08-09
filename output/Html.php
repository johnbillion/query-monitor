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

	public static function output_inner( $vars ) {

		echo '<table cellspacing="0" class="qm-inner">';

		foreach ( $vars as $key => $value ) {
			echo '<tr>';
			echo '<td>' . esc_html( $key ) . '</td>';
			if ( is_array( $value ) ) {
				echo '<td class="qm-has-inner">';
				self::output_inner( $value );
				echo '</td>';
			} else if ( is_object( $value ) ) {
				echo '<td class="qm-has-inner">';
				self::output_inner( get_object_vars( $value ) );
				echo '</td>';
			} else if ( is_bool( $value ) ) {
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
	 * @param  string $name      The name for the `data-` attributes that get filtered by this control.
	 * @param  array  $values    Possible values for this control.
	 * @param  string $label     Label text for the filter control.
	 * @param  string $highlight Optional. The name for the `data-` attributes that get highlighted by this control.
	 * @return string            Markup for the table filter controls.
	 */
	protected function build_filter( $name, array $values, $label, $highlight = '' ) {

		if ( empty( $values ) ) {
			return esc_html( $label ); // Return label text, without being marked up as a label element.
		}

		usort( $values, 'strcasecmp' );

		$filter_id = 'qm-filter-' . $this->collector->id . '-' . $name;

		$out = '<label for="' . esc_attr( $filter_id ) .'">' . esc_html( $label ) . '</label>';
		$out .= '<select id="' . esc_attr( $filter_id ) . '" class="qm-filter" data-filter="' . esc_attr( $name ) . '" data-highlight="' . esc_attr( $highlight ) . '" data-qm-user-option-key="qm/' . esc_attr( $this->collector->id ) . '/filter/' . esc_attr( $name ) . '">';
		$out .= '<option value="">' . esc_html_x( 'All', '"All" option for filters', 'query-monitor' ) . '</option>';

		foreach ( $values as $value ) {
			$out .= '<option value="' . esc_attr( $value ) . '">' . esc_html( $value ) . '</option>';
		}

		$out .= '</select>';

		return $out;

	}

	/**
	 * Returns the column sorter controls. Safe for output.
	 *
	 * @return string Markup for the column sorter controls.
	 */
	protected function build_sorter( $col = false ) {
		$out = '<span class="qm-sort-controls">';
		/* translators: Button for sorting table columns in ascending order */
		$out .= '<button ' .
			'class="qm-sort qm-sort-asc"' .
				( false !== $col
					? ' data-qm-user-option-key="' . esc_attr( $this->collector->id ) . '/sort"' .
					  ' data-qm-user-option-value="' . esc_attr( json_encode( array( 'col' => $col, 'order' => 'asc' ) ) ) . '"'
					: ''
				) .
			'><span class="screen-reader-text">' . esc_html__( 'Ascending', 'query-monitor' ) . '</span></button>';
		/* translators: Button for sorting table columns in descending order */
		$out .= '<button ' .
			'class="qm-sort qm-sort-desc"' .
				( false !== $col
					? ' data-qm-user-option-key="' . esc_attr( $this->collector->id ) . '/sort"' .
					  ' data-qm-user-option-value="' . esc_attr( json_encode( array( 'col' => $col, 'order' => 'desc' ) ) ) . '"'
					: ''
				) . '><span class="screen-reader-text">' . esc_html__( 'Descending', 'query-monitor' ) . '</span></button>';
		$out .= '</span>';
		return $out;
	}

	protected function menu( array $args ) {

		return array_merge( array(
			'id'   => esc_attr( "query-monitor-{$this->collector->id}" ),
			'href' => esc_attr( '#' . $this->collector->id() )
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

		$regex = 'ADD|AFTER|ALTER|AND|BEGIN|COMMIT|CREATE|DESCRIBE|DELETE|DROP|ELSE|END|EXCEPT|FROM|GROUP|HAVING|INNER|INSERT|INTERSECT|LEFT|LIMIT|ON|OR|ORDER|OUTER|REPLACE|RIGHT|ROLLBACK|SELECT|SET|SHOW|START|THEN|TRUNCATE|UNION|UPDATE|USING|VALUES|WHEN|WHERE|XOR';
		$sql = preg_replace( '# (' . $regex . ') #', '<br>$1 ', $sql );

		return $sql;

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
	 * If clickable file links are enabled, a link such as this is returned:
	 *
	 *     <a href="subl://open/?line={line}&url={file}">{text}</a>
	 *
	 * Otherwise, the display text and file details such as this is returned:
	 *
	 *     {text}<br>{file}:{line}
	 *
	 * @param  string $text The display text, such as a function name or file name.
	 * @param  string $file The full file path and name.
	 * @param  int    $line Optional. A line number, if appropriate.
	 * @return string The fully formatted file link or file name, safe for output.
	 */
	public static function output_filename( $text, $file, $line = 0 ) {

		if ( empty( $file ) ) {
			return esc_html( $text );
		}

		# Further reading:
		# http://simonwheatley.co.uk/2012/07/clickable-stack-traces/
		# https://github.com/grych/subl-handler

		$link_line = ( $line ) ? $line : 1;

		if ( ! isset( self::$file_link_format ) ) {
			$format = ini_get( 'xdebug.file_link_format' );
			$format = apply_filters( 'qm/output/file_link_format', $format );
			if ( empty( $format ) ) {
				self::$file_link_format = false;
			} else {
				self::$file_link_format = str_replace( array( '%f', '%l' ), array( '%1$s', '%2$d' ), $format );
			}
		}

		if ( false === self::$file_link_format ) {
			$fallback = QM_Util::standard_dir( $file, '' );
			if ( $line ) {
				$fallback .= ':' . $line;
			}
			$return = esc_html( $text );
			if ( $fallback !== $text ) {
				$return .= '<br><span class="qm-info">&nbsp;' . esc_html( $fallback ) . '</span>';
			}
			return $return;
		}

		$link = sprintf( self::$file_link_format, urlencode( $file ), intval( $link_line ) );
		return sprintf( '<a href="%s">%s</a>', esc_attr( $link ), esc_html( $text ) );

	}

	protected function get_user_pref( $key, $default = false ) {
		if ( $this->has_user_pref( $key, QM_Dispatcher_Html::$user_prefs ) )
			return QM_Dispatcher_Html::$user_prefs[$key];

		return $default;
	}

		protected function get_user_pref_sort( $key, $default = false ) {
			return $this->user_pref_sort = $this->get_user_pref( $key, $default );
		}

	protected function has_user_pref( $key, $prefs = false ) {
		if ( false === $prefs )
			$prefs = QM_Dispatcher_Html::$user_prefs;

		return $prefs && is_array( $prefs ) && count( $prefs ) && array_key_exists( $key, $prefs );
	}

	protected function get_user_pref_sort_class( $col, $default = '' ) {
		if (
			false === $this->user_pref_sort
			|| !is_array( $this->user_pref_sort )
			|| !array_key_exists( 'col', $this->user_pref_sort )
		)
			return $default;

		return (
			$col === $this->user_pref_sort['col']
			&& array_key_exists( 'order', $this->user_pref_sort )
				? ' qm-sorted-' . $this->user_pref_sort['order']
				: ''
		);
	}

	protected function get_data_sorted_by_user_pref( $data ) {
		$temp = $data;
		$first = array_shift( $temp );
		unset( $temp );

		$type = array_key_exists( $this->user_pref_sort['col'], $first )
			? is_numeric( $first[$this->user_pref_sort['col']] )
				? 'SORT_NUMERIC'
				: 'SORT_REGULAR'
			: 'SORT_REGULAR'
		;
		unset($first);

		$sorter = $this->get_sort_column_values( $data );

		if ( 'SORT_NUMERIC' === $type )
			array_map( 'intval', $sorter );

		array_multisort(
			$sorter,
			( 'asc' === $this->user_pref_sort['order']
				? SORT_ASC
				: SORT_DESC
			),
			( 'SORT_NUMERIC' === $type
				? SORT_NUMERIC
				: SORT_REGULAR
			),
			$data
		);

		return $data;
	}

	public function get_sort_column_values( $data ) {
		$sorter = array();
		foreach ( $data as $row )
			$sorter[] = strtolower( $row[$this->user_pref_sort['col']] );
		return $sorter;
	}

}
