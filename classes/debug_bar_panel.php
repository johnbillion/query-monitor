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

abstract class Debug_Bar_Panel {

	public $_title = '';
	public $_visible = true;

	public function __construct( $title = '' ) {
		$this->title( $title );

		if ( $this->init() === false ) {
			$this->set_visible( false );
			return;
		}

		# @TODO convert to QM classes
		add_filter( 'debug_bar_classes', array( $this, 'debug_bar_classes' ) );
	}

	/**
	 * Initializes the panel.
	 */
	public function init() {}

	public function prerender() {}

	/**
	 * Renders the panel.
	 */
	public function render() {}

	public function is_visible() {
		return $this->_visible;
	}

	public function set_visible( $visible ) {
		$this->_visible = $visible;
	}

	public function title( $title = null ) {
		if ( ! isset( $title ) ) {
			return $this->_title;
		}
		$this->_title = $title;
	}

	public function debug_bar_classes( $classes ) {
		return $classes;
	}

	public function Debug_Bar_Panel( $title = '' ) {
		Debug_Bar_Panel::__construct( $title );
	}

}
