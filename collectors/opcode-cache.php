<?php declare(strict_types = 1);
/**
 * Opcode cache collector.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @extends QM_DataCollector<QM_Data_Cache>
 */
class QM_Collector_Opcode_Cache extends QM_DataCollector {

	public $id = 'opcode-cache';

	public function get_storage(): QM_Data {
		return new QM_Data_Cache();
	}

	/**
	 * @return void
	 */
	public function process() {
		$this->data->has = false;
		$this->data->cache_hit_percentage = 0;
		$this->data->cache_extensions = array();

		if ( function_exists( 'extension_loaded' ) ) {
			$this->data->cache_extensions = array_map( 'extension_loaded', array(
				'APC' => 'APC',
				'Zend OPcache' => 'Zend OPcache',
			) );
		}

		if( isset( $this->data->cache_extensions['APC'] ) && $this->data->cache_extensions['APC'] ) {
			$enabled = ini_get( 'apc.enabled' );
			$enabled = $enabled === '1' || $enabled === 'On';

			if ( function_exists( 'apc_cache_info' ) ) {
				$stats = apc_cache_info();
				if ( is_array( $stats ) ) {
					$this->data->stats = $stats;
					if ( isset( $this->data->stats['num_hits'] ) ) {
						$this->data->stats['cache_hits'] = (int) $this->data->stats['num_hits'];
					}
					if ( isset( $stats['num_misses'] ) ) {
						$this->data->stats['cache_misses'] = (int) $this->data->stats['num_misses'];
					}
				}
				
			}
		} else {
			$enabled = ini_get( 'opcache.enable' );
			$enabled = $enabled === '1' || $enabled === 'On';

			$restrict_api = ini_get( 'opcache.restrict_api' );
			$api_available = true;
			if ( ! empty( $restrict_api ) ) {
				$restrict_api = trailingslashit( $restrict_api );
				if ( strpos( __DIR__, $restrict_api ) !== 0 ) {
					$api_available = false;
				}
			}
			
			if( $enabled && $api_available && function_exists( 'opcache_get_status' ) ) {
				$full_status = opcache_get_status( true );

				if ( is_array( $full_status ) ) {
					if ( isset( $full_status['opcache_statistics'] ) ) {
						$this->data->stats = $full_status['opcache_statistics'];

						// Opcache stats are reflecting the hits/misses since the web server started.
						// We would need to correlate with the included files to get a more accurate hit/miss count.
						if ( function_exists( 'get_included_files' ) ) {
							$files_included = get_included_files();
							$files_hit = count( array_intersect_key( $full_status['scripts'], array_flip( $files_included ) ) );
							$files_missed = count( $files_included ) - $files_hit;

							$this->data->stats['cache_hits'] = $files_hit;
							$this->data->stats['cache_misses'] = $files_missed;
						}
					}

					$memory_used  = -1;
					$memory_limit = QM_Util::convert_hr_to_bytes( ini_get( 'opcache.memory_consumption' ) . 'M' ); // memory_consumption is in MB
					if ( isset( $full_status['memory_usage'] ) ) {
						$memory_used = (int) $full_status['memory_usage']['used_memory'] + (int) $full_status['memory_usage']['wasted_memory'];
						$memory_free = (int) $full_status['memory_usage']['free_memory'];
						$memory_limit = $memory_used + $memory_free;
					}

					$this->data->usage_meters['opcode-memory'] = array(
						'type' => 'bytes',
						'used' => $memory_used,
						'limit' => $memory_limit,
						'label' => __( 'Memory', 'query-monitor' ),
					);

					if ( isset( $full_status['interned_strings_usage'] ) ) {
						$this->data->usage_meters['strings-memory'] = array(
							'type' => 'bytes',
							'used' => $full_status['interned_strings_usage']['used_memory'],
							'limit' => $full_status['interned_strings_usage']['buffer_size'],
							'label' => __( 'Interned strings', 'query-monitor' ),
						);
					}

					$max_files = ini_get( 'opcache.max_accelerated_files' );
					// According to the documentation: The actual value used will be the first number in the set of prime numbers below which is greater than the specified value.
					// https://www.php.net/manual/en/opcache.configuration.php#ini.opcache.max-accelerated-files
					$real_max_files = array( 223, 463, 983, 1979, 3907, 7963, 16229, 32531, 65407, 130987, 262237, 524521, 1048793 );
					foreach( $real_max_files as $v ) {
						if( $max_files <= $v ) {
							$real_max_files = $v;
							break;
						}
					}
					$cached_files = isset( $full_status['opcache_statistics']['num_cached_scripts'] ) ? (int) $full_status['opcache_statistics']['num_cached_scripts'] : 0;
					
					if( $cached_files ) {
						$this->data->usage_meters['cached-scripts'] = array(
							'type' => 'count',
							'used' => $cached_files,
							'limit' => $real_max_files,
							'label' => __( 'Cached scripts', 'query-monitor' ),
						);
					}
				}
			}
		}

		$this->data->has = $enabled;

		if ( ! empty( $this->data->stats['cache_hits'] ) ) {
			$total = $this->data->stats['cache_hits'];

			if ( ! empty( $this->data->stats['cache_misses'] ) ) {
				$total += $this->data->stats['cache_misses'];
			}

			$this->data->cache_hit_percentage = ( 100 / $total ) * $this->data->stats['cache_hits'];
		}
	}

}

/**
 * @param array<string, QM_Collector> $collectors
 * @param QueryMonitor $qm
 * @return array<string, QM_Collector>
 */
function register_qm_collector_opcode_cache( array $collectors, QueryMonitor $qm ) {
	$collectors['opcode-cache'] = new QM_Collector_Opcode_Cache();
	return $collectors;
}

add_filter( 'qm/collectors', 'register_qm_collector_opcode_cache', 20, 2 );
