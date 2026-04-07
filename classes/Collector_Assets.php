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
 * @phpstan-type WPScriptModule array{
 *   src: string,
 *   version: string|false|null,
 *   enqueue: bool,
 *   dependencies: list<array{
 *     id: string,
 *     import: 'static'|'dynamic',
 *   }>,
 * }
 * @phpstan-type QMScriptModule array{
 *   id: string,
 *   src: string,
 *   version: string|false|null,
 *   dependencies: list<string>,
 *   dependents: list<string>,
 * }
 * @extends QM_DataCollector<QM_Data_Assets>
 */
abstract class QM_Collector_Assets extends QM_DataCollector {
	/**
	 * @var array<int, string>
	 */
	protected $header = array();

	/**
	 * @var array<int, string>
	 */
	protected $footer = array();

	/**
	 * @var list<string>
	 */
	protected $broken = array();

	/**
	 * @var list<string>
	 */
	protected $missing = array();

	/**
	 * @var string
	 */
	protected $default_version = '';

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

		$this->header = $dependencies->done;
	}

	/**
	 * @return void
	 */
	public function action_print_footer_scripts() {
		if ( empty( $this->header ) ) {
			return;
		}

		$type = $this->get_dependency_type();

		/** @var WP_Dependencies $dependencies */
		$dependencies = $GLOBALS[ "wp_{$type}" ];

		$this->footer = array_diff( $dependencies->done, $this->header );
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

		if ( empty( $this->header ) && empty( $this->footer ) && empty( $modules ) ) {
			return;
		}

		$this->default_version = get_bloginfo( 'version' );

		$url = new QM_Data_URL();
		$url->origin = self::get_origin();
		$url->scheme = is_ssl() ? 'https' : 'http';
		$url->hostname = (string) parse_url( $url->origin, PHP_URL_HOST );
		$port = (string) parse_url( $url->origin, PHP_URL_PORT );
		$url->host = $port ? "{$url->hostname}:{$port}" : $url->hostname;
		$url->insecure = ( 'https' !== $url->scheme ) && ( 'localhost' !== $url->hostname );
		$url->local = true;
		$url->absolute = $url->origin;
		$this->data->url = $url;

		$positions = array(
			'missing',
			'broken',
			'header',
			'footer',
		);

		$type = $this->get_dependency_type();

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
				$this->broken = array_values( array_unique( $broken ) );
			}
		}

		// A missing asset is one which has been enqueued with dependencies that don't exist
		if ( ! empty( $missing ) ) {
			$this->missing = array_values( array_unique( $missing ) );
			foreach ( $this->missing as $handle ) {
				$raw->add( $handle, false );
				$key = array_search( $handle, $raw->done, true );
				if ( false !== $key ) {
					unset( $raw->done[ $key ] );
				}
			}
		}

		$asset_data = array();
		$missing_dependencies = array();

		if ( is_array( $modules ) ) {
			foreach ( $modules as $id => $module ) {
				$url = $this->hyper_determine_the_proto_characteristics_of_a_pseudo_url_from_space( $module['src'] );

				$position = 'modules';

				$asset_data[] = array(
					'handle' => $id,
					'position' => $position,
					'url' => $url,
					'source' => $module['src'],
					'ver' => $module['version'] ?: '',
					'warning' => false,
					'dependents' => $module['dependents'],
					'dependencies' => $module['dependencies'],
				);

				$this->log_type( $position );
			}
		}

		foreach ( $positions as $position ) {
			if ( empty( $this->{$position} ) ) {
				continue;
			}

			/** @var string $handle */
			foreach ( $this->{$position} as $handle ) {
				/** @var _WP_Dependency|false $dependency */
				$dependency = $raw->query( $handle );

				if ( ! $dependency ) {
					continue;
				}

				$dependents = $this->get_dependents( $dependency, $raw );

				list( $url, $source ) = $this->get_dependency_data( $dependency );

				// Widen this type because plugins can set ver to boolean true.
				/** @var string|bool|null $dependency_ver */
				$dependency_ver = $dependency->ver;

				if ( empty( $dependency_ver ) || $dependency_ver === true ) {
					$ver = '';
				} else {
					$ver = $dependency_ver;
				}

				$warning = ! in_array( $handle, $raw->done, true );

				$dependencies = array_values( $dependency->deps );

				foreach ( $dependencies as $dep ) {
					if ( ! $raw->query( $dep ) ) {
						// A missing dependency is a dependency on an asset that doesn't exist
						$missing_dependencies[ $dep ] = true;
					}
				}

				$asset_data[] = array(
					'handle' => $handle,
					'position' => $position,
					'url' => $url,
					'source' => $source,
					'ver' => $ver,
					'warning' => $warning,
					'dependents' => $dependents,
					'dependencies' => $dependencies,
				);

				$this->log_type( $position );
			}
		}

		$this->data->assets = $asset_data;
		$this->data->missing_dependencies = $missing_dependencies;
	}

	/**
	 * Returns the script modules registered in WP_Script_Modules.
	 *
	 * @return array<string, array>|null
	 * @phpstan-return array<string, QMScriptModule>|null
	 */
	protected static function get_script_modules(): ?array {
		// WP 6.5
		if ( ! function_exists( 'wp_script_modules' ) ) {
			return null;
		}

		$modules = wp_script_modules();
		$reflector = new ReflectionClass( $modules );

		$get_marked_for_enqueue = $reflector->getMethod( 'get_marked_for_enqueue' );
		$get_dependencies = $reflector->getMethod( 'get_dependencies' );
		$get_src = $reflector->getMethod( 'get_src' );

		if ( \PHP_VERSION_ID < 80100 ) {
			$get_marked_for_enqueue->setAccessible( true );
			$get_dependencies->setAccessible( true );
			$get_src->setAccessible( true );
		}

		/**
		 * Script modules marked for enqueue, keyed by script module ID.
		 *
		 * @var array<string, array<string, mixed>> $enqueued
		 * @phpstan-var array<string, WPScriptModule> $enqueued
		 */
		$enqueued = $get_marked_for_enqueue->invoke( $modules );

		$deps = self::get_module_dependencies( $modules, $get_dependencies, array_keys( $enqueued ) );

		$all_modules = array_merge(
			$enqueued,
			$deps
		);

		/**
		 * @var array<string, array<string, array<mixed>>> $sources
		 * @phpstan-var array<string, QMScriptModule> $sources
		 */
		$sources = array();

		foreach ( $all_modules as $id => $module ) {
			/** @var string $src */
			$src = $get_src->invoke( $modules, $id );

			$dependencies = array_values( wp_list_pluck( $all_modules[ $id ]['dependencies'], 'id' ) );
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
				'version' => $module['version'] ?? '',
				'dependencies' => $dependencies,
				'dependents' => $dependents,
			);
		}

		if ( \PHP_VERSION_ID < 80100 ) {
			$get_marked_for_enqueue->setAccessible( false );
			$get_dependencies->setAccessible( false );
			$get_src->setAccessible( false );
		}

		return $sources;
	}

	/**
	 * Retrieves all the dependencies for the given script module identifiers.
	 *
	 * @param list<string> $ids
	 *
	 * @return array<string, array<string, mixed>> $deps
	 * @phpstan-return array<string, WPScriptModule> $deps
	 */
	private static function get_module_dependencies(
		WP_Script_Modules $modules,
		ReflectionMethod $get_dependencies,
		array $ids
	): array {
		return $get_dependencies->invoke( $modules, $ids );
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
	 *   0: QM_Data_URL,
	 *   1: string|WP_Error,
	 * }
	 */
	public function get_dependency_data( _WP_Dependency $dependency ) {
		$loader = rtrim( $this->get_dependency_type(), 's' );
		$src = $dependency->src;
		$url = new QM_Data_URL();

		if ( null === $dependency->ver ) {
			$ver = '';
		} else {
			$ver = $dependency->ver ?: $this->default_version;
		}

		if ( ! empty( $src ) && ! empty( $ver ) ) {
			$src = add_query_arg( 'ver', $ver, $src );
		}

		/** This filter is documented in wp-includes/class.wp-scripts.php */
		$source = apply_filters( "{$loader}_loader_src", $src, $dependency->handle );

		if ( is_string( $source ) ) {
			// @TODO this still needs to normalise to absolute URL.
			$url = $this->hyper_determine_the_proto_characteristics_of_a_pseudo_url_from_space( $source );
		}

		if ( $url->insecure ) {
			$source = new WP_Error( 'qm_insecure_content', __( 'Insecure content', 'query-monitor' ), array(
				'src' => $source,
			) );
		}

		if ( $source instanceof WP_Error ) {
			$error_data = $source->get_error_data();
			if ( $error_data && isset( $error_data['src'] ) ) {
				$url = $this->hyper_determine_the_proto_characteristics_of_a_pseudo_url_from_space( $error_data['src'] );
			}
		} elseif ( empty( $source ) ) {
			$source = '';
		}

		return array( $url, $source );
	}

	/**
	 * Accepts an absolute URL, relative URL, or root-relative URL and returns its normalised information.
	 *
	 * This is primarily so relative URLs are correctly handled in the same way as local absolute URLs.
	 */
	protected function hyper_determine_the_proto_characteristics_of_a_pseudo_url_from_space( string $url ): QM_Data_URL {
		$parsed_url = new QM_Data_URL();
		$parsed_url->hostname = (string) parse_url( $url, PHP_URL_HOST );
		$parsed_url->scheme = (string) parse_url( $url, PHP_URL_SCHEME );
		$port = (string) parse_url( $url, PHP_URL_PORT );

		if ( empty( $parsed_url->hostname ) ) {
			// Relative URL - prepend the origin
			$parsed_url->origin = $this->data->url->origin;
			$parsed_url->hostname = $this->data->url->hostname;
			$parsed_url->scheme = $this->data->url->scheme;
			$parsed_url->absolute = $this->data->url->origin . $url;
			$port = (string) parse_url( $this->data->url->origin, PHP_URL_PORT );
		} else {
			// Absolute URL
			$parsed_url->origin = $parsed_url->scheme . '://' . $parsed_url->hostname . ( $port ? ':' . $port : '' );
			$parsed_url->absolute = $url;
		}

		$parsed_url->insecure = ( 'https' !== $parsed_url->scheme ) && ( 'localhost' !== $parsed_url->hostname ) && ( ! $this->data->url->insecure );
		$parsed_url->host = $port ? "{$parsed_url->hostname}:{$port}" : $parsed_url->hostname;
		$parsed_url->local = ( $this->data->url->origin === $parsed_url->origin );

		return $parsed_url;
	}
}
