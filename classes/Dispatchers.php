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

class QM_Dispatchers implements IteratorAggregate {

	private $items = array();

	public function getIterator() {
		return new ArrayIterator( $this->items );
	}

	public static function add( QM_Dispatcher $dispatcher ) {
		$dispatchers = self::init();
		$dispatchers->items[ $dispatcher->id ] = $dispatcher;
	}

	public static function get( $id ) {
		$dispatchers = self::init();
		if ( isset( $dispatchers->items[ $id ] ) ) {
			return $dispatchers->items[ $id ];
		}
		return false;
	}

	public static function init() {
		static $instance;

		if ( ! $instance ) {
			$instance = new QM_Dispatchers;
		}

		return $instance;

	}

}
