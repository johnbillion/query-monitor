<?php
/*
Copyright 2009-2015 John Blackbourn

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

*/

if ( ! class_exists( 'QM_Backtrace' ) ) {
class QM_Backtrace {

	protected static $ignore_class = array(
		'wpdb'           => true,
		'QueryMonitor'   => true,
		'ExtQuery'       => true,
		'W3_Db'          => true,
		'Debug_Bar_PHP'  => true,
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
		'do_action'               => 1,
		'apply_filters'           => 1,
		'do_action_ref_array'     => 1,
		'apply_filters_ref_array' => 1,
		'get_template_part'       => 2,
		'load_template'           => 'dir',
		'get_header'              => 1,
		'get_sidebar'             => 1,
		'get_footer'              => 1,
	);
	protected static $filtered = false;
	protected $trace           = null;
	protected $filtered_trace  = null;
	protected $calling_line    = 0;
	protected $calling_file    = '';

	public function __construct( array $args = array() ) {
		# @TODO save the args as a property and process the trace JIT
		$args = array_merge( array(
			'ignore_current_filter' => true,
			'ignore_items'          => 0,
		), $args );
		$this->trace = debug_backtrace( false );
		$this->ignore( 1 ); # Self-awareness
		
		/**
		 * If error_handler() is in the trace, QM fails later when it tries
		 * to get $lowest['file'] in get_filtered_trace()
		 */ 
		if ( $this->trace[0]['function'] === 'error_handler' ) {
			$this->ignore( 1 );
		}


		if ( $args['ignore_items'] ) {
			$this->ignore( $args['ignore_items'] );
		}
		if ( $args['ignore_current_filter'] ) {
			$this->ignore_current_filter();
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

		foreach ( $this->trace as $item ) {

			try {

				if ( isset( $item['class'] ) ) {
					if ( !is_object( $item['class'] ) and !class_exists( $item['class'], false ) ) {
						continue;
					}
					if ( !method_exists( $item['class'], $item['function'] ) ) {
						continue;
					}
					$ref = new ReflectionMethod( $item['class'], $item['function'] );
					$file = $ref->getFileName();
				} else if ( function_exists( $item['function'] ) ) {
					$ref = new ReflectionFunction( $item['function'] );
					$file = $ref->getFileName();
				} else if ( isset( $item['file'] ) ) {
					$file = $item['file'];
				} else {
					continue;
				}

				$comp = QM_Util::get_file_component( $file );
				$components[$comp->type] = $comp;
			} catch ( ReflectionException $e ) {
				# nothing
			}

		}

		foreach ( QM_Util::get_file_dirs() as $type => $dir ) {
			if ( isset( $components[$type] ) ) {
				return $components[$type];
			}
		}

		# This should not happen

	}

	public function get_trace() {
		return $this->trace;
	}

	public function get_filtered_trace() {

		if ( !isset( $this->filtered_trace ) ) {

			$trace = array_map( array( $this, 'filter_trace' ), $this->trace );
			$trace = array_values( array_filter( $trace ) );

			if ( empty( $trace ) && !empty($this->trace) ) {
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
		for ( $i = 0; $i < absint( $num ); $i++ ) {
			unset( $this->trace[$i] );
		}
		$this->trace = array_values( $this->trace );
		return $this;
	}

	public function ignore_current_filter() {

		if ( isset( $this->trace[2] ) and isset( $this->trace[2]['function'] ) ) {
			if ( in_array( $this->trace[2]['function'], array( 'apply_filters', 'do_action' ) ) ) {
				$this->ignore( 3 ); # Ignore filter and action callbacks
			}
		}

	}

	public function filter_trace( array $trace ) {

		if ( !self::$filtered and function_exists( 'did_action' ) and did_action( 'plugins_loaded' ) ) {

			# Only run apply_filters on these once
			self::$ignore_class  = apply_filters( 'qm/trace/ignore_class',  self::$ignore_class );
			self::$ignore_method = apply_filters( 'qm/trace/ignore_method', self::$ignore_method );
			self::$ignore_func   = apply_filters( 'qm/trace/ignore_func',   self::$ignore_func );
			self::$show_args     = apply_filters( 'qm/trace/show_args',     self::$show_args );
			self::$filtered = true;

		}

		$return = $trace;

		if ( isset( $trace['class'] ) ) {

			if ( isset( self::$ignore_class[$trace['class']] ) ) {
				$return = null;
			} else if ( isset( self::$ignore_method[$trace['class']][$trace['function']] ) ) {
				$return = null;
			} else if ( 0 === strpos( $trace['class'], 'QM_' ) ) {
				$return = null;
			} else {
				$return['id']      = $trace['class'] . $trace['type'] . $trace['function'] . '()';
				$return['display'] = $trace['class'] . $trace['type'] . $trace['function'] . '()';
			}

		} else {

			if ( isset( self::$ignore_func[$trace['function']] ) ) {

				$return = null;

			} else if ( isset( self::$show_args[$trace['function']] ) ) {

				$show = self::$show_args[$trace['function']];

				if ( 'dir' === $show ) {
					if ( isset( $trace['args'][0] ) ) {
						$arg = QM_Util::standard_dir( $trace['args'][0], '~/' );
						$return['id']      = $trace['function'] . '()';
						$return['display'] = $trace['function'] . "('{$arg}')";
					}
				} else {
					$args = array();
					for ( $i = 0; $i < $show; $i++ ) {
						if ( isset( $trace['args'][$i] ) )
							$args[] = '\'' . $trace['args'][$i] . '\'';
					}
					$return['id']      = $trace['function'] . '()';
					$return['display'] = $trace['function'] . '(' . implode( ',', $args ) . ')';
				}

			} else {

				$return['id']      = $trace['function'] . '()';
				$return['display'] = $trace['function'] . '()';

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
