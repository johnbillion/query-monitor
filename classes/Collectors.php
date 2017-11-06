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

if ( ! class_exists( 'QM_Collectors' ) ) {
class QM_Collectors implements IteratorAggregate {

	private $items = array();
	private $processed = false;

	public function getIterator() {
		return new ArrayIterator( $this->items );
	}

	public static function add( QM_Collector $collector ) {
		$collectors = self::init();
		$collectors->items[ $collector->id ] = $collector;
	}

	public static function get( $id ) {
		$collectors = self::init();
		if ( isset( $collectors->items[ $id ] ) ) {
			return $collectors->items[ $id ];
		}
		return false;
	}

	public static function init() {
		static $instance;

		if ( ! $instance ) {
			$instance = new QM_Collectors;
		}

		return $instance;

	}

	public function process() {
		if ( $this->processed ) {
			return;
		}
		foreach ( $this as $collector ) {
			$collector->tear_down();
			$collector->process();
		}
		$this->processed = true;
	}

}
}
