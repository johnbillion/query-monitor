<?php
/**
 * Enqueued scripts and styles collector.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class QM_Collector_Assets extends QM_Collector {

	/**
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		add_action( 'admin_print_footer_scripts', array( $this, 'action_print_footer_scripts' ) );
		add_action( 'wp_print_footer_scripts', array( $this, 'action_print_footer_scripts' ) );
		add_action( 'admin_head', array( $this, 'action_head' ), 9999 );
		add_action( 'wp_head', array( $this, 'action_head' ), 9999 );
		add_action( 'login_head', array( $this, 'action_head' ), 9999 );
		add_action( 'embed_head', array( $this, 'action_head' ), 9999 );
	}

	/**
	 * @return void
	 */
	public function tear_down() {
		remove_action( 'admin_print_footer_scripts', array( $this, 'action_print_footer_scripts' ) );
		remove_action( 'wp_print_footer_scripts', array( $this, 'action_print_footer_scripts' ) );
		remove_action( 'admin_head', array( $this, 'action_head' ), 9999 );
		remove_action( 'wp_head', array( $this, 'action_head' ), 9999 );
		remove_action( 'login_head', array( $this, 'action_head' ), 9999 );
		remove_action( 'embed_head', array( $this, 'action_head' ), 9999 );

		parent::tear_down();
	}

	/**
	 * @return string
	 */
	abstract public function get_dependency_type();

	/**
	 * @return void
	 */
	public function action_head() {
		$type = $this->get_dependency_type();

		$this->data['header'] = $GLOBALS[ "wp_{$type}" ]->done;
	}

	/**
	 * @return void
	 */
	public function action_print_footer_scripts() {
		if ( empty( $this->data['header'] ) ) {
			return;
		}

		$type = $this->get_dependency_type();

		$this->data['footer'] = array_diff( $GLOBALS[ "wp_{$type}" ]->done, $this->data['header'] );

	}

	/**
	 * @return void
	 */
	public function process() {
		if ( empty( $this->data['header'] ) && empty( $this->data['footer'] ) ) {
			return;
		}

		$this->data['is_ssl'] = is_ssl();
		$this->data['host'] = wp_unslash( $_SERVER['HTTP_HOST'] );
		$this->data['default_version'] = get_bloginfo( 'version' );
		$this->data['port'] = (string) parse_url( $this->data['host'], PHP_URL_PORT );

		$home_url = home_url();
		$positions = array(
			'missing',
			'broken',
			'header',
			'footer',
		);

		$this->data['counts'] = array(
			'missing' => 0,
			'broken' => 0,
			'header' => 0,
			'footer' => 0,
			'total' => 0,
		);

		$type = $this->get_dependency_type();

		foreach ( array( 'header', 'footer' ) as $position ) {
			if ( empty( $this->data[ $position ] ) ) {
				$this->data[ $position ] = array();
			}
		}
		$raw = $GLOBALS[ "wp_{$type}" ];
		$broken = array_values( array_diff( $raw->queue, $raw->done ) );
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
				$this->data['broken'] = array_unique( $broken );
			}
		}

		// A missing asset is one which has been enqueued with dependencies that don't exist
		if ( ! empty( $missing ) ) {
			$this->data['missing'] = array_unique( $missing );
			foreach ( $this->data['missing'] as $handle ) {
				$raw->add( $handle, false );
				$key = array_search( $handle, $raw->done, true );
				if ( false !== $key ) {
					unset( $raw->done[ $key ] );
				}
			}
		}

		$all_dependencies = array();
		$all_dependents = array();

		$missing_dependencies = array();

		foreach ( $positions as $position ) {
			if ( empty( $this->data[ $position ] ) ) {
				continue;
			}

			foreach ( $this->data[ $position ] as $handle ) {
				$dependency = $raw->query( $handle );

				if ( ! $dependency ) {
					continue;
				}

				$all_dependencies = array_merge( $all_dependencies, $dependency->deps );
				$dependents = $this->get_dependents( $dependency, $raw );
				$all_dependents = array_merge( $all_dependents, $dependents );

				list( $host, $source, $local, $port ) = $this->get_dependency_data( $dependency );

				if ( empty( $dependency->ver ) ) {
					$ver = '';
				} else {
					$ver = $dependency->ver;
				}

				$warning = ! in_array( $handle, $raw->done, true );

				if ( is_wp_error( $source ) ) {
					$display = $source->get_error_message();
				} else {
					$display = ltrim( str_replace( "{$home_url}/", '/', remove_query_arg( 'ver', $source ) ), '/' );
				}

				$dependencies = $dependency->deps;

				foreach ( $dependencies as & $dep ) {
					if ( ! $raw->query( $dep ) ) {
						// A missing dependency is a dependecy on an asset that doesn't exist
						$missing_dependencies[ $dep ] = true;
					}
				}

				$this->data['assets'][ $position ][ $handle ] = array(
					'host' => $host,
					'port' => $port,
					'source' => $source,
					'local' => $local,
					'ver' => $ver,
					'warning' => $warning,
					'display' => $display,
					'dependents' => $dependents,
					'dependencies' => $dependencies,
				);

				$this->data['counts'][ $position ]++;
				$this->data['counts']['total']++;
			}
		}

		unset( $this->data[ $position ] );

		$all_dependencies = array_unique( $all_dependencies );
		sort( $all_dependencies );
		$this->data['dependencies'] = $all_dependencies;

		$all_dependents = array_unique( $all_dependents );
		sort( $all_dependents );
		$this->data['dependents'] = $all_dependents;

		$this->data['missing_dependencies'] = $missing_dependencies;
	}

	/**
	 * @param _WP_Dependency $item
	 * @param WP_Dependencies $dependencies
	 * @return array<int, string>
	 */
	protected static function get_broken_dependencies( _WP_Dependency $item, WP_Dependencies $dependencies ) {
		$broken = array();

		foreach ( $item->deps as $handle ) {
			$dep = $dependencies->query( $handle );
			if ( $dep instanceof _WP_Dependency ) {
				$broken = array_merge( $broken, self::get_broken_dependencies( $dep, $dependencies ) );
			} else {
				$broken[] = $item->handle;
			}
		}

		return $broken;
	}

	/**
	 * @param _WP_Dependency $dependency
	 * @param WP_Dependencies $dependencies
	 * @return array<int, string>
	 */
	public function get_dependents( _WP_Dependency $dependency, WP_Dependencies $dependencies ) {
		$dependents = array();
		$handles = array_unique( array_merge( $dependencies->queue, $dependencies->done ) );

		foreach ( $handles as $handle ) {
			$item = $dependencies->query( $handle );
			if ( $item instanceof _WP_Dependency ) {
				if ( in_array( $dependency->handle, $item->deps, true ) ) {
					$dependents[] = $handle;
				}
			}
		}

		sort( $dependents );

		return $dependents;
	}

	/**
	 * @param _WP_Dependency $dependency
	 * @return array{
	 *   0: string,
	 *   1: string|WP_Error,
	 *   2: bool,
	 *   3: string,
	 * }
	 */
	public function get_dependency_data( _WP_Dependency $dependency ) {
		$data = $this->get_data();
		$loader = rtrim( $this->get_dependency_type(), 's' );
		$src = $dependency->src;

		if ( null === $dependency->ver ) {
			$ver = '';
		} else {
			$ver = $dependency->ver ? $dependency->ver : $this->data['default_version'];
		}

		if ( ! empty( $src ) && ! empty( $ver ) ) {
			$src = add_query_arg( 'ver', $ver, $src );
		}

		/** This filter is documented in wp-includes/class.wp-scripts.php */
		$source = apply_filters( "{$loader}_loader_src", $src, $dependency->handle );

		$host = (string) parse_url( $source, PHP_URL_HOST );
		$scheme = (string) parse_url( $source, PHP_URL_SCHEME );
		$port = (string) parse_url( $source, PHP_URL_PORT );
		$http_host = $data['host'];
		$http_port = $data['port'];

		if ( empty( $host ) && ! empty( $http_host ) ) {
			$host = $http_host;
			$port = $http_port;
		}

		if ( $scheme && $data['is_ssl'] && ( 'https' !== $scheme ) && ( 'localhost' !== $host ) ) {
			$source = new WP_Error( 'qm_insecure_content', __( 'Insecure content', 'query-monitor' ), array(
				'src' => $source,
			) );
		}

		if ( is_wp_error( $source ) ) {
			$error_data = $source->get_error_data();
			if ( $error_data && isset( $error_data['src'] ) ) {
				$host = (string) parse_url( $error_data['src'], PHP_URL_HOST );
			}
		} elseif ( empty( $source ) ) {
			$source = '';
			$host = '';
		}

		$local = ( $http_host === $host );

		return array( $host, $source, $local, $port );
	}

}
