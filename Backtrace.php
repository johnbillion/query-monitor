<?php
/*
Copyright 2013 John Blackbourn

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

*/

class QM_Backtrace {

	protected static $ignore_class = array(
		'wpdb'           => true,
		'QueryMonitor'   => true,
		'QueryMonitorDB' => true,
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
		'section_template'        => 2,
		'load_template'           => 'dir',
		'get_header'              => 1,
		'get_sidebar'             => 1,
		'get_footer'              => 1,
	);
	protected static $filtered = false;
	protected $trace           = null;
	protected $filtered_trace  = null;

	public function __construct( array $args = array() ) {
		$args = array_merge( array(
			'ignore_current_filter' => true,
			'ignore_items'          => 0,
		), $args );
		$this->trace = debug_backtrace( false );
		$this->ignore( 1 ); # Self-awareness

		if ( $args['ignore_items'] )
			$this->ignore( $args['ignore_items'] );
		if ( $args['ignore_current_filter'] )
			$this->ignore_current_filter();

	}

	public function get_stack() {

		$trace = $this->get_filtered_trace();
		$stack = array();

		if ( empty( $trace ) ) {
			if ( isset( $this->trace[0]['file'] ) ) {
				$stack[] = QM_Util::standard_dir( $this->trace[0]['file'], '' );
			} else {
				$stack[] = __( 'Unknown', 'query-monitor' );
			}
		} else {
			$stack = wp_list_pluck( $trace, 'display' );
		}

		return $stack;

	}

	public function get_caller() {

		$trace = $this->get_filtered_trace();

		if ( empty( $trace ) ) {
			return reset( $this->trace );
		} else {
			return reset( $trace );
		}

	}

	public function get_component() {

		$components = array();

		foreach ( $this->trace as $item ) {

			try {

				if ( isset( $item['file'] ) ) {
					$file = $item['file'];
				} else if ( isset( $item['class'] ) ) {
					$ref = new ReflectionMethod( $item['class'], $item['function'] );
					$file = $ref->getFileName();
				} else {
					$ref = new ReflectionFunction( $item['function'] );
					$file = $ref->getFileName();
				}

				$comp = QM_Util::get_file_component( $file );
				$components[$comp->type] = $comp;
			} catch ( ReflectionException $e ) {
				# nothing
			}

		}

		foreach ( QM_Util::get_file_dirs() as $type => $dir ) {
			if ( isset( $components[$type] ) )
				return $components[$type];
		}

		# This should not happen

	}

	public function get_trace() {
		return $this->trace;
	}

	public function get_filtered_trace() {

		if ( !isset( $this->filtered_trace ) ) {
			$trace = array_map( 'QM_Backtrace::filter_trace', $this->trace );
			$trace = array_values( array_filter( $trace ) );
			$this->filtered_trace = $trace;
		}

		return $this->filtered_trace;

	}

	public function ignore( $num ) {
		for ( $i = 0; $i < absint( $num ); $i++ )
			unset( $this->trace[$i] );
		$this->trace = array_values( $this->trace );
		return $this;
	}

	public function ignore_current_filter() {

		if ( isset( $this->trace[2] ) and isset( $this->trace[2]['function'] ) ) {
			if ( in_array( $this->trace[2]['function'], array( 'apply_filters', 'do_action' ) ) )
				$this->ignore( 3 ); # Ignore filter and action callbacks
		}

	}

	public static function filter_trace( array $trace ) {

		if ( !self::$filtered and function_exists( 'did_action' ) and did_action( 'plugins_loaded' ) ) {

			# Only run apply_filters on these once
			self::$ignore_class  = apply_filters( 'query_monitor_ignore_class',  self::$ignore_class );
			self::$ignore_method = apply_filters( 'query_monitor_ignore_method', self::$ignore_method );
			self::$ignore_func   = apply_filters( 'query_monitor_ignore_func',   self::$ignore_func );
			self::$show_args     = apply_filters( 'query_monitor_show_args',     self::$show_args );
			self::$filtered = true;

		}

		if ( isset( $trace['class'] ) ) {

			if ( isset( self::$ignore_class[$trace['class']] ) ) {
				return null;
			} else if ( isset( self::$ignore_method[$trace['class']][$trace['function']] ) ) {
				return null;
			} else if ( 0 === strpos( $trace['class'], 'QM_' ) ) {
				return null;
			} else {
				$trace['id']      = $trace['class'] . $trace['type'] . $trace['function'] . '()';
				$trace['display'] = $trace['class'] . $trace['type'] . $trace['function'] . '()';
				return $trace;
			}

		} else {

			if ( isset( self::$ignore_func[$trace['function']] ) ) {

				return null;

			} else if ( isset( self::$show_args[$trace['function']] ) ) {

				$show = self::$show_args[$trace['function']];
				if ( 'dir' === $show ) {
					if ( isset( $trace['args'][0] ) ) {
						$arg = QM_Util::standard_dir( $trace['args'][0], '&hellip;/' );
						$trace['id']      = $trace['function'] . '()';
						$trace['display'] = $trace['function'] . "('{$arg}')";
						return $trace;
					}
				} else {
					$args = array();
					for ( $i = 0; $i < $show; $i++ ) {
						if ( isset( $trace['args'][$i] ) )
							$args[] = sprintf( "'%s'", $trace['args'][$i] );
					}
					$trace['id']      = $trace['function'] . '()';
					$trace['display'] = $trace['function'] . '(' . implode( ',', $args ) . ')';
					return $trace;
				}

			}

			$trace['id']      = $trace['function'] . '()';
			$trace['display'] = $trace['function'] . '()';
			return $trace;

		}

	}

}
