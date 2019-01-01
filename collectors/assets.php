<?php
/**
 * Enqueued scripts and styles collector.
 *
 * @package query-monitor
 */

class QM_Collector_Assets extends QM_Collector {

	public $id = 'assets';

	public function __construct() {
		parent::__construct();
		add_action( 'admin_print_footer_scripts', array( $this, 'action_print_footer_scripts' ) );
		add_action( 'wp_print_footer_scripts',    array( $this, 'action_print_footer_scripts' ) );
		add_action( 'admin_head',                 array( $this, 'action_head' ), 999 );
		add_action( 'wp_head',                    array( $this, 'action_head' ), 999 );
		add_action( 'login_head',                 array( $this, 'action_head' ), 999 );
		add_action( 'embed_head',                 array( $this, 'action_head' ), 999 );
	}

	public function action_head() {
		global $wp_scripts, $wp_styles;

		$this->data['header']['styles']  = $wp_styles->done;
		$this->data['header']['scripts'] = $wp_scripts->done;

	}

	public function action_print_footer_scripts() {
		global $wp_scripts, $wp_styles;

		if ( empty( $this->data['header'] ) ) {
			return;
		}

		// @TODO remove the need for these raw scripts & styles to be collected
		$this->data['raw']['scripts'] = $wp_scripts;
		$this->data['raw']['styles']  = $wp_styles;

		$this->data['footer']['scripts'] = array_diff( $wp_scripts->done, $this->data['header']['scripts'] );
		$this->data['footer']['styles']  = array_diff( $wp_styles->done, $this->data['header']['styles'] );

	}

	public function process() {
		if ( ! isset( $this->data['raw'] ) ) {
			return;
		}

		$this->data['is_ssl'] = is_ssl();
		$this->data['host']   = wp_unslash( $_SERVER['HTTP_HOST'] );

		$positions = array(
			'missing',
			'broken',
			'header',
			'footer',
		);

		foreach ( array( 'scripts', 'styles' ) as $type ) {
			foreach ( array( 'header', 'footer' ) as $position ) {
				if ( empty( $this->data[ $position ][ $type ] ) ) {
					$this->data[ $position ][ $type ] = array();
				}
			}
			$raw     = $this->data['raw'][ $type ];
			$broken  = array_values( array_diff( $raw->queue, $raw->done ) );
			$missing = array_values( array_diff( $raw->queue, array_keys( $raw->registered ) ) );

			// A broken asset is one which has been deregistered without also being dequeued
			if ( ! empty( $broken ) ) {
				foreach ( $broken as $key => $handle ) {
					$item = $raw->query( $handle );
					if ( $item ) {
						$broken = array_merge( $broken, self::get_broken_dependencies( $item, $raw ) );
					} else {
						unset( $broken[ $key ] );
						$missing[] = $handle;
					}
				}

				if ( ! empty( $broken ) ) {
					$this->data['broken'][ $type ] = array_unique( $broken );
				}
			}

			// A missing asset is one which has been enqueued with dependencies that don't exist
			if ( ! empty( $missing ) ) {
				$this->data['missing'][ $type ] = array_unique( $missing );
				foreach ( $this->data['missing'][ $type ] as $handle ) {
					$raw->add( $handle, false );
					$key = array_search( $handle, $raw->done, true );
					if ( false !== $key ) {
						unset( $raw->done[ $key ] );
					}
				}
			}

			$all_dependencies = array();
			$all_dependents   = array();

			foreach ( $positions as $position ) {
				if ( ! empty( $this->data[ $position ][ $type ] ) ) {
					$handles = $this->data[ $position ][ $type ];

					foreach ( $handles as $handle ) {
						$dependency = $this->data['raw'][ $type ]->query( $handle );

						if ( ! $dependency ) {
							continue;
						}

						$all_dependencies = array_merge( $all_dependencies, $dependency->deps );

						$dependents     = $this->get_dependents( $dependency, $this->data['raw'][ $type ] );
						$all_dependents = array_merge( $all_dependents, $dependents );
					}
				}
			}
			$all_dependencies = array_unique( $all_dependencies );
			sort( $all_dependencies );
			$this->data['dependencies'][ $type ] = $all_dependencies;

			$all_dependents = array_unique( $all_dependents );
			sort( $all_dependents );
			$this->data['dependents'][ $type ] = $all_dependents;
		}
	}

	protected static function get_broken_dependencies( _WP_Dependency $item, WP_Dependencies $dependencies ) {
		$broken = array();

		foreach ( $item->deps as $handle ) {
			$dep = $dependencies->query( $handle );
			if ( $dep ) {
				$broken = array_merge( $broken, self::get_broken_dependencies( $dep, $dependencies ) );
			} else {
				$broken[] = $item->handle;
			}
		}

		return $broken;
	}

	public function get_dependents( _WP_Dependency $dependency, WP_Dependencies $dependencies ) {
		$dependents = array();
		$handles    = array_unique( array_merge( $dependencies->queue, $dependencies->done ) );

		foreach ( $handles as $handle ) {
			$item = $dependencies->query( $handle );
			if ( $item ) {
				if ( in_array( $dependency->handle, $item->deps, true ) ) {
					$dependents[] = $handle;
				}
			}
		}

		sort( $dependents );

		return $dependents;
	}

	public function get_dependency_data( _WP_Dependency $dependency, WP_Dependencies $dependencies, $type ) {
		$data = $this->get_data();

		$loader = rtrim( $type, 's' );
		$src    = $dependency->src;

		if ( ! empty( $src ) && ! empty( $dependency->ver ) ) {
			$src = add_query_arg( 'ver', $dependency->ver, $src );
		}

		/**
		 * Filter the asset loader source.
		 *
		 * The variable {$loader} can be either 'script' or 'style'.
		 *
		 * @since 2.9.0
		 *
		 * @param string $src    Script or style loader source path.
		 * @param string $handle Script or style handle.
		 */
		$source = apply_filters( "{$loader}_loader_src", $src, $dependency->handle );

		$host   = (string) wp_parse_url( $source, PHP_URL_HOST );
		$scheme = (string) wp_parse_url( $source, PHP_URL_SCHEME );
		$http_host = $data['host'];

		if ( empty( $host ) && ! empty( $http_host ) ) {
			$host = $http_host;
		}

		$insecure = ( $scheme && $data['is_ssl'] && ( 'https' !== $scheme ) );

		if ( $insecure ) {
			$source = new WP_Error( 'insecure_content', __( 'Insecure content', 'query-monitor' ), array(
				'src' => $source,
			) );
		}

		if ( is_wp_error( $source ) ) {
			$src        = $source->get_error_message();
			$error_data = $source->get_error_data();
			if ( $error_data && isset( $error_data['src'] ) ) {
				$host = (string) wp_parse_url( $error_data['src'], PHP_URL_HOST );
			}
		} elseif ( empty( $source ) ) {
			$src  = '';
			$host = '';
		} else {
			$src = $source;
		}

		$local = ( $http_host === $host );

		return array( $src, $host, $source, $local );
	}

	public function name() {
		return __( 'Scripts & Styles', 'query-monitor' );
	}

}

function register_qm_collector_assets( array $collectors, QueryMonitor $qm ) {
	$collectors['assets'] = new QM_Collector_Assets();
	return $collectors;
}

add_filter( 'qm/collectors', 'register_qm_collector_assets', 10, 2 );
