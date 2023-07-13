<?php declare(strict_types = 1);
/**
 * Object cache collector.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @extends QM_DataCollector<QM_Data_Cache>
 */
class QM_Collector_Cache extends QM_DataCollector {

	public $id = 'cache';

	public function get_storage(): QM_Data {
		return new QM_Data_Cache();
	}

	/**
	 * @return void
	 */
	public function process() {
		global $wp_object_cache;

		$this->data->has_object_cache = (bool) wp_using_ext_object_cache();
		$this->data->cache_hit_percentage = 0;

		if ( is_object( $wp_object_cache ) ) {
			$object_vars = get_object_vars( $wp_object_cache );

			if ( array_key_exists( 'cache_hits', $object_vars ) ) {
				$this->data->stats['cache_hits'] = (int) $object_vars['cache_hits'];
			}

			if ( array_key_exists( 'cache_misses', $object_vars ) ) {
				$this->data->stats['cache_misses'] = (int) $object_vars['cache_misses'];
			}

			$stats = array();

			if ( method_exists( $wp_object_cache, 'getStats' ) ) {
				$stats = $wp_object_cache->getStats();
			} elseif ( array_key_exists( 'stats', $object_vars ) && is_array( $object_vars['stats'] ) ) {
				$stats = $object_vars['stats'];
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
					if ( ! is_string( $key ) ) {
						continue;
					}
					$this->data->stats[ $key ] = $value;
				}
			}

			if ( ! isset( $this->data->stats['cache_hits'] ) ) {
				if ( isset( $this->data->stats['get_hits'] ) ) {
					$this->data->stats['cache_hits'] = (int) $this->data->stats['get_hits'];
				}
			}

			if ( ! isset( $this->data->stats['cache_misses'] ) ) {
				if ( isset( $this->data->stats['get_misses'] ) ) {
					$this->data->stats['cache_misses'] = (int) $this->data->stats['get_misses'];
				}
			}
		}

		if ( ! empty( $this->data->stats['cache_hits'] ) ) {
			$total = $this->data->stats['cache_hits'];

			if ( ! empty( $this->data->stats['cache_misses'] ) ) {
				$total += $this->data->stats['cache_misses'];
			}

			$this->data->cache_hit_percentage = ( 100 / $total ) * $this->data->stats['cache_hits'];
		}

		$this->data->display_hit_rate_warning = ( 100 === $this->data->cache_hit_percentage );

		if ( function_exists( 'extension_loaded' ) ) {
			$this->data->object_cache_extensions = array_map( 'extension_loaded', array(
				'Afterburner' => 'afterburner',
				'APCu' => 'apcu',
				'Redis' => 'redis',
				'Relay' => 'relay',
				'Memcache' => 'memcache',
				'Memcached' => 'memcached',
			) );
			$this->data->opcode_cache_extensions = array_map( 'extension_loaded', array(
				'APC' => 'APC',
				'Zend OPcache' => 'Zend OPcache',
			) );
		} else {
			$this->data->object_cache_extensions = array();
			$this->data->opcode_cache_extensions = array();
		}

		$this->data->has_opcode_cache = array_filter( $this->data->opcode_cache_extensions ) ? true : false;
	}

}

/**
 * @param array<string, QM_Collector> $collectors
 * @param QueryMonitor $qm
 * @return array<string, QM_Collector>
 */
function register_qm_collector_cache( array $collectors, QueryMonitor $qm ) {
	$collectors['cache'] = new QM_Collector_Cache();
	return $collectors;
}

add_filter( 'qm/collectors', 'register_qm_collector_cache', 20, 2 );
