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

class QM_Collector_Cache extends QM_Collector {

	public $id = 'cache';

	public function name() {
		return __( 'Cache', 'query-monitor' );
	}

	public function process() {
		global $wp_object_cache;

		$this->data['ext_object_cache'] = (bool) wp_using_ext_object_cache();
		$this->data['cache_hit_percentage'] = 0;

		if ( is_object( $wp_object_cache ) ) {
			if ( property_exists( $wp_object_cache, 'cache_hits' ) ) {
				$this->data['stats']['cache_hits'] = (int) $wp_object_cache->cache_hits;
			}

			if ( property_exists( $wp_object_cache, 'cache_misses' ) ) {
				$this->data['stats']['cache_misses'] = (int) $wp_object_cache->cache_misses;
			}

			if ( property_exists( $wp_object_cache, 'stats' ) && is_array( $wp_object_cache->stats ) ) {
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

		$this->data['display_hit_rate_warning'] = ( 100 === $this->data['cache_hit_percentage'] );

	}

}

function register_qm_collector_cache( array $collectors, QueryMonitor $qm ) {
	$collectors['cache'] = new QM_Collector_Cache;
	return $collectors;
}

add_filter( 'qm/collectors', 'register_qm_collector_cache', 20, 2 );
