<?php declare(strict_types = 1);
/**
 * Function call backtrace container.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'QM_Backtrace' ) ) {
class QM_Backtrace {

	/**
	 * @var array<string, bool>
	 */
	protected static $ignore_class = array(
		'wpdb' => true,
		'hyperdb' => true,
		'LudicrousDB' => true,
		'QueryMonitor' => true,
		'W3_Db' => true,
		'Debug_Bar_PHP' => true,
		'WP_Hook' => true,
		'Altis\Cloud\DB' => true,
		'Yoast\WP\Lib\ORM' => true,
		'Perflab_SQLite_DB' => true,
		'WP_SQLite_DB' => true,
	);

	/**
	 * @var array<string, array<string, bool>>
	 */
	protected static $ignore_method = array();

	/**
	 * @var array<string, bool>
	 */
	protected static $ignore_func = array(
		'include_once' => true,
		'require_once' => true,
		'include' => true,
		'require' => true,
		'call_user_func_array' => true,
		'call_user_func' => true,
		'trigger_error' => true,
		'_doing_it_wrong' => true,
		'_deprecated_argument' => true,
		'_deprecated_constructor' => true,
		'_deprecated_file' => true,
		'_deprecated_function' => true,
		'_deprecated_hook' => true,
		'dbDelta' => true,
		'maybe_create_table' => true,
	);

	/**
	 * @var array<string, int|string>
	 */
	protected static $show_args = array(
		'do_action' => 1,
		'apply_filters' => 1,
		'do_action_ref_array' => 1,
		'apply_filters_ref_array' => 1,
		'do_action_deprecated' => 1,
		'apply_filters_deprecated' => 1,
		'get_query_template' => 1,
		'resolve_block_template' => 1,
		'get_template_part' => 2,
		'get_extended_template_part' => 2,
		'ai_get_template_part' => 2,
		'load_template' => 'dir',
		'dynamic_sidebar' => 1,
		'get_header' => 1,
		'get_sidebar' => 1,
		'get_footer' => 1,
		'get_transient' => 1,
		'set_transient' => 1,
		'class_exists' => 2,
		'current_user_can' => 3,
		'user_can' => 4,
		'current_user_can_for_blog' => 4,
		'author_can' => 4,
	);

	/**
	 * @var array<string, bool>
	 */
	protected static $ignore_hook = array();

	/**
	 * @var bool
	 */
	protected static $filtered = false;

	/**
	 * @var array<string, mixed[]>
	 */
	protected $args = array();

	/**
	 * @var mixed[]
	 */
	protected $trace;

	/**
	 * @var mixed[]|null
	 */
	protected $filtered_trace = null;

	/**
	 * @var int
	 */
	protected $calling_line = 0;

	/**
	 * @var string
	 */
	protected $calling_file = '';

	/**
	 * @var QM_Component|null
	 */
	protected $component = null;

	/**
	 * @var mixed[]|null
	 */
	protected $top_frame = null;

	/**
	 * @param array<string, mixed[]> $args
	 * @param mixed[] $trace
	 */
	public function __construct( array $args = array(), array $trace = null ) {
		$this->trace = $trace ?? debug_backtrace( 0 );

		$this->args = array_merge( array(
			'ignore_class' => array(),
			'ignore_method' => array(),
			'ignore_func' => array(),
			'ignore_hook' => array(),
			'show_args' => array(),
		), $args );

		foreach ( $this->trace as & $frame ) {
			if ( ! isset( $frame['args'] ) ) {
				continue;
			}

			if ( isset( $frame['function'], self::$show_args[ $frame['function'] ] ) ) {
				$show = self::$show_args[ $frame['function'] ];

				if ( ! is_int( $show ) ) {
					$show = 1;
				}

				$frame['args'] = array_slice( $frame['args'], 0, $show );

			} else {
				unset( $frame['args'] );
			}
		}
	}

	/**
	 * @param mixed[] $frame
	 * @return void
	 */
	public function push_frame( array $frame ) {
		$this->top_frame = $frame;
	}

	/**
	 * @return array<int, string>
	 */
	public function get_stack() {

		$trace = $this->get_filtered_trace();
		$stack = array_column( $trace, 'display' );

		return $stack;

	}

	/**
	 * @return mixed[]|false
	 */
	public function get_caller() {

		$trace = $this->get_filtered_trace();

		return reset( $trace );

	}

	/**
	 * @return QM_Component
	 */
	public function get_component() {
		if ( isset( $this->component ) ) {
			return $this->component;
		}

		$components = array();
		$frames = $this->get_filtered_trace();

		if ( $this->top_frame ) {
			array_unshift( $frames, $this->top_frame );
		}

		foreach ( $frames as $frame ) {
			$component = self::get_frame_component( $frame );

			if ( $component ) {
				if ( 'plugin' === $component->type ) {
					// If the component is a plugin then it can't be anything else,
					// so short-circuit and return early.
					$this->component = $component;
					return $this->component;
				}

				$components[ $component->type ] = $component;
			}
		}

		$file_dirs = QM_Util::get_file_dirs();
		$file_dirs['dropin'] = WP_CONTENT_DIR;

		foreach ( $file_dirs as $type => $dir ) {
			if ( isset( $components[ $type ] ) ) {
				$this->component = $components[ $type ];
				return $this->component;
			}
		}

		$component = new QM_Component();
		$component->type = 'unknown';
		$component->name = __( 'Unknown', 'query-monitor' );
		$component->context = 'unknown';

		return $component;
	}

	/**
	 * Attempts to determine the component responsible for a given frame.
	 *
	 * @param mixed[] $frame A single frame from a trace.
	 * @phpstan-param array{
	 *   class?: class-string,
	 *   function?: string,
	 *   file?: string,
	 * } $frame
	 * @return QM_Component|null An object representing the component, or null if
	 *                           the component cannot be determined.
	 */
	public static function get_frame_component( array $frame ) {
		try {

			if ( isset( $frame['class'], $frame['function'] ) ) {
				if ( ! class_exists( $frame['class'], false ) ) {
					return null;
				}
				if ( ! method_exists( $frame['class'], $frame['function'] ) ) {
					return null;
				}
				$ref = new ReflectionMethod( $frame['class'], $frame['function'] );
				$file = $ref->getFileName();
			} elseif ( isset( $frame['function'] ) && function_exists( $frame['function'] ) ) {
				$ref = new ReflectionFunction( $frame['function'] );
				$file = $ref->getFileName();
			} elseif ( isset( $frame['file'] ) ) {
				$file = $frame['file'];
			} else {
				return null;
			}

			if ( ! $file ) {
				return null;
			}

			return QM_Util::get_file_component( $file );

		} catch ( ReflectionException $e ) {
			return null;
		}
	}

	/**
	 * @return mixed[]
	 */
	public function get_trace() {
		return $this->trace;
	}

	/**
	 * @deprecated Use the `::get_filtered_trace()` method instead.
	 *
	 * @return mixed[]
	 */
	public function get_display_trace() {
		return $this->get_filtered_trace();
	}

	/**
	 * @return array<int, array<string, mixed>>
	 * @phpstan-return list<array{
	 *   file: string,
	 *   line: int,
	 *   display: string,
	 * }>
	 */
	public function get_filtered_trace() {

		if ( ! isset( $this->filtered_trace ) ) {

			$trace = array_map( array( $this, 'filter_trace' ), $this->trace );
			$trace = array_values( array_filter( $trace ) );

			if ( empty( $trace ) && ! empty( $this->trace ) ) {
				$lowest = $this->trace[0];
				$file = QM_Util::standard_dir( $lowest['file'], '' );
				$lowest['calling_file'] = $lowest['file'];
				$lowest['calling_line'] = $lowest['line'];
				$lowest['function'] = $file;
				$lowest['display'] = $file;
				$lowest['id'] = $file;
				unset( $lowest['class'], $lowest['args'], $lowest['type'] );

				// When a PHP error is triggered which doesn't have a stack trace, for example a
				// deprecated error, QM will blame itself due to its error handler. This prevents that.
				if ( false === strpos( $file, 'query-monitor/collectors/php_errors.php' ) ) {
					$trace[0] = $lowest;
				}
			}

			$this->filtered_trace = $trace;

		}

		return $this->filtered_trace;

	}

	/**
	 * @param array<int, string> $stack
	 * @return array<int, string>
	 */
	public static function get_filtered_stack( array $stack ) {
		$trace = new self( array(), array() );
		$return = array();

		foreach ( $stack as $i => $item ) {
			$frame = array(
				'function' => $item,
			);

			if ( false !== strpos( $item, '->' ) ) {
				list( $class, $function ) = explode( '->', $item );
				$frame = array(
					'class' => $class,
					'type' => '->',
					'function' => $function,
				);
			}

			if ( false !== strpos( $item, '::' ) ) {
				list( $class, $function ) = explode( '::', $item );
				$frame = array(
					'class' => $class,
					'type' => '::',
					'function' => $function,
				);
			}

			$frame['args'] = array();

			if ( $trace->filter_trace( $frame ) ) {
				$return[] = $item;
			}
		}

		return $return;
	}

	/**
	 * @deprecated Use the `ignore_class`, `ignore_method`, `ignore_func`, and `ignore_hook` arguments instead.
	 *
	 * @param int $num
	 * @return self
	 */
	public function ignore( $num ) {
		for ( $i = 0; $i < $num; $i++ ) {
			unset( $this->trace[ $i ] );
		}
		$this->trace = array_values( $this->trace );
		return $this;
	}

	/**
	 * @param mixed[] $frame
	 * @return mixed[]|null
	 */
	public function filter_trace( array $frame ) {

		if ( ! self::$filtered && function_exists( 'did_action' ) && did_action( 'plugins_loaded' ) ) {

			/**
			 * Filters which classes to ignore when constructing user-facing call stacks.
			 *
			 * @since 2.7.0
			 *
			 * @param array<string, bool> $ignore_class Array of class names to ignore. The array keys are class names to ignore,
			 *                                          the array values are whether to ignore the class (usually true).
			 */
			self::$ignore_class = apply_filters( 'qm/trace/ignore_class', self::$ignore_class );

			/**
			 * Filters which class methods to ignore when constructing user-facing call stacks.
			 *
			 * @since 2.7.0
			 *
			 * @param array<string, array<string, bool>> $ignore_method Array of method names to ignore. The top level array keys are
			 *                                                          class names, the second level array keys are method names, and
			 *                                                          the array values are whether to ignore the method (usually true).
			 */
			self::$ignore_method = apply_filters( 'qm/trace/ignore_method', self::$ignore_method );

			/**
			 * Filters which functions to ignore when constructing user-facing call stacks.
			 *
			 * @since 2.7.0
			 *
			 * @param array<string, bool> $ignore_func Array of function names to ignore. The array keys are function names to ignore,
			 *                                         the array values are whether to ignore the function (usually true).
			 */
			self::$ignore_func = apply_filters( 'qm/trace/ignore_func', self::$ignore_func );

			/**
			 * Filters which action and filter names to ignore when constructing user-facing call stacks.
			 *
			 * @since 3.8.0
			 *
			 * @param array<string, bool> $ignore_hook Array of hook names to ignore. The array keys are hook names to ignore,
			 *                                         the array values are whether to ignore the hook (usually true).
			 */
			self::$ignore_hook = apply_filters( 'qm/trace/ignore_hook', self::$ignore_hook );

			/**
			 * Filters the number of argument values to show for the given function name when constructing user-facing
			 * call stacks.
			 *
			 * @since 2.7.0
			 *
			 * @param array<string,int|string> $show_args The number of argument values to show for the given function name. The
			 *                                            array keys are function names, the array values are either integers or
			 *                                            "dir" to specifically treat the function argument as a directory path.
			 */
			self::$show_args = apply_filters( 'qm/trace/show_args', self::$show_args );

			self::$filtered = true;

		}

		$return = $frame;
		$ignore_class = array_filter( array_merge( self::$ignore_class, $this->args['ignore_class'] ) );
		$ignore_method = array_filter( array_merge( self::$ignore_method, $this->args['ignore_method'] ) );
		$ignore_func = array_filter( array_merge( self::$ignore_func, $this->args['ignore_func'] ) );
		$ignore_hook = array_filter( array_merge( self::$ignore_hook, $this->args['ignore_hook'] ) );
		$show_args = array_merge( self::$show_args, $this->args['show_args'] );

		$hook_functions = array(
			'apply_filters' => true,
			'do_action' => true,
			'apply_filters_ref_array' => true,
			'do_action_ref_array' => true,
			'apply_filters_deprecated' => true,
			'do_action_deprecated' => true,
		);

		if ( ! isset( $frame['function'] ) ) {
			$frame['function'] = '(unknown)';
		}

		if ( isset( $frame['class'] ) ) {
			if ( isset( $ignore_class[ $frame['class'] ] ) ) {
				$return = null;
			} elseif ( isset( $ignore_method[ $frame['class'] ][ $frame['function'] ] ) ) {
				$return = null;
			} elseif ( 0 === strpos( $frame['class'], 'QM' ) ) {
				$return = null;
			} else {
				$return['id'] = $frame['class'] . $frame['type'] . $frame['function'] . '()';
				$return['display'] = QM_Util::shorten_fqn( $frame['class'] . $frame['type'] . $frame['function'] ) . '()';
			}
		} else {
			if ( isset( $ignore_func[ $frame['function'] ] ) ) {
				$return = null;
			} elseif ( isset( $show_args[ $frame['function'] ] ) ) {
				$show = $show_args[ $frame['function'] ];

				if ( 'dir' === $show ) {
					if ( isset( $frame['args'][0] ) ) {
						$arg = QM_Util::standard_dir( $frame['args'][0], '' );
						$return['id'] = $frame['function'] . '()';
						$return['display'] = QM_Util::shorten_fqn( $frame['function'] ) . "('{$arg}')";
					}
				} else {
					if ( isset( $hook_functions[ $frame['function'] ], $frame['args'][0] ) && is_string( $frame['args'][0] ) && isset( $ignore_hook[ $frame['args'][0] ] ) ) {
						$return = null;
					} else {
						$args = array();
						for ( $i = 0; $i < $show; $i++ ) {
							if ( isset( $frame['args'] ) && array_key_exists( $i, $frame['args'] ) ) {
								if ( is_string( $frame['args'][ $i ] ) ) {
									$args[] = '\'' . $frame['args'][ $i ] . '\'';
								} else {
									$args[] = QM_Util::display_variable( $frame['args'][ $i ] );
								}
							}
						}
						$return['id'] = $frame['function'] . '()';
						$return['display'] = QM_Util::shorten_fqn( $frame['function'] ) . '(' . implode( ',', $args ) . ')';
					}
				}
			} else {
				$return['id'] = $frame['function'] . '()';
				$return['display'] = QM_Util::shorten_fqn( $frame['function'] ) . '()';
			}
		}

		if ( $return ) {

			$return['calling_file'] = $this->calling_file;
			$return['calling_line'] = $this->calling_line;

			if ( ! isset( $return['file'] ) ) {
				$return['file'] = $this->calling_file;
			}

			if ( ! isset( $return['line'] ) ) {
				$return['line'] = $this->calling_line;
			}
		}

		if ( isset( $frame['line'] ) ) {
			$this->calling_line = $frame['line'];
		}
		if ( isset( $frame['file'] ) ) {
			$this->calling_file = $frame['file'];
		}

		return $return;

	}

}
} else {

	add_action( 'init', 'QueryMonitor::symlink_warning' );

}
