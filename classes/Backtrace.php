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
/**
 * @phpstan-type ComponentFrame array{
 *   class: ?string,
 *   function: ?string,
 *   file: ?string,
 * }
 * @phpstan-type BacktraceArgs array{
 *   ignore_class?: mixed[],
 *   ignore_namespace?: mixed[],
 *   ignore_method?: mixed[],
 *   ignore_func?: mixed[],
 *   ignore_hook?: mixed[],
 *   show_args?: mixed[],
 *   callsite?: QM_Data_Callsite,
 *   time?: float,
 * }
 */
class QM_Backtrace implements JsonSerializable {

	/**
	 * Classes to trim from the top of the stack trace.
	 *
	 * @var array<string, bool>
	 */
	protected static $ignore_class = array(
		'wpdb' => true,
		'hyperdb' => true,
		'LudicrousDB' => true,
		'QueryMonitor' => true,
		'W3_Db' => true,
		'Debug_Bar_PHP' => true,
		'Altis\Cloud\DB' => true,
		'Yoast\WP\Lib\ORM' => true,
		'Perflab_SQLite_DB' => true,
		'WP_SQLite_DB' => true,
	);

	/**
	 * @var array<string, bool>
	 */
	protected static $ignore_namespace = array();

	/**
	 * @var array<string, array<string, bool>>
	 */
	protected static $ignore_method = array();

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
		'user_can_for_site' => 5,
		'current_user_can_for_site' => 4,
		'author_can' => 4,
		'include_once' => 'dir',
		'require_once' => 'dir',
		'include' => 'dir',
		'require' => 'dir',
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
	 * Compact trace retained for lazy component detection.
	 * Each entry holds only class, function, and file.
	 * Cleared once the component has been computed.
	 *
	 * @var list<ComponentFrame>
	 */
	protected $component_trace = array();

	/**
	 * The caller frame (first frame after filtering and trimming,
	 * before the frame 0 drop). Used by get_caller() for attribution.
	 *
	 * @var QM_Data_Stack_Frame|null
	 */
	protected $caller_frame = null;

	/**
	 * File/line from the innermost dropped frame. Seeded from frame 0
	 * and overwritten by trim_top_frames as it removes additional frames.
	 * Used during frame shifting to give the top visible frame a location.
	 *
	 * @var array{
	 *   file: string|null,
	 *   line: int|null,
	 * }
	 */
	protected $top_frame_location = array(
		'file' => null,
		'line' => null,
	);

	/**
	 * Pre-computed frame tuples for JSON output: [registryIndex, lineNumber].
	 *
	 * @var list<array{
	 *   int,
	 *   int|null,
	 * }>
	 */
	protected $output_frames = array();

	/**
	 * @var QM_Component|null
	 */
	protected $component = null;

	/**
	 * @var ?QM_Data_Callsite
	 */
	protected $callsite = null;

	/**
	 * Absolute timestamp from microtime( true ) when this backtrace was captured.
	 *
	 * @var float
	 */
	protected $time;

	/**
	 * @phpstan-param BacktraceArgs $args
	 * @param mixed[] $trace
	 */
	public function __construct( array $args = array(), ?array $trace = null ) {
		$trace ??= debug_backtrace( 0 );
		$this->time = $args['time'] ?? microtime( true );

		if ( isset( $args['callsite'] ) ) {
			$this->callsite = $args['callsite'];
			unset( $args['callsite'] );
		}

		unset( $args['time'] );

		/** @var array<string, mixed[]> $args */
		$args = array_merge( array(
			'ignore_class' => array(),
			'ignore_namespace' => array(),
			'ignore_method' => array(),
			'ignore_func' => array(),
			'ignore_hook' => array(),
			'show_args' => array(),
		), $args );

		self::ensure_filters_loaded();

		foreach ( $trace as & $frame ) {
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

		$filtered_trace = $this->build_filtered_trace( $trace, $args );
		$this->process_output_frames( $filtered_trace );

		$component_trace = array();

		foreach ( $filtered_trace as $frame ) {
			$component_trace[] = array(
				'class' => $frame->class,
				'function' => $frame->function,
				'file' => $frame->file,
			);
		}

		if ( function_exists( 'did_action' ) && did_action( 'after_setup_theme' ) ) {
			$this->get_component( $component_trace );
		} else {
			$this->component_trace = $component_trace;
		}

		// Free data that is only needed during construction.
		$this->top_frame_location = array(
			'file' => null,
			'line' => null,
		);
	}

	/**
	 * @deprecated Use the `callsite` argument instead.
	 *
	 * @param array{
	 *   file: string,
	 *   line: int,
	 * } $frame
	 * @return void
	 */
	public function push_frame( array $frame ) {
		$callsite = new QM_Data_Callsite();
		$callsite->file = $frame['file'];
		$callsite->line = $frame['line'];
		$this->callsite = $callsite;
	}

	/**
	 * @return ?QM_Data_Callsite
	 */
	public function get_callsite() {
		return $this->callsite;
	}

	/**
	 * @return float Absolute timestamp from microtime( true ).
	 */
	public function get_time() {
		return $this->time;
	}

	/**
	 * @param float $time Absolute timestamp from microtime( true ).
	 * @return void
	 */
	public function set_time( float $time ) {
		$this->time = $time;
	}

	/**
	 * @return list<string>
	 */
	public function get_stack() {
		$stack = array();

		foreach ( $this->output_frames as $tuple ) {
			$frame = QM_Frame_Registry::get_frame( $tuple[0] );

			if ( isset( $frame->args ) ) {
				$stack[] = $frame->id . '(' . $frame->args . ')';
			} else {
				$stack[] = $frame->id . '()';
			}
		}

		return $stack;
	}

	/**
	 * Returns the meaningful caller frame from the filtered trace.
	 *
	 * @return QM_Data_Stack_Frame|false
	 */
	public function get_caller() {
		if ( ! $this->caller_frame ) {
			return false;
		}

		return $this->caller_frame;

	}

	/**
	 * Determines the component responsible for this backtrace.
	 *
	 * @param list<ComponentFrame> $trace
	 */
	public function get_component( array $trace = array() ) : QM_Component {
		if ( isset( $this->component ) ) {
			return $this->component;
		}

		$trace = $trace ?: $this->component_trace;

		$components = array();

		if ( $this->callsite && $this->callsite->file ) {
			$component = QM_Util::get_file_component( $this->callsite->file );

			if ( $component->is_plugin() ) {
				// If the component is a plugin then it can't be anything else,
				// so short-circuit and return early.
				$this->component = $component;
				$this->component_trace = array();
				return $this->component;
			}

			$components[ $component->type ] = $component;
		}

		foreach ( $trace as $frame ) {
			$component = self::get_frame_component( $frame );

			if ( $component ) {
				if ( $component->is_plugin() ) {
					// If the component is a plugin then it can't be anything else,
					// so short-circuit and return early.
					$this->component = $component;
					$this->component_trace = array();
					return $this->component;
				}

				$components[ $component->type ] = $component;
			}
		}

		$this->component_trace = array();

		$file_dirs = QM_Util::get_file_dirs();
		$file_dirs[ QM_Component::TYPE_DROPIN ] = WP_CONTENT_DIR;

		foreach ( $file_dirs as $type => $dir ) {
			if ( isset( $components[ $type ] ) ) {
				$this->component = $components[ $type ];
				return $this->component;
			}
		}

		return QM_Component::from( QM_Component::TYPE_UNKNOWN, 'unknown' );
	}

	/**
	 * Attempts to determine the component responsible for a given frame.
	 *
	 * @param ComponentFrame $frame
	 */
	public static function get_frame_component( array $frame ) :? QM_Component {
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
	 * @deprecated Use the `::get_filtered_trace()` method instead.
	 *
	 * @return QM_Data_Stack_Frame[]
	 */
	public function get_display_trace() : array {
		return $this->get_filtered_trace();
	}

	/**
	 * Resolves frames from the registry into full QM_Data_Stack_Frame objects.
	 *
	 * @return QM_Data_Stack_Frame[]
	 */
	public function get_filtered_trace() : array {
		$frames = array();

		foreach ( $this->output_frames as [ $id, $line ] ) {
			$entry = QM_Frame_Registry::get_frame( $id );

			$frame = new QM_Data_Stack_Frame();
			$frame->id = $entry->id;
			$frame->file = $entry->file;
			$frame->line = $line;

			if ( isset( $entry->args ) ) {
				$frame->args = $entry->args;
			}

			$frames[] = $frame;
		}

		return $frames;
	}

	/**
	 * Builds the filtered trace from the raw backtrace.
	 *
	 * @param mixed[] $raw_trace Trace from debug_backtrace().
	 * @param array<string, mixed[]> $args
	 * @return QM_Backtrace_Frame[]
	 */
	protected function build_filtered_trace( array $raw_trace, array $args ): array {
		$ignore_namespace = array_filter( array_merge( self::$ignore_namespace, $args['ignore_namespace'] ) );
		$ignore_method    = array_filter( array_merge( self::$ignore_method, $args['ignore_method'] ) );
		$ignore_hook      = array_filter( array_merge( self::$ignore_hook, $args['ignore_hook'] ) );
		$show_args        = array_filter( array_merge( self::$show_args, $args['show_args'] ) );

		$trace = array();

		foreach ( $raw_trace as $frame ) {
			$filtered = $this->filter_trace( $frame, $ignore_namespace, $ignore_method, $ignore_hook, $show_args );

			if ( $filtered ) {
				$trace[] = $filtered;
			}
		}

		// Trim ignored classes and functions from the top of the stack.
		$trace = $this->trim_top_frames( $trace, $args );

		// Trim include/require frames from the bottom of the stack
		// (WordPress bootstrap noise).
		$trace = $this->trim_bottom_frames( $trace );

		// Store frame 0 as the caller for get_caller() attribution.
		if ( ! empty( $trace ) ) {
			$this->caller_frame = $trace[0]->to_data();
		}

		if ( empty( $trace ) && ! empty( $raw_trace ) ) {
			$lowest = $raw_trace[0];
			$file = isset( $lowest['file'] ) ? QM_Util::standard_dir( $lowest['file'] ) : '';

			// When a PHP error is triggered which doesn't have a stack trace, for example a
			// deprecated error, QM will blame itself due to its error handler. This prevents that.
			if ( false === strpos( $file, 'query-monitor/collectors/php_errors.php' ) ) {
				$fallback = new QM_Backtrace_Frame();
				$fallback->id = isset( $lowest['class'] )
					? $lowest['class'] . $lowest['type'] . $lowest['function']
					: $lowest['function'];
				$fallback->function = $lowest['function'];
				$fallback->file = $lowest['file'] ?? null;
				$fallback->line = $lowest['line'] ?? null;
				$trace[0] = $fallback;
			}
		}

		return $trace;
	}

	/**
	 * Trims ignored classes and functions from the top of the stack trace.
	 *
	 * @param QM_Backtrace_Frame[] $trace
	 * @param array<string, mixed[]> $args
	 * @return QM_Backtrace_Frame[]
	 */
	protected function trim_top_frames( array $trace, array $args ): array {
		$ignore_class = array_merge( self::$ignore_class, $args['ignore_class'] );
		$ignore_func = $args['ignore_func'];

		while ( ! empty( $trace ) ) {
			$frame = $trace[0];

			// QM's own classes are always trimmed from the top.
			// Their file/line is never useful so don't update top_frame_location.
			if ( isset( $frame->class ) && 0 === strpos( $frame->class, 'QM' ) ) {
				array_shift( $trace );
				continue;
			}

			if ( isset( $frame->class ) && isset( $ignore_class[ $frame->class ] ) ) {
				$this->top_frame_location = array(
					'file' => $frame->file,
					'line' => $frame->line,
				);
				array_shift( $trace );
				continue;
			}

			if ( isset( $frame->function ) && isset( $ignore_func[ $frame->function ] ) ) {
				$this->top_frame_location = array(
					'file' => $frame->file,
					'line' => $frame->line,
				);
				array_shift( $trace );
				continue;
			}

			break;
		}

		return $trace;
	}

	/**
	 * Trims include/require frames from the bottom of the stack trace.
	 *
	 * @param QM_Backtrace_Frame[] $trace
	 * @return QM_Backtrace_Frame[]
	 */
	protected static function trim_bottom_frames( array $trace ): array {
		$include_functions = array(
			'include' => true,
			'include_once' => true,
			'require' => true,
			'require_once' => true,
		);

		while ( ! empty( $trace ) ) {
			$frame = end( $trace );

			if ( isset( $frame->function ) && isset( $include_functions[ $frame->function ] ) ) {
				array_pop( $trace );
				continue;
			}

			break;
		}

		return $trace;
	}

	/**
	 * @param array<int, string> $stack
	 * @return array<int, string>
	 */
	public static function get_filtered_stack( array $stack ) {
		self::ensure_filters_loaded();

		$trace = new self( array(), array() );
		$ignore_namespace = self::$ignore_namespace;
		$ignore_method = self::$ignore_method;
		$ignore_hook = self::$ignore_hook;
		$show_args = self::$show_args;
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

			if ( $trace->filter_trace( $frame, $ignore_namespace, $ignore_method, $ignore_hook, $show_args ) ) {
				$return[] = $item;
			}
		}

		return $return;
	}

	/**
	 * Ensures that the filter hooks for trace configuration have been applied.
	 *
	 * @return void
	 */
	protected static function ensure_filters_loaded(): void {
		if ( ! self::$filtered && function_exists( 'did_action' ) && did_action( 'plugins_loaded' ) ) {

			/**
			 * Filters which classes to ignore when constructing user-facing call stacks.
			 *
			 * @since 2.7.0
			 *
			 * @param array<string, bool> $ignore_class Array of class names to ignore. The array keys are class names to ignore,
			 *                                          the array values are whether to ignore the class (usually true).
			 */
			self::$ignore_class = array_filter( apply_filters( 'qm/trace/ignore_class', self::$ignore_class ) );

			/**
			 * Filters which namespaces to ignore when constructing user-facing call stacks.
			 *
			 * @since 3.19.0
			 *
			 * @param array<string, bool> $ignore_namespace Array of namespace names to ignore. The array keys are namespace names to ignore,
			 *                                              the array values are whether to ignore the namespace (usually true).
			 */
			self::$ignore_namespace = array_filter( apply_filters( 'qm/trace/ignore_namespace', self::$ignore_namespace ) );

			/**
			 * Filters which class methods to ignore when constructing user-facing call stacks.
			 *
			 * @since 2.7.0
			 *
			 * @param array<string, array<string, bool>> $ignore_method Array of method names to ignore. The top level array keys are
			 *                                                          class names, the second level array keys are method names, and
			 *                                                          the array values are whether to ignore the method (usually true).
			 */
			self::$ignore_method = array_filter( apply_filters( 'qm/trace/ignore_method', self::$ignore_method ) );

			/**
			 * Filters which action and filter names to ignore when constructing user-facing call stacks.
			 *
			 * @since 3.8.0
			 *
			 * @param array<string, bool> $ignore_hook Array of hook names to ignore. The array keys are hook names to ignore,
			 *                                         the array values are whether to ignore the hook (usually true).
			 */
			self::$ignore_hook = array_filter( apply_filters( 'qm/trace/ignore_hook', self::$ignore_hook ) );

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
	}

	/**
	 * Formats a single trace frame and determines whether it should be hidden.
	 *
	 * @param mixed[] $frame A single frame from debug_backtrace().
	 * @param array<string, bool> $ignore_namespace
	 * @param array<string, array<string, bool>> $ignore_method
	 * @param array<string, bool> $ignore_hook
	 * @param array<string, int|string> $show_args
	 */
	public function filter_trace( array $frame, array $ignore_namespace, array $ignore_method, array $ignore_hook, array $show_args ) :? QM_Backtrace_Frame {

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

		$id = '';
		$frame_args = null;

			if ( isset( $frame['class'] ) ) {
			// WP_Hook methods are always filtered out. The do_action/apply_filters
			// frame below them is retained with its original file/line.
			if ( 'WP_Hook' === $frame['class'] ) {
				return null;
			}

			if ( isset( $ignore_method[ $frame['class'] ][ $frame['function'] ] ) ) {
				return null;
			}

			foreach ( array_keys( $ignore_namespace ) as $namespace ) {
				if ( 0 === strpos( $frame['class'], $namespace . '\\' ) ) {
					return null;
				}
			}

			$id = $frame['class'] . $frame['type'] . $frame['function'];
		} else {
			foreach ( array_keys( $ignore_namespace ) as $namespace ) {
				if ( 0 === strpos( $frame['function'], $namespace . '\\' ) ) {
					return null;
				}
			}

			if ( isset( $show_args[ $frame['function'] ] ) ) {
				$show = $show_args[ $frame['function'] ];

				if ( 'dir' === $show ) {
					if ( isset( $frame['args'][0] ) ) {
						$id = $frame['function'];
						$frame_args = QM_Util::standard_dir( $frame['args'][0], '' );
					}
				} else {
					if ( isset( $hook_functions[ $frame['function'] ], $frame['args'][0] ) && is_string( $frame['args'][0] ) && isset( $ignore_hook[ $frame['args'][0] ] ) ) {
						return null;
					}

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
					$id = $frame['function'];
					$frame_args = implode( ',', $args );
				}
			} else {
				$id = $frame['function'];
			}
		}

		$result = new QM_Backtrace_Frame();
		$result->id = $id;
		$result->args = $frame_args;
		$result->file = $frame['file'] ?? null;
		$result->line = $frame['line'] ?? null;
		$result->function = $frame['function'];

		if ( isset( $frame['class'] ) ) {
			$result->class = $frame['class'];
		}

		// Hook dispatch functions keep their original file/line so clicking
		// them takes you to the do_action/apply_filters call site, not to
		// the internal location where WP_Hook invokes the callback.
		if ( isset( $hook_functions[ $frame['function'] ] ) ) {
			$result->keep_file_line = true;
		}

		return $result;

	}

	/**
	 * Shifts file/line numbers and registers each frame with the frame
	 * registry. Called eagerly from the constructor so the registry is
	 * populated without needing a separate serialization pass.
	 *
	 * @param QM_Backtrace_Frame[] $trace
	 * @return void
	 */
	protected function process_output_frames( array $trace ): void {
		// Seed the shift from the innermost dropped/trimmed frame's location,
		// or from the call site for PHP error traces.
		$prev_file = $this->top_frame_location['file'] ?? ( $this->callsite ? $this->callsite->file : null );
		$prev_line = $this->top_frame_location['line'] ?? ( $this->callsite ? $this->callsite->line : null );

		foreach ( $trace as $frame ) {
			$data = $frame->to_data();

			if ( $frame->keep_file_line ) {
				// Hook dispatch frames (do_action, apply_filters) keep their
				// original file/line. Update prev so the frame below gets
				// shifted to this location (where it calls the hook).
				$prev_file = $frame->file;
				$prev_line = $frame->line;
			} else {
				// Shift file/line up by one frame so each frame shows where it
				// makes its call to the frame above, not where it was called from.
				$data->file = $prev_file;
				$data->line = $prev_line;

				$prev_file = $frame->file;
				$prev_line = $frame->line;
			}

			$this->output_frames[] = array( QM_Frame_Registry::register( $data ), $data->line );
		}
	}

	/**
	 * @phpstan-return array{
	 *   component: QM_Component,
	 *   callsite: ?QM_Data_Callsite,
	 *   frames: list<array{int, int|null}>,
	 *   time: float,
	 * }
	 */
	public function jsonSerialize(): array {
		return array(
			'component' => $this->get_component(),
			'callsite' => $this->callsite,
			'frames' => $this->output_frames,
			'time' => ( $this->time - $_SERVER['REQUEST_TIME_FLOAT'] ) * 1000,
		);
	}
}
} else {

	add_action( 'init', 'QueryMonitor::symlink_warning' );

}
