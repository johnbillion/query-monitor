<?php declare(strict_types = 1);
/**
 * Function call stack trace container.
 *
 * @package query-monitor
 */

/**
 * @phpstan-type BacktraceFrameType array{
 *   file?: string,
 *   line?: int,
 *   function: string,
 *   args?: array<mixed>,
 *   class?: string,
 *   type?: '->'|'::',
 * }
 * @implements \IteratorAggregate<int,QM_StackFrame>
 */
final class QM_StackTrace implements IteratorAggregate {
	/**
	 * @var array<int,QM_StackFrame>
	 */
	protected $frames = array();

	/**
	 * @var array<string, true>
	 */
	protected static $ignore_class = array(
		'Altis\Cloud\DB' => true,
		'Debug_Bar_PHP' => true,
		'hyperdb' => true,
		'LudicrousDB' => true,
		'Perflab_SQLite_DB' => true,
		'QueryMonitor' => true,
		'W3_Db' => true,
		'WP_Hook' => true,
		'wpdb' => true,
		'Yoast\WP\Lib\ORM' => true,
	);

	/**
	 * @var array<string, bool>
	 * @TODO correct this doc ^
	 */
	protected static $ignore_method = array();

	/**
	 * @var array<string, true>
	 */
	protected static $ignore_func = array(
		'call_user_func_array' => true,
		'call_user_func' => true,
		'include_once' => true,
		'include' => true,
		'require_once' => true,
		'require' => true,
		'trigger_error' => true,
	);

	/**
	 * @var array<string, true>
	 */
	protected static $ignore_hook = array();

	/**
	 * @var array<string, int|string>
	 */
	protected static $show_args = array(
		'ai_get_template_part' => 2,
		'apply_filters_deprecated' => 1,
		'apply_filters_ref_array' => 1,
		'apply_filters' => 1,
		'author_can' => 4,
		'class_exists' => 2,
		'current_user_can_for_blog' => 4,
		'current_user_can' => 3,
		'do_action_deprecated' => 1,
		'do_action_ref_array' => 1,
		'do_action' => 1,
		'dynamic_sidebar' => 1,
		'get_extended_template_part' => 2,
		'get_footer' => 1,
		'get_header' => 1,
		'get_option' => 1,
		'get_query_template' => 1,
		'get_sidebar' => 1,
		'get_template_part' => 2,
		'get_transient' => 1,
		'load_template' => 'dir',
		'resolve_block_template' => 1,
		'set_transient' => 1,
		'user_can' => 4,
	);

	/**
	 * @var bool
	 */
	protected static $filtered = false;

	/**
	 * @var ?array<string, mixed>
	 */
	protected $options = null;

	/**
	 * @var ?QM_Component
	 */
	protected $component = null;

	/**
	 * @param array<int, array<string, mixed>> $trace
	 * @param array<string, mixed> $options
	 * @phpstan-param list<BacktraceFrameType> $trace
	 */
	public static function from_debug_backtrace( array $trace, array $options = array() ): self {
		return new static( $trace, $options );
	}

	/**
	 * @param array<string, mixed> $options
	 */
	public static function init( array $options = array() ): self {
		$trace = debug_backtrace( 0 );

		return new static( $trace, $options );
	}

	/**
	 * @param array<int, array<string, mixed>> $trace
	 * @param array<string, mixed> $options
	 * @phpstan-param list<BacktraceFrameType> $trace
	 */
	protected function __construct( array $trace, array $options = array() ) {
		$options = array_merge( array(
			'ignore_class' => array(),
			'ignore_method' => array(),
			'ignore_func' => array(),
			'ignore_hook' => array(),
			'show_args' => array(),
		), $options );

		if ( ! self::$filtered && function_exists( 'did_action' ) && did_action( 'plugins_loaded' ) ) {
			self::prepare_ignorance();
		}

		$this->options = array(
			'ignore_class' => array_filter( array_merge( self::$ignore_class, $options['ignore_class'] ) ),
			'ignore_method' => array_filter( array_merge( self::$ignore_method, $options['ignore_method'] ) ),
			'ignore_func' => array_filter( array_merge( self::$ignore_func, $options['ignore_func'] ) ),
			'ignore_hook' => array_filter( array_merge( self::$ignore_hook, $options['ignore_hook'] ) ),
			'show_args' => array_merge( self::$show_args, $options['show_args'] ),
		);

		$this->frames = array_values( array_filter( array_map( array( $this, 'process_frame' ), $trace ) ) );

		$this->options = null;
	}

	/**
	 * @param array<string, mixed> $frame
	 * @phpstan-param BacktraceFrameType $frame
	 */
	public function process_frame( array $frame ): ?QM_StackFrame {
		if ( ! isset( $frame['file'] ) || ! isset( $frame['line'] ) ) {
			return QM_StackFrame::from_minimal_frame( $frame );
		}

		if ( isset( $frame['class'], $frame['type'] ) ) {
			if ( isset( $this->options['ignore_class'][ $frame['class'] ] ) ) {
				return null;
			}

			if ( isset( $this->options['ignore_method'][ $frame['class'] ][ $frame['function'] ] ) ) {
				return null;
			}

			if ( 0 === strpos( $frame['class'], 'QM' ) ) {
				return null;
			}

			return QM_StackFrame::from_class_frame( $frame );
		}

		if ( isset( $this->options['ignore_func'][ $frame['function'] ] ) ) {
			return null;
		}

		$hook_functions = array(
			'apply_filters_deprecated' => true,
			'apply_filters_ref_array' => true,
			'apply_filters' => true,
			'do_action_deprecated' => true,
			'do_action_ref_array' => true,
			'do_action' => true,
		);

		if ( isset( $hook_functions[ $frame['function'] ], $frame['args'][0] ) && is_string( $frame['args'][0] ) && isset( $this->options['ignore_hook'][ $frame['args'][0] ] ) ) {
			return null;
		}

		return QM_StackFrame::from_function_frame( $frame );
	}

	/**
	 * This is used by, for example, HTTP header output
	 *
	 * @return array<int, string>
	 */
	public function get_stack(): array {
		return wp_list_pluck( $this->frames, 'display' );
	}

	public function get_caller(): ?QM_StackFrame {
		return reset( $this->frames ) ?: null;
	}

	public function get_component(): QM_Component {
		if ( isset( $this->component ) ) {
			return $this->component;
		}

		$components = array();

		foreach ( $this->frames as $frame ) {
			$component = $frame->get_component();

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

		foreach ( QM_Util::get_file_dirs() as $type => $dir ) {
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

	protected static function prepare_ignorance(): void {
		/**
		 * Filters which classes to ignore when constructing user-facing stack traces.
		 *
		 * @since 2.7.0
		 *
		 * @param array<string,true> $ignore_class Array of class names to ignore. The array keys are class names to ignore,
		 *                                         the array values are always boolean true.
		 */
		self::$ignore_class = apply_filters( 'qm/trace/ignore_class', self::$ignore_class );

		/**
		 * Filters which class methods to ignore when constructing user-facing stack traces.
		 *
		 * @since 2.7.0
		 *
		 * @param array<string,true> $ignore_method Array of method names to ignore. The array keys are method names to ignore,
		 *                                          the array values are always boolean true.
		 */
		self::$ignore_method = apply_filters( 'qm/trace/ignore_method', self::$ignore_method ); // @TODO correct this doc

		/**
		 * Filters which functions to ignore when constructing user-facing stack traces.
		 *
		 * @since 2.7.0
		 *
		 * @param array<string,true> $ignore_func Array of function names to ignore. The array keys are function names to ignore,
		 *                                        the array values are always boolean true.
		 */
		self::$ignore_func = apply_filters( 'qm/trace/ignore_func', self::$ignore_func );

		/**
		 * Filters which action and filter names to ignore when constructing user-facing stack traces.
		 *
		 * @since x.x.x
		 *
		 * @param array<string,true> $ignore_hook Array of hook names to ignore. The array keys are hook names to ignore,
		 *                                        the array values are always boolean true.
		 */
		self::$ignore_hook = apply_filters( 'qm/trace/ignore_hook', self::$ignore_hook );

		/**
		 * Filters the number of argument values to show for the given function name when constructing user-facing
		 * stack traces.
		 *
		 * @since 2.7.0
		 *
		 * @param (int|string)[] $show_args The number of argument values to show for the given function name. The
		 *                                  array keys are function names, the array values are either integers or
		 *                                  "dir" to specifically treat the function argument as a directory path.
		 */
		self::$show_args = apply_filters( 'qm/trace/show_args', self::$show_args );

		self::$filtered = true;
	}

	/**
	 * @return ArrayIterator<int,QM_StackFrame>
	 */
	#[\ReturnTypeWillChange]
	public function getIterator() {
		return new ArrayIterator( $this->frames );
	}
}
