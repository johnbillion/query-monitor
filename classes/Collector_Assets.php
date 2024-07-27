<?php declare(strict_types = 1);
/**
 * Enqueued scripts and styles collector.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @extends QM_DataCollector<QM_Data_Assets>
 */
abstract class QM_Collector_Assets extends QM_DataCollector {

	public function get_storage(): QM_Data {
		return new QM_Data_Assets();
	}

	/**
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		add_action( 'admin_print_footer_scripts', array( $this, 'action_print_footer_scripts' ), 9999 );
		add_action( 'wp_print_footer_scripts', array( $this, 'action_print_footer_scripts' ), 9999 );
		add_action( 'admin_head', array( $this, 'action_head' ), 9999 );
		add_action( 'wp_head', array( $this, 'action_head' ), 9999 );
		add_action( 'login_head', array( $this, 'action_head' ), 9999 );
		add_action( 'embed_head', array( $this, 'action_head' ), 9999 );
	}

	/**
	 * @return void
	 */
	public function tear_down() {
		remove_action( 'admin_print_footer_scripts', array( $this, 'action_print_footer_scripts' ), 9999 );
		remove_action( 'wp_print_footer_scripts', array( $this, 'action_print_footer_scripts' ), 9999 );
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

		/** @var WP_Dependencies $dependencies */
		$dependencies = $GLOBALS[ "wp_{$type}" ];

		$this->data->header = $dependencies->done;
	}

	/**
	 * @return void
	 */
	public function action_print_footer_scripts() {
		if ( empty( $this->data->header ) ) {
			return;
		}

		$type = $this->get_dependency_type();

		/** @var WP_Dependencies $dependencies */
		$dependencies = $GLOBALS[ "wp_{$type}" ];

		$this->data->footer = array_diff( $dependencies->done, $this->data->header );
	}

	/**
	 * @return void
	 */
	public function process() {
		$type = $this->get_dependency_type();
		$modules = null;

		if ( $type === 'scripts' ) {
			$modules = self::get_script_modules();
		}

		if ( empty( $this->data->header ) && empty( $this->data->footer ) && empty( $modules ) ) {
			return;
		}

		$this->data->is_ssl = is_ssl();
		$this->data->full_host = wp_unslash( $_SERVER['HTTP_HOST'] );
		$this->data->host = (string) parse_url( $this->data->full_host, PHP_URL_HOST );
		$this->data->default_version = get_bloginfo( 'version' );
		$this->data->port = (string) parse_url( $this->data->full_host, PHP_URL_PORT );

		$positions = array(
			'missing',
			'broken',
			'header',
			'footer',
		);

		$this->data->counts = array(
			'missing' => 0,
			'broken' => 0,
			'header' => 0,
			'footer' => 0,
			'total' => 0,
		);

		foreach ( array( 'header', 'footer' ) as $position ) {
			if ( empty( $this->data->{$position} ) ) {
				$this->data->{$position} = array();
			}
		}

		/** @var WP_Dependencies $raw */
		$raw = $GLOBALS[ "wp_{$type}" ];
		$broken = array_values( array_diff( $raw->queue, $raw->done ) );
		$missing = array_values( array_diff( $raw->queue, array_keys( $raw->registered ) ) );

		// A broken asset is one which has been deregistered without also being dequeued
		if ( ! empty( $broken ) ) {
			foreach ( $broken as $key => $handle ) {
				/** @var _WP_Dependency|false $item */
				$item = $raw->query( $handle );
				if ( $item ) {
					$broken = array_merge( $broken, self::get_broken_dependencies( $item, $raw ) );
				} else {
					unset( $broken[ $key ] );
					$missing[] = $handle;
				}
			}

			if ( ! empty( $broken ) ) {
				$this->data->broken = array_unique( $broken );
			}
		}

		// A missing asset is one which has been enqueued with dependencies that don't exist
		if ( ! empty( $missing ) ) {
			$this->data->missing = array_unique( $missing );
			foreach ( $this->data->missing as $handle ) {
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
			if ( empty( $this->data->{$position} ) ) {
				continue;
			}

			/** @var string $handle */
			foreach ( $this->data->{$position} as $handle ) {
				/** @var _WP_Dependency|false $dependency */
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

				if ( $source instanceof WP_Error ) {
					$display = $source->get_error_message();
				} else {
					$display = ltrim( preg_replace( '#https?://' . preg_quote( $this->data->full_host, '#' ) . '#', '', remove_query_arg( 'ver', $source ) ), '/' );
				}

				$dependencies = $dependency->deps;

				foreach ( $dependencies as $dep ) {
					if ( ! $raw->query( $dep ) ) {
						// A missing dependency is a dependency on an asset that doesn't exist
						$missing_dependencies[ $dep ] = true;
					}
				}

				$this->data->assets[ $position ][ $handle ] = array(
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

				$this->data->counts[ $position ]++;
				$this->data->counts['total']++;
			}
		}

		unset( $this->data->{$position} );

		if ( is_array( $modules ) ) {
			foreach ( $modules as $id => $module ) {
				list( $host, $source, $local, $port ) = $this->get_module_data( $module['src'] );

				$display = ltrim( preg_replace( '#https?://' . preg_quote( $this->data->full_host, '#' ) . '#', '', remove_query_arg( 'ver', $source ) ), '/' );

				$this->data->assets['modules'][ $id ] = array(
					'host' => $host,
					'port' => $port,
					'source' => $source,
					'local' => $local,
					'ver' => $module['version'] ?: '',
					'warning' => false,
					'display' => $display,
					'dependents' => $module['dependents'],
					'dependencies' => $module['dependencies'],
				);

				$all_dependencies = array_merge( $all_dependencies, $module['dependencies'] );
				$all_dependents = array_merge( $all_dependents, $module['dependents'] );
			}
		}

		$all_dependencies = array_unique( $all_dependencies );
		sort( $all_dependencies );
		$this->data->dependencies = $all_dependencies;

		$all_dependents = array_unique( $all_dependents );
		sort( $all_dependents );
		$this->data->dependents = $all_dependents;

		$this->data->missing_dependencies = $missing_dependencies;
	}

	/**
	 * Undocumented function
	 *
	 * @return array<string, array>|null
	 * @phpstan-return array<string, array{
	 *   id: string,
	 *   src: string,
	 *   version: string|false|null,
	 *   dependencies: list<string>,
	 *   dependents: list<string>,
	 * }>|null
	 */
	protected static function get_script_modules(): ?array {
		// WP 6.5
		if ( ! function_exists( 'wp_script_modules' ) ) {
			return null;
		}

		$modules = wp_script_modules();

		if ( ! ( $modules instanceof \WP_Script_Modules ) ) {
			return null;
		}

		$reflector = new ReflectionClass( $modules );

		$get_marked_for_enqueue = $reflector->getMethod( 'get_marked_for_enqueue' );
		$get_marked_for_enqueue->setAccessible( true );

		$get_dependencies = $reflector->getMethod( 'get_dependencies' );
		$get_dependencies->setAccessible( true );

		$get_src = $reflector->getMethod( 'get_src' );
		$get_src->setAccessible( true );

		/**
		 * @var array<string, array<string, mixed>> $enqueued
		 * @phpstan-var array<string, array{
		 *   src: string,
		 *   version: string|false|null,
		 *   enqueue: bool,
		 *   dependencies: list<array{
		 *     id: string,
		 *     import: 'static'|'dynamic',
		 *   }>,
		 * }> $enqueued
		 */
		$enqueued = $get_marked_for_enqueue->invoke( $modules );

		/**
		 * @var array<string, array<string, mixed>> $deps
		 * @phpstan-var array<string, array{
		 *   src: string,
		 *   version: string|false|null,
		 *   enqueue: bool,
		 *   dependencies: list<array{
		 *     id: string,
		 *     import: 'static'|'dynamic',
		 *   }>,
		 * }> $deps
		 */
		$deps = $get_dependencies->invoke( $modules, array_keys( $enqueued ) );

		$all_modules = array_merge(
			$enqueued,
			$deps
		);

		/**
		 * @var array<string, array<string, mixed>> $sources
		 * @phpstan-var array<string, array{
		 *   id: string,
		 *   src: string,
		 *   version: string|false|null,
		 *   dependencies: list<string>,
		 *   dependents: list<string>,
		 * }> $sources
		 */
		$sources = array();

		foreach ( $all_modules as $id => $module ) {
			/** @var string $src */
			$src = $get_src->invoke( $modules, $id );

			/**
			 * @var array<string, array<string, mixed>> $script_dependencies
			 */
			$script_dependencies = $get_dependencies->invoke( $modules, array( $id ) );
			$dependencies = array_keys( $script_dependencies );
			$dependents = array();

			foreach ( $all_modules as $dep_id => $dep ) {
				foreach ( $dep['dependencies'] as $dependency ) {
					if ( $dependency['id'] === $id ) {
						$dependents[] = $dep_id;
					}
				}
			}

			$sources[ $id ] = array(
				'id' => $id,
				'src' => $src,
				'version' => $module['version'],
				'dependencies' => $dependencies,
				'dependents' => $dependents,
			);
		}

		// @todo check isPrivate before changing visibility back
		$get_marked_for_enqueue->setAccessible( false );
		$get_dependencies->setAccessible( false );
		$get_src->setAccessible( false );

		return $sources;
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
	 * @return mixed[]
	 * @phpstan-return array{
	 *   0: string,
	 *   1: string|WP_Error,
	 *   2: bool,
	 *   3: string,
	 * }
	 */
	public function get_dependency_data( _WP_Dependency $dependency ) {
		/** @var QM_Data_Assets */
		$data = $this->get_data();
		$loader = rtrim( $this->get_dependency_type(), 's' );
		$src = $dependency->src;
		$host = '';
		$full_host = '';
		$scheme = '';
		$port = '';

		if ( null === $dependency->ver ) {
			$ver = '';
		} else {
			$ver = $dependency->ver ?: $this->data->default_version;
		}

		if ( ! empty( $src ) && ! empty( $ver ) ) {
			$src = add_query_arg( 'ver', $ver, $src );
		}

		/** This filter is documented in wp-includes/class.wp-scripts.php */
		$source = apply_filters( "{$loader}_loader_src", $src, $dependency->handle );

		if ( is_string( $source ) ) {
			$host = (string) parse_url( $source, PHP_URL_HOST );
			$scheme = (string) parse_url( $source, PHP_URL_SCHEME );
			$port = (string) parse_url( $source, PHP_URL_PORT );
			$full_host = $host;

			if ( ! empty( $port ) ) {
				$full_host .= ':' . $port;
			}
		}

		if ( empty( $host ) ) {
			$full_host = $data->full_host;
			$host = $data->host;
			$port = $data->port;
		}

		if ( $scheme && $data->is_ssl && ( 'https' !== $scheme ) && ( 'localhost' !== $host ) ) {
			$source = new WP_Error( 'qm_insecure_content', __( 'Insecure content', 'query-monitor' ), array(
				'src' => $source,
			) );
		}

		if ( $source instanceof WP_Error ) {
			$error_data = $source->get_error_data();
			if ( $error_data && isset( $error_data['src'] ) ) {
				$host = (string) parse_url( $error_data['src'], PHP_URL_HOST );
			}
		} elseif ( empty( $source ) ) {
			$source = '';
			$host = '';
		}

		$local = ( $data->full_host === $full_host );

		return array( $host, $source, $local, $port );
	}

	/**
	 * @param string $src
	 * @return mixed[]
	 * @phpstan-return array{
	 *   0: string,
	 *   1: string,
	 *   2: bool,
	 *   3: string,
	 * }
	 */
	protected function get_module_data( string $src ): array {
		/** @var QM_Data_Assets */
		$data = $this->get_data();

		$host = (string) parse_url( $src, PHP_URL_HOST );
		$port = (string) parse_url( $src, PHP_URL_PORT );
		$full_host = $host;

		if ( ! empty( $port ) ) {
			$full_host .= ':' . $port;
		}

		if ( empty( $host ) ) {
			$full_host = $data->full_host;
			$host = $data->host;
			$port = $data->port;
		}

		$source = $src;
		$local = ( $data->full_host === $full_host );

		return array( $host, $source, $local, $port );
	}
}
