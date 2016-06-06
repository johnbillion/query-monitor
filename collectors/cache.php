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

class QM_Collector_Cache extends QM_Collector {

	public $id = 'cache';

	public function name() {
		return __( 'Cache', 'query-monitor' );
	}

	public function process() {
		global $wp_object_cache;

		$this->data['ext_object_cache'] = (bool) wp_using_ext_object_cache();

		if ( is_object( $wp_object_cache ) ) {

			if ( isset( $wp_object_cache->cache_hits ) ) {
				$this->data['stats']['cache_hits'] = $wp_object_cache->cache_hits;
			}

			if ( isset( $wp_object_cache->cache_misses ) ) {
				$this->data['stats']['cache_misses'] = $wp_object_cache->cache_misses;
			}

			if ( isset( $wp_object_cache->stats ) ) {
				foreach ( $wp_object_cache->stats as $key => $value ) {
					if ( ! is_scalar( $value ) ) {
						continue;
					}
					$this->data['stats'][ $key ] = $value;
				}
			}

		}

		if ( isset( $this->data['stats']['cache_hits'] ) && isset( $this->data['stats']['cache_misses'] ) ) {
			$total = $this->data['stats']['cache_misses'] + $this->data['stats']['cache_hits'];
			$this->data['cache_hit_percentage'] = ( 100 / $total ) * $this->data['stats']['cache_hits'];
		}

	}

}

function register_qm_collector_cache( array $collectors, QueryMonitor $qm ) {
	$collectors['cache'] = new QM_Collector_Cache;
	return $collectors;
}

add_filter( 'qm/collectors', 'register_qm_collector_cache', 20, 2 );
