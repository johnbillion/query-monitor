<?php
/*
Copyright 2009-2015 John Blackbourn

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

*/

abstract class QM_Output_Html implements QM_Output {

	protected static $file_link_format = null;

	public function __construct( QM_Collector $collector ) {
		$this->collector = $collector;
	}

	public function admin_menu( array $menu ) {

		$menu[] = $this->menu( array(
			'title' => $this->collector->name(),
		) );
		return $menu;

	}

	public static function output_inner( $vars ) {

		echo '<table cellspacing="0" class="qm-inner">';

		foreach ( $vars as $key => $value ) {
			echo '<tr>';
			echo '<td valign="top">' . esc_html( $key ) . '</td>';
			if ( is_array( $value ) ) {
				echo '<td valign="top" class="qm-has-inner">';
				self::output_inner( $value );
				echo '</td>';
			} else if ( is_object( $value ) ) {
				echo '<td valign="top" class="qm-has-inner">';
				self::output_inner( get_object_vars( $value ) );
				echo '</td>';
			} else if ( is_bool( $value ) ) {
				if ( $value ) {
					echo '<td valign="top" class="qm-true">true</td>';
				} else {
					echo '<td valign="top" class="qm-false">false</td>';
				}
			} else {
				echo '<td valign="top">';
				echo nl2br( esc_html( $value ) );
				echo '</td>';
			}
			echo '</td>';
			echo '</tr>';
		}
		echo '</table>';

	}

	protected function build_filter( $name, array $values, $highlight = '' ) {

		if ( empty( $values ) ) {
			return '';
		}

		usort( $values, 'strcasecmp' );

		$out = '<select id="qm-filter-' . esc_attr( $this->collector->id . '-' . $name ) . '" class="qm-filter" data-filter="' . esc_attr( $name ) . '" data-highlight="' . esc_attr( $highlight ) . '">';
		$out .= '<option value="">' . _x( 'All', '"All" option for filters', 'query-monitor' ) . '</option>';

		foreach ( $values as $value ) {
			$out .= '<option value="' . esc_attr( $value ) . '">' . esc_html( $value ) . '</option>';
		}

		$out .= '</select>';

		return $out;

	}

	protected function build_sorter() {
		$out = '<span class="qm-sort-controls">';
		$out .= '<a href="#" class="qm-sort qm-sort-asc">&#9650;</a>';
		$out .= '<a href="#" class="qm-sort qm-sort-desc">&#9660;</a>';
		$out .= '</span>';
		return $out;
	}

	protected function menu( array $args ) {

		return array_merge( array(
			'id'   => "query-monitor-{$this->collector->id}",
			'href' => '#' . $this->collector->id()
		), $args );

	}

	public static function format_sql( $sql ) {

		$sql = str_replace( array( "\r\n", "\r", "\n", "\t" ), ' ', $sql );
		$sql = esc_html( $sql );
		$sql = trim( $sql );

		foreach( array(
			'ALTER', 'AND', 'COMMIT', 'CREATE', 'DESCRIBE', 'DELETE', 'DROP', 'ELSE', 'END', 'FROM', 'GROUP',
			'HAVING', 'INNER', 'INSERT', 'LEFT', 'LIMIT', 'ON', 'OR', 'ORDER', 'OUTER', 'REPLACE', 'RIGHT', 'ROLLBACK', 'SELECT', 'SET',
			'SHOW', 'START', 'THEN', 'TRUNCATE', 'UPDATE', 'VALUES', 'WHEN', 'WHERE'
		) as $cmd ) {
			$sql = trim( str_replace( " $cmd ", "<br>$cmd ", $sql ) );
		}

		# @TODO profile this as an alternative:
		# $sql = preg_replace( '# (ALTER|AND|COMMIT|CREATE|DESCRIBE) #', '<br>$1 ', $sql );

		return $sql;

	}

	public static function format_url( $url ) {
		$url = str_replace( array(
			'=',
			'&amp;',
			'?',
		), array(
			'<span class="qm-equals">=</span>',
			'<br><span class="qm-param">&amp;</span>',
			'<br><span class="qm-param">?</span>',
		), esc_html( $url ) );
		return $url;

	}

	public static function output_filename( $text, $file, $line = 1 ) {

		# Further reading:
		# http://simonwheatley.co.uk/2012/07/clickable-stack-traces/
		# https://github.com/grych/subl-handler

		if ( !isset( self::$file_link_format ) ) {
			$format = ini_get( 'xdebug.file_link_format' );
			$format = apply_filters( 'qm/output/file_link_format', $format );
			if ( empty( $format ) ) {
				self::$file_link_format = false;
			} else {
				self::$file_link_format = str_replace( array( '%f', '%l' ), array( '%1$s', '%2$d' ), $format );
			}
		}

		if ( false === self::$file_link_format ) {
			return $text;
		}

		$link = sprintf( self::$file_link_format, urlencode( $file ), $line );
		return sprintf( '<a href="%s">%s</a>', $link, $text );

	}

}
