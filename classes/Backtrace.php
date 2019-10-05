<?php
/**
 * Function call backtrace container.
 *
 * @package query-monitor
 */

if ( ! class_exists( 'QM_Backtrace' ) ) {
class QM_Backtrace {

	protected static $ignore_class = array(
		'wpdb'           => true,
		'QueryMonitor'   => true,
		'W3_Db'          => true,
		'Debug_Bar_PHP'  => true,
		'WP_Hook'        => true,
	);
	protected static $ignore_method = array();
	protected static $ignore_func = array(
		'include_once'         => true,
		'require_once'         => true,
		'include'              => true,
		'require'              => true,
		'call_user_func_array' => true,
		'call_user_func'       => true,
		'trigger_error'        => true,
		'_doing_it_wrong'      => true,
		'_deprecated_argument' => true,
		'_deprecated_file'     => true,
		'_deprecated_function' => true,
		'dbDelta'              => true,
	);
	protected static $show_args = array(
		'do_action'                  => 1,
		'apply_filters'              => 1,
		'do_action_ref_array'        => 1,
		'apply_filters_ref_array'    => 1,
		'get_template_part'          => 2,
		'get_extended_template_part' => 2,
		'load_template'              => 'dir',
		'dynamic_sidebar'            => 1,
		'get_header'                 => 1,
		'get_sidebar'                => 1,
		'get_footer'                 => 1,
		'class_exists'               => 2,
		'current_user_can'           => 3,
		'user_can'                   => 4,
		'current_user_can_for_blog'  => 4,
		'author_can'                 => 4,
	);
	protected static $filtered = false;
	protected $trace           = null;
	protected $filtered_trace  = null;
	protected $calling_line    = 0;
	protected $calling_file    = '';

	public function __construct( array $args = array() ) {
		$this->trace = debug_backtrace( false );

		$args = array_merge( array(
			'ignore_current_filter' => true,
			'ignore_frames'         => 0,
		), $args );

		$this->ignore( 1 ); # Self-awareness

		/**
		 * If error_handler() is in the trace, QM fails later when it tries
		 * to get $lowest['file'] in get_filtered_trace()
		 */
		if ( 'error_handler' === $this->trace[0]['function'] ) {
			$this->ignore( 1 );
		}

		if ( $args['ignore_frames'] ) {
			$this->ignore( $args['ignore_frames'] );
		}
		if ( $args['ignore_current_filter'] ) {
			$this->ignore_current_filter();
		}

		foreach ( $this->trace as $k => $frame ) {
			if ( ! isset( $frame['args'] ) ) {
				continue;
			}

			if ( isset( $frame['function'] ) && isset( self::$show_args[ $frame['function'] ] ) ) {
				$show = self::$show_args[ $frame['function'] ];

				if ( 'dir' === $show ) {
					$show = 1;
				}

				$frame['args'] = array_slice( $frame['args'], 0, $show );

			} else {
				unset( $frame['args'] );
			}

			$this->trace[ $k ] = $frame;
		}
	}

	public function get_stack() {

		$trace = $this->get_filtered_trace();
		$stack = wp_list_pluck( $trace, 'display' );

		return $stack;

	}

	public function get_caller() {

		$trace = $this->get_filtered_trace();

		return reset( $trace );

	}

	public function get_component() {

		$components = array();

		foreach ( $this->trace as $frame ) {
			$component = self::get_frame_component( $frame );

			if ( $component ) {
				if ( 'plugin' === $component->type ) {
					// If the component is a plugin then it can't be anything else,
					// so short-circuit and return early.
					return $component;
				}

				$components[ $component->type ] = $component;
			}
		}

		foreach ( QM_Util::get_file_dirs() as $type => $dir ) {
			if ( isset( $components[ $type ] ) ) {
				return $components[ $type ];
			}
		}

		# This should not happen

	}

	public static function get_frame_component( array $frame ) {
			try {

				if ( isset( $frame['class'] ) ) {
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

				return QM_Util::get_file_component( $file );

			} catch ( ReflectionException $e ) {
				return null;
			}
	}

	public function get_trace() {
		return $this->trace;
	}

	public function get_display_trace() {
		return $this->get_filtered_trace();
	}

	public function get_filtered_trace() {

		if ( ! isset( $this->filtered_trace ) ) {

			$trace = array_map( array( $this, 'filter_trace' ), $this->trace );
			$trace = array_values( array_filter( $trace ) );

			if ( empty( $trace ) && ! empty( $this->trace ) ) {
				$lowest                 = $this->trace[0];
				$file                   = QM_Util::standard_dir( $lowest['file'], '' );
				$lowest['calling_file'] = $lowest['file'];
				$lowest['calling_line'] = $lowest['line'];
				$lowest['function']     = $file;
				$lowest['display']      = $file;
				$lowest['id']           = $file;
				unset( $lowest['class'], $lowest['args'], $lowest['type'] );
				$trace[0] = $lowest;
			}

			$this->filtered_trace = $trace;

		}

		return $this->filtered_trace;

	}

	public function ignore( $num ) {
		for ( $i = 0; $i < $num; $i++ ) {
			unset( $this->trace[ $i ] );
		}
		$this->trace = array_values( $this->trace );
		return $this;
	}

	public function ignore_current_filter() {

		if ( isset( $this->trace[2] ) && isset( $this->trace[2]['function'] ) ) {
			if ( in_array( $this->trace[2]['function'], array( 'apply_filters', 'do_action' ), true ) ) {
				$this->ignore( 3 ); # Ignore filter and action callbacks
			}
		}

	}

	public function filter_trace( array $trace ) {

		if ( ! self::$filtered && function_exists( 'did_action' ) && did_action( 'plugins_loaded' ) ) {

			/**
			 * Filters which classes to ignore when constructing user-facing call stacks.
			 *
			 * @since 2.7.0
			 *
			 * @param bool[] $ignore_class Array of class names to ignore. The array keys are class names to ignore,
			 *                             the array values are whether to ignore the class or not (usually true).
			 */
			self::$ignore_class  = apply_filters( 'qm/trace/ignore_class',  self::$ignore_class );

			/**
			 * Filters which class methods to ignore when constructing user-facing call stacks.
			 *
			 * @since 2.7.0
			 *
			 * @param bool[] $ignore_method Array of method names to ignore. The array keys are method names to ignore,
			 *                              the array values are whether to ignore the method or not (usually true).
			 */
			self::$ignore_method = apply_filters( 'qm/trace/ignore_method', self::$ignore_method );

			/**
			 * Filters which functions to ignore when constructing user-facing call stacks.
			 *
			 * @since 2.7.0
			 *
			 * @param bool[] $ignore_func Array of function names to ignore. The array keys are function names to ignore,
			 *                            the array values are whether to ignore the function or not (usually true).
			 */
			self::$ignore_func   = apply_filters( 'qm/trace/ignore_func',   self::$ignore_func );

			/**
			 * Filters the number of argument values to show for the given function name when constructing user-facing
			 * call stacks.
			 *
			 * @since 2.7.0
			 *
			 * @param (int|string)[] $show_args The number of argument values to show for the given function name. The
			 *                                  array keys are function names, the array values are either integers or
			 *                                  "dir" to specifically treat the function argument as a directory path.
			 */
			self::$show_args     = apply_filters( 'qm/trace/show_args',     self::$show_args );

			self::$filtered = true;

		}

		$return = $trace;

		if ( isset( $trace['class'] ) ) {
			if ( isset( self::$ignore_class[ $trace['class'] ] ) ) {
				$return = null;
			} elseif ( isset( self::$ignore_method[ $trace['class'] ][ $trace['function'] ] ) ) {
				$return = null;
			} elseif ( 0 === strpos( $trace['class'], 'QM' ) ) {
				$return = null;
			} else {
				$return['id']      = $trace['class'] . $trace['type'] . $trace['function'] . '()';
				$return['display'] = QM_Util::shorten_fqn( $trace['class'] . $trace['type'] . $trace['function'] ) . '()';
			}
		} else {
			if ( isset( self::$ignore_func[ $trace['function'] ] ) ) {
				$return = null;
			} elseif ( isset( self::$show_args[ $trace['function'] ] ) ) {
				$show = self::$show_args[ $trace['function'] ];

				if ( 'dir' === $show ) {
					if ( isset( $trace['args'][0] ) ) {
						$arg = QM_Util::standard_dir( $trace['args'][0], '' );
						$return['id']      = $trace['function'] . '()';
						$return['display'] = QM_Util::shorten_fqn( $trace['function'] ) . "('{$arg}')";
					}
				} else {
					$args = array();
					for ( $i = 0; $i < $show; $i++ ) {
						if ( isset( $trace['args'][ $i ] ) ) {
							if ( is_string( $trace['args'][ $i ] ) ) {
								$args[] = '\'' . $trace['args'][ $i ] . '\'';
							} else {
								$args[] = QM_Util::display_variable( $trace['args'][ $i ] );
							}
						}
					}
					$return['id']      = $trace['function'] . '()';
					$return['display'] = QM_Util::shorten_fqn( $trace['function'] ) . '(' . implode( ',', $args ) . ')';
				}
			} else {
				$return['id']      = $trace['function'] . '()';
				$return['display'] = QM_Util::shorten_fqn( $trace['function'] ) . '()';
			}
		}

		if ( $return ) {

			$return['calling_file'] = $this->calling_file;
			$return['calling_line'] = $this->calling_line;

		}

		if ( isset( $trace['line'] ) ) {
			$this->calling_line = $trace['line'];
		}
		if ( isset( $trace['file'] ) ) {
			$this->calling_file = $trace['file'];
		}

		return $return;

	}

}
} else {

	add_action( 'init', 'QueryMonitor::symlink_warning' );

}
