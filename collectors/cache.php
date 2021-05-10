<?php
/**
 * Object cache collector.
 *
 * @package query-monitor
 */

defined( 'ABSPATH' ) || exit;

class QM_Collector_Cache extends QM_Collector {

	public $id = 'cache';

	public function process() {
		global $wp_object_cache;

		$this->data['has_object_cache']     = (bool) wp_using_ext_object_cache();
		$this->data['cache_hit_percentage'] = 0;

		if ( is_object( $wp_object_cache ) ) {
			$object_vars = get_object_vars( $wp_object_cache );

			if ( array_key_exists( 'cache_hits', $object_vars ) ) {
				$this->data['stats']['cache_hits'] = (int) $wp_object_cache->cache_hits;
			}

			if ( array_key_exists( 'cache_misses', $object_vars ) ) {
				$this->data['stats']['cache_misses'] = (int) $wp_object_cache->cache_misses;
			}

			if ( method_exists( $wp_object_cache, 'getStats' ) ) {
				$stats = $wp_object_cache->getStats();
			} elseif ( array_key_exists( 'stats', $object_vars ) && is_array( $wp_object_cache->stats ) ) {
				$stats = $wp_object_cache->stats;
			} elseif ( function_exists( 'wp_cache_get_stats' ) ) {
				$stats = wp_cache_get_stats();
			}

			if ( ! empty( $stats ) ) {
				if ( is_array( $stats ) && ! isset( $stats['get_hits'] ) && 1 === count( $stats ) ) {
					$first_server = reset( $stats );
					if ( isset( $first_server['get_hits'] ) ) {
						$stats = $first_server;
					}
				}

				foreach ( $stats as $key => $value ) {
					if ( ! is_scalar( $value ) ) {
						continue;
					}
					$this->data['stats'][ $key ] = $value;
				}
			}

			if ( ! isset( $this->data['stats']['cache_hits'] ) ) {
				if ( isset( $this->data['stats']['get_hits'] ) ) {
					$this->data['stats']['cache_hits'] = (int) $this->data['stats']['get_hits'];
				}
			}

			if ( ! isset( $this->data['stats']['cache_misses'] ) ) {
				if ( isset( $this->data['stats']['get_misses'] ) ) {
					$this->data['stats']['cache_misses'] = (int) $this->data['stats']['get_misses'];
				}
			}
		}

		if ( ! empty( $this->data['stats']['cache_hits'] ) ) {
			$total = $this->data['stats']['cache_hits'];

			if ( ! empty( $this->data['stats']['cache_misses'] ) ) {
				$total += $this->data['stats']['cache_misses'];
			}

			$this->data['cache_hit_percentage'] = ( 100 / $total ) * $this->data['stats']['cache_hits'];
		}

		$this->data['display_hit_rate_warning'] = ( 100 === $this->data['cache_hit_percentage'] );

		if ( function_exists( 'extension_loaded' ) ) {
			$this->data['object_cache_extensions'] = array_map( 'extension_loaded', array(
				'APCu'         => 'APCu',
				'Memcache'     => 'Memcache',
				'Memcached'    => 'Memcached',
				'Redis'        => 'Redis',
			) );
			$this->data['opcode_cache_extensions'] = array_map( 'extension_loaded', array(
				'APC'          => 'APC',
				'Zend OPcache' => 'Zend OPcache',
			) );
		} else {
			$this->data['object_cache_extensions'] = array();
			$this->data['opcode_cache_extensions'] = array();
		}

		$this->data['has_opcode_cache'] = array_filter( $this->data['opcode_cache_extensions'] ) ? true : false;
	}

}

function register_qm_collector_cache( array $collectors, QueryMonitor $qm ) {
	$collectors['cache'] = new QM_Collector_Cache();
	return $collectors;
}

add_filter( 'qm/collectors', 'register_qm_collector_cache', 20, 2 );
