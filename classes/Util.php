<?php declare(strict_types = 1);
/**
 * General utilities class.
 *
 * @package query-monitor
 */

if ( ! class_exists( 'QM_Util' ) ) {
class QM_Util {

	/**
	 * @var array<string, QM_Component>
	 */
	protected static $file_components = array();

	/**
	 * @var array<string, string|null>
	 */
	protected static $file_dirs = array();

	/**
	 * @var string|null
	 */
	protected static $abspath = null;

	/**
	 * @var string|null
	 */
	protected static $contentpath = null;

	private function __construct() {}

	/**
	 * @param string $size
	 * @return float
	 */
	public static function convert_hr_to_bytes( $size ) {

		# Annoyingly, wp_convert_hr_to_bytes() is defined in a file that's only
		# loaded in the admin area, so we'll use our own version.
		# See also https://core.trac.wordpress.org/ticket/17725

		$bytes = (float) $size;

		if ( $bytes ) {
			$last = strtolower( substr( $size, -1 ) );
			$pos = strpos( ' kmg', $last, 1 );
			if ( $pos ) {
				$bytes *= pow( 1024, $pos );
			}
			$bytes = round( $bytes );
		}

		return $bytes;

	}

	/**
	 * @param string $dir
	 * @param string $path_replace
	 * @return string
	 */
	public static function standard_dir( $dir, $path_replace = null ) {

		$dir = self::normalize_path( $dir );

		if ( is_string( $path_replace ) ) {
			if ( ! self::$abspath ) {
				self::$abspath = self::normalize_path( ABSPATH );
				self::$contentpath = self::normalize_path( dirname( WP_CONTENT_DIR ) . '/' );
			}
			$dir = str_replace( array(
				self::$abspath,
				self::$contentpath,
			), $path_replace, $dir );
		}

		return $dir;

	}

	/**
	 * @param string $path
	 * @return string
	 */
	public static function normalize_path( $path ) {
		if ( function_exists( 'wp_normalize_path' ) ) {
			$path = wp_normalize_path( $path );
		} else {
			$path = str_replace( '\\', '/', $path );
			$path = str_replace( '//', '/', $path );
		}

		return $path;
	}

	/**
	 * @TODO all this logic could move into a QM_Components collection
	 *
	 * @return array<string, string|null>
	 */
	public static function get_file_dirs() {
		if ( empty( self::$file_dirs ) ) {

			/**
			 * Filters the absolute directory paths that correlate to components.
			 *
			 * Note that this filter is applied before QM adds its built-in list of components. This is
			 * so custom registered components take precedence during component detection.
			 *
			 * See also the corresponding filters:
			 *
			 *  - `qm/component_type/{$type}`
			 *  - `qm/component_context/{$type}`
			 *  - `qm/component_name/{$type}`
			 *
			 * @since 3.6.0
			 *
			 * @param array<string, string|null> $dirs Array of absolute directory paths keyed by component identifier.
			 */
			self::$file_dirs = apply_filters( 'qm/component_dirs', self::$file_dirs );

			self::$file_dirs[ QM_Component::TYPE_PLUGIN ] = WP_PLUGIN_DIR;
			self::$file_dirs[ QM_Component::TYPE_MU_VENDOR ] = WPMU_PLUGIN_DIR . '/vendor';
			self::$file_dirs[ QM_Component::TYPE_VIP_SHARED_PLUGIN ] = WPMU_PLUGIN_DIR . '/shared-plugins';
			self::$file_dirs[ QM_Component::TYPE_MU_PLUGIN ] = WPMU_PLUGIN_DIR;
			self::$file_dirs[ QM_Component::TYPE_VIP_PLUGIN ] = get_theme_root() . '/vip/plugins';

			if ( defined( 'WPCOM_VIP_CLIENT_MU_PLUGIN_DIR' ) ) {
				self::$file_dirs[ QM_Component::TYPE_VIP_CLIENT_MU_PLUGIN ] = WPCOM_VIP_CLIENT_MU_PLUGIN_DIR;
			}

			if ( defined( '\Altis\ROOT_DIR' ) ) {
				self::$file_dirs[ QM_Component::TYPE_ALTIS_VENDOR ] = \Altis\ROOT_DIR . '/vendor';
			}

			self::$file_dirs[ QM_Component::TYPE_THEME ] = null;
			self::$file_dirs[ QM_Component::TYPE_STYLESHEET ] = get_stylesheet_directory();
			self::$file_dirs[ QM_Component::TYPE_TEMPLATE ] = get_template_directory();
			self::$file_dirs[ QM_Component::TYPE_OTHER ] = WP_CONTENT_DIR;
			self::$file_dirs[ QM_Component::TYPE_CORE ] = ABSPATH;
			self::$file_dirs[ QM_Component::TYPE_UNKNOWN ] = null;

			foreach ( self::$file_dirs as $type => $dir ) {
				if ( null === $dir ) {
					continue;
				}

				self::$file_dirs[ $type ] = self::standard_dir( $dir );
			}
		}

		return self::$file_dirs;
	}

	/**
	 * Attempts to determine the component responsible for a given file name.
	 *
	 * @param string $file An absolute file path.
	 * @return QM_Component An object representing the component.
	 */
	public static function get_file_component( $file ) {
		$file = self::standard_dir( $file );
		$type = '';

		if ( isset( self::$file_components[ $file ] ) ) {
			return self::$file_components[ $file ];
		}

		foreach ( self::get_file_dirs() as $type => $dir ) {
			// this slash makes paths such as plugins-mu match mu-plugin not plugin
			if ( $dir && ( 0 === strpos( $file, trailingslashit( $dir ) ) ) ) {
				break;
			}
		}

		$context = $type;

		switch ( $type ) {
			case QM_Component::TYPE_ALTIS_VENDOR:
				$plug = str_replace( \Altis\ROOT_DIR . '/vendor/', '', $file );
				$plug = explode( '/', $plug, 3 );
				$plug = $plug[0] . '/' . $plug[1];
				$context = $plug;
				break;
			case QM_Component::TYPE_PLUGIN:
			case QM_Component::TYPE_MU_PLUGIN:
			case QM_Component::TYPE_MU_VENDOR:
				$plug = str_replace( '/vendor/', '/', $file );
				$plug = plugin_basename( $plug );
				if ( strpos( $plug, '/' ) ) {
					$plug = explode( '/', $plug );
					$plug = reset( $plug );
				} else {
					$plug = basename( $plug );
				}
				$context = $plug;
				break;
			case QM_Component::TYPE_VIP_SHARED_PLUGIN:
			case QM_Component::TYPE_VIP_PLUGIN:
			case QM_Component::TYPE_VIP_CLIENT_MU_PLUGIN:
				$plug = str_replace( self::$file_dirs[ $type ], '', $file );
				$plug = trim( $plug, '/' );
				if ( strpos( $plug, '/' ) ) {
					$plug = explode( '/', $plug );
					$plug = reset( $plug );
				} else {
					$plug = basename( $plug );
				}
				$context = $plug;
				break;
			case QM_Component::TYPE_STYLESHEET:
			case QM_Component::TYPE_TEMPLATE:
			case QM_Component::TYPE_CORE:
				// Nothing.
				break;
			case QM_Component::TYPE_OTHER:
				// Anything else that's within the content directory should appear as
				// `wp-content/{dir}` or `wp-content/{file}`
				$name = self::standard_dir( $file );
				$name = str_replace( dirname( self::$file_dirs[ QM_Component::TYPE_OTHER ] ), '', $name );
				$parts = explode( '/', trim( $name, '/' ) );
				$context = $parts[0] . '/' . $parts[1];

				// Now detect specific drop-in plugins:
				if ( ! function_exists( '_get_dropins' ) ) {
					require_once ABSPATH . 'wp-admin/includes/plugin.php';
				}

				/** @var array<int, string> $dropins */
				$dropins = array_keys( _get_dropins() );

				foreach ( $dropins as $dropin ) {
					$dropin_path = WP_CONTENT_DIR . $dropin;

					if ( $file !== $dropin_path ) {
						continue;
					}

					$type = QM_Component::TYPE_DROPIN;
					$context = pathinfo( $dropin, PATHINFO_BASENAME );
				}

				break;
			case QM_Component::TYPE_UNKNOWN:
			default:
				/**
				 * Filters the type of a custom or unknown component.
				 *
				 * The dynamic portion of the hook name, `$type`, refers to the component identifier.
				 *
				 * See also the corresponding filters:
				 *
				 *  - `qm/component_dirs`
				 *  - `qm/component_name/{$type}`
				 *  - `qm/component_context/{$type}`
				 *
				 * @since 3.8.1
				 *
				 * @param string $type    The component type.
				 * @param string $file    The full file path for the file within the component.
				 * @param string $name    The component name.
				 * @param string $context The context for the component.
				 */
				$type = apply_filters( "qm/component_type/{$type}", $type, $file, $file, $context );

				/**
				 * Filters the context for a custom or unknown component. The context is usually a
				 * representation specific to the individual component, for example the file base name.
				 *
				 * The dynamic portion of the hook name, `$type`, refers to the component identifier.
				 *
				 * See also the corresponding filters:
				 *
				 *  - `qm/component_dirs`
				 *  - `qm/component_type/{$type}`
				 *  - `qm/component_name/{$type}`
				 *
				 * @since 3.8.0
				 *
				 * @param string $context The context for the component.
				 * @param string $file    The full file path for the file within the component.
				 * @param string $name    The component name.
				 */
				$context = apply_filters( "qm/component_context/{$type}", $context, $file, $file );
				break;
		}

		self::$file_components[ $file ] = QM_Component::from( $type, $context, $file );

		return self::$file_components[ $file ];
	}

	/**
	 * @param array<string, mixed> $callback
	 * @return array<string, mixed>
	 * @phpstan-return array{
	 *   name?: string,
	 *   file?: string|false,
	 *   line?: string|false,
	 *   error?: WP_Error,
	 *   component?: QM_Component,
	 *   callback_type: string,
	 *   start_line?: int,
	 *   display_file?: string,
	 * }
	 */
	public static function populate_callback( array $callback ) {

		if ( is_string( $callback['function'] ) && ( false !== strpos( $callback['function'], '::' ) ) ) {
			$callback['function'] = explode( '::', $callback['function'] );
		}

		if ( isset( $callback['class'] ) ) {
			$callback['function'] = array(
				$callback['class'],
				$callback['function'],
			);
		}

		try {

			if ( is_array( $callback['function'] ) ) {
				if ( is_object( $callback['function'][0] ) ) {
					$class = get_class( $callback['function'][0] );
					$access = '->';
					$callback['callback_type'] = 'method';
				} else {
					$class = $callback['function'][0];
					$access = '::';
					$callback['callback_type'] = 'static_method';
				}

				$callback['name'] = self::shorten_fqn( $class . $access . $callback['function'][1] ) . '()';
				$ref = new ReflectionMethod( $class, $callback['function'][1] );
			} elseif ( is_object( $callback['function'] ) ) {
				if ( $callback['function'] instanceof Closure ) {
					$ref = new ReflectionFunction( $callback['function'] );
					$filename = $ref->getFileName();

					if ( $filename ) {
						$file = self::standard_dir( $filename, '' );
						if ( 0 === strpos( $file, '/' ) ) {
							$file = basename( $filename );
						}
						$callback['callback_type'] = 'closure';
						$callback['start_line'] = $ref->getStartLine();
						$callback['display_file'] = $file;
					} else {
						$callback['callback_type'] = 'unknown_closure';
					}
				} else {
					// the object should have a __invoke() method
					$class = get_class( $callback['function'] );
					$callback['name'] = self::shorten_fqn( $class ) . '->__invoke()';
					$callback['callback_type'] = 'invokable';
					$ref = new ReflectionMethod( $class, '__invoke' );
				}
			} else {
				$callback['name'] = self::shorten_fqn( $callback['function'] ) . '()';
				$callback['callback_type'] = 'function';
				$ref = new ReflectionFunction( $callback['function'] );
			}

			$callback['file'] = $ref->getFileName();
			$callback['line'] = $ref->getStartLine();

			// Handle legacy create_function() lambdas (PHP 7.4 only, removed in PHP 8.0)
			$name = trim( $ref->getName() );
			if ( 0 === strpos( $name, 'lambda_' ) ) {
				// Just use the lambda name as-is, these are from deprecated create_function()
				$callback['name'] = $name . '()';
				$callback['callback_type'] = 'lambda';
				unset( $callback['file'], $callback['line'] );
			}

			if ( ! empty( $callback['file'] ) ) {
				$callback['component'] = self::get_file_component( $callback['file'] );
			} else {
				$callback['component'] = QM_Component::from( QM_Component::TYPE_PHP, 'php' );
			}
		} catch ( ReflectionException $e ) {

			$callback['error'] = new WP_Error( 'reflection_exception', $e->getMessage() );
			$callback['callback_type'] = 'unknown';

		}

		unset( $callback['function'], $callback['class'] );

		return $callback;

	}

	/**
	 * Generate the translated callback name from callback properties.
	 *
	 * @param array<string, mixed> $callback Callback data array.
	 * @return string Translated callback name.
	 */
	public static function get_callback_name( array $callback ) {
		if ( isset( $callback['name'] ) ) {
			return $callback['name'];
		}

		if ( ! isset( $callback['callback_type'] ) ) {
			return '';
		}

		switch ( $callback['callback_type'] ) {
			case 'closure':
				return sprintf(
					/* translators: A closure is an anonymous PHP function. 1: Line number, 2: File name */
					__( 'Closure on line %1$d of %2$s', 'query-monitor' ),
					$callback['start_line'],
					$callback['display_file']
				);

			case 'unknown_closure':
				/* translators: A closure is an anonymous PHP function */
				return __( 'Unknown closure', 'query-monitor' );

			case 'function':
			case 'method':
			case 'static_method':
			case 'invokable':
			case 'lambda':
			case 'unknown':
			default:
				return $callback['name'] ?? '';
		}
	}

	/**
	 * @return bool
	 */
	public static function is_ajax() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return true;
		}
		return false;
	}

	/**
	 * @return bool
	 */
	public static function is_async() {
		if ( self::is_ajax() ) {
			return true;
		}
		if ( isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && 'xmlhttprequest' === strtolower( $_SERVER['HTTP_X_REQUESTED_WITH'] ) ) { // phpcs:ignore
			return true;
		}
		return false;
	}

	/**
	 * @return WP_Role|false
	 */
	public static function get_admins() {
		if ( is_multisite() ) {
			return false;
		} else {
			return get_role( 'administrator' );
		}
	}

	/**
	 * @return bool
	 */
	public static function is_multi_network() {
		return ( function_exists( 'is_multi_network' ) && is_multi_network() );
	}

	/**
	 * @param int|string $client
	 * @return array<string, int>
	 * @phpstan-return array{
	 *   major: int,
	 *   minor: int,
	 *   patch: int,
	 * }
	 */
	public static function get_client_version( $client ) {

		$client = intval( $client );

		$hello = $client % 10000;

		$major = intval( floor( $client / 10000 ) );
		$minor = intval( floor( $hello / 100 ) );
		$patch = intval( $hello % 100 );

		return compact( 'major', 'minor', 'patch' );

	}

	/**
	 * @param string $sql
	 * @return string
	 */
	public static function get_query_type( $sql ) {
		// Trim leading whitespace and brackets
		$sql = ltrim( $sql, ' \t\n\r\0\x0B(' );

		if ( 0 === strpos( $sql, '/*' ) ) {
			// Strip out leading comments such as `/*NO_SELECT_FOUND_ROWS*/` before calculating the query type
			$sql = preg_replace( '|^/\*[^\*/]+\*/|', '', $sql );
		}

		$words = preg_split( '/\b/', trim( $sql ), 2, PREG_SPLIT_NO_EMPTY );
		$type = 'Unknown';

		if ( is_array( $words ) && isset( $words[0] ) ) {
			$type = strtoupper( $words[0] );
		}

		return $type;
	}

	/**
	 * @param mixed $value
	 * @return string|float|int
	 */
	public static function display_variable( $value ) {
		if ( is_string( $value ) ) {
			return $value;
		} elseif ( $value === null ) {
			return 'null';
		} elseif ( is_bool( $value ) ) {
			return ( $value ) ? 'true' : 'false';
		} elseif ( is_scalar( $value ) ) {
			return $value;
		} elseif ( is_object( $value ) ) {
			$class = get_class( $value );

			switch ( true ) {

				case ( $value instanceof WP_Post ):
				case ( $value instanceof WP_User ):
					$class = sprintf( '%s (ID: %s)', $class, $value->ID );
					break;

				case ( $value instanceof WP_Term ):
					$class = sprintf( '%s (term_id: %s)', $class, $value->term_id );
					break;

				case ( $value instanceof WP_Comment ):
					$class = sprintf( '%s (comment_ID: %s)', $class, $value->comment_ID );
					break;

				case ( $value instanceof WP_Error ):
					$class = sprintf( '%s (%s)', $class, $value->get_error_code() );
					break;

				case ( $value instanceof WP_Role ):
				case ( $value instanceof WP_Post_Type ):
				case ( $value instanceof WP_Taxonomy ):
					$class = sprintf( '%s (%s)', $class, $value->name );
					break;

				case ( $value instanceof WP_Network ):
					$class = sprintf( '%s (id: %s)', $class, $value->id );
					break;

				case ( $value instanceof WP_Site ):
					$class = sprintf( '%s (blog_id: %s)', $class, $value->blog_id );
					break;

				case ( $value instanceof WP_Theme ):
					$class = sprintf( '%s (%s)', $class, $value->get_stylesheet() );
					break;

			}

			return $class;
		} else {
			return gettype( $value );
		}
	}

	/**
	 * Shortens a fully qualified name to reduce the length of the names of long namespaced symbols.
	 *
	 * This initialises portions that do not form the first or last portion of the name. For example:
	 *
	 *     Inpsyde\Wonolog\HookListener\HookListenersRegistry->hook_callback()
	 *
	 * becomes:
	 *
	 *     Inpsyde\W\H\HookListenersRegistry->hook_callback()
	 *
	 * @param string $fqn A fully qualified name.
	 * @return string A shortened version of the name.
	 */
	public static function shorten_fqn( $fqn ) {
		if ( substr_count( $fqn, '\\' ) < 3 ) {
			return $fqn;
		}

		return preg_replace_callback( '#\\\\[a-zA-Z0-9_\\\\]{4,}\\\\#', function( array $matches ) {
			preg_match_all( '#\\\\([a-zA-Z0-9_])#', $matches[0], $m );
			return '\\' . implode( '\\', $m[1] ) . '\\';
		}, $fqn );
	}

	/**
	 * Helper function for JSON encoding data and formatting it in a consistent manner.
	 *
	 * @param mixed $data The data to be JSON encoded.
	 * @return string The JSON encoded data.
	 */
	public static function json_format( $data ) {
		// phpcs:ignore PHPCompatibility.Constants.NewConstants.json_unescaped_slashesFound
		$json_options = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES;

		$json = json_encode( $data, $json_options );

		if ( false === $json ) {
			return '';
		}

		return $json;
	}

	/**
	 * Returns the site editor URL for a given template or template part name.
	 *
	 * @param string $template The site template name, for example `twentytwentytwo//header-small-dark`.
	 * @param string $type     The template type, either 'wp_template_part' or 'wp_template'.
	 * @return string The admin URL for editing the site template.
	 */
	public static function get_site_editor_url( string $template, string $type = 'wp_template_part' ): string {
		return add_query_arg(
			array(
				'postType' => $type,
				'postId' => rawurlencode( $template ),
				'canvas' => 'edit',
			),
			admin_url( 'site-editor.php' )
		);
	}

	/**
	 * @deprecated
	 * @param mixed $data
	 * @return bool
	 */
	public static function is_stringy( $data ) {
		return ( is_string( $data ) || ( is_object( $data ) && method_exists( $data, '__toString' ) ) );
	}

	/**
	 * @param array<array-key, array<string, mixed>> $array
	 */
	public static function sort( array &$array, string $field ): void {
		usort( $array, fn( array $a, array $b ): int => ( $a[ $field ] <=> $b[ $field ] ) );
	}

	/**
	 * @param array<array-key, array<string, mixed>> $array
	 */
	public static function rsort( array &$array, string $field ): void {
		usort( $array, fn( array $a, array $b ): int => ( $b[ $field ] <=> $a[ $field ] ) );
	}
}
}
